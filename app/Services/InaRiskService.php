<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Membaca indeks bahaya InaRISK BNPB di satu titik koordinat dan
 * menurunkan zona rumah (Merah/Kuning/Hijau) dari indeks tersebut.
 */
class InaRiskService
{
    /**
     * Indeks bahaya (0–1) di titik lat/lng untuk satu layer.
     * Mengembalikan null bila titik berada di luar area bahaya (NoData).
     *
     * @throws RuntimeException bila server InaRISK gagal / timeout (hasil gagal tidak di-cache)
     */
    public function indeksDi(string $layerId, float $lat, float $lng): ?float
    {
        $layer = config("inarisk.layers.$layerId");
        if (!$layer) {
            throw new RuntimeException("Layer InaRISK '$layerId' tidak dikenal.");
        }

        // ±11 m per 4 desimal — titik berdekatan memakai hasil yang sama.
        $key = sprintf('inarisk:%s:%.4f:%.4f', $layerId, $lat, $lng);

        $hasil = Cache::remember($key, now()->addDays(30), function () use ($layer, $lat, $lng) {
            return ['nilai' => $this->identify($layer['indeks'], $lat, $lng)];
        });

        return $hasil['nilai'];
    }

    public function levelDari(?float $indeks): ?string
    {
        if ($indeks === null) return null;
        if ($indeks > config('inarisk.batas_tinggi')) return 'tinggi';
        if ($indeks > config('inarisk.batas_sedang')) return 'sedang';
        return 'rendah';
    }

    public function zonaDari(?string $level): string
    {
        switch ($level) {
            case 'tinggi': return 'Merah';
            case 'sedang': return 'Kuning';
            default:       return 'Hijau'; // rendah / di luar area bahaya
        }
    }

    /**
     * Hitung & simpan zona satu rumah dari koordinatnya.
     *
     * @return array{id:int, zona:?string, indeks:?float, level:?string}
     * @throws RuntimeException bila server InaRISK gagal
     */
    public function perbaruiZonaRumah(int $idRumah): array
    {
        $rumah = DB::table('rumah')->where('id_rumah', $idRumah)->first(['id_rumah', 'latitude', 'longitude']);
        if (!$rumah) {
            throw new RuntimeException("Rumah #$idRumah tidak ditemukan.");
        }
        if (!is_numeric($rumah->latitude) || !is_numeric($rumah->longitude)) {
            return ['id' => $idRumah, 'zona' => null, 'indeks' => null, 'level' => null];
        }

        $indeks = $this->indeksDi(config('inarisk.zona_layer'), (float) $rumah->latitude, (float) $rumah->longitude);
        $level  = $this->levelDari($indeks);
        $zona   = $this->zonaDari($level);

        DB::table('rumah')->where('id_rumah', $idRumah)->update([
            'status_zona_tsunami' => $zona,
            'indeks_bahaya'       => $indeks,
            'zona_dianalisis_at'  => now(),
        ]);

        return ['id' => $idRumah, 'zona' => $zona, 'indeks' => $indeks, 'level' => $level];
    }

    private function identify(string $service, float $lat, float $lng): ?float
    {
        $url = rtrim(config('inarisk.base_url'), '/') . "/$service/ImageServer/identify";

        try {
            $response = Http::timeout(config('inarisk.timeout'))
                ->retry(2, 800)
                ->get($url, [
                    'geometry'           => json_encode(['x' => $lng, 'y' => $lat, 'spatialReference' => ['wkid' => 4326]]),
                    'geometryType'       => 'esriGeometryPoint',
                    'returnGeometry'     => 'false',
                    'returnCatalogItems' => 'false',
                    'f'                  => 'json',
                ]);
        } catch (\Throwable $e) {
            throw new RuntimeException('Server InaRISK tidak merespons: ' . $e->getMessage(), 0, $e);
        }

        $json = $response->json();
        if (!is_array($json) || isset($json['error']) || !array_key_exists('value', $json)) {
            $pesan = $json['error']['message'] ?? 'respons tidak dikenal';
            throw new RuntimeException("Server InaRISK gagal: $pesan");
        }

        return is_numeric($json['value']) ? max(0.0, min(1.0, (float) $json['value'])) : null;
    }
}
