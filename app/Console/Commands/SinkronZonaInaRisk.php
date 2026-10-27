<?php

namespace App\Console\Commands;

use App\Services\InaRiskService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SinkronZonaInaRisk extends Command
{
    protected $signature = 'inarisk:zona
                            {--semua : Hitung ulang semua rumah, termasuk yang sudah dianalisis}';

    protected $description = 'Hitung zona Merah/Kuning/Hijau rumah dari indeks bahaya InaRISK BNPB';

    public function handle(InaRiskService $inaRisk)
    {
        $ids = DB::table('rumah')
            ->whereNotNull('latitude')->whereNotNull('longitude')
            ->when(!$this->option('semua'), fn($q) => $q->whereNull('zona_dianalisis_at'))
            ->orderBy('id_rumah')
            ->pluck('id_rumah');

        if ($ids->isEmpty()) {
            $this->info('Tidak ada rumah yang perlu dianalisis.');
            return 0;
        }

        $this->info("Layer zona: " . config('inarisk.zona_layer') . " — {$ids->count()} rumah.");
        $gagal = 0;

        foreach ($ids as $id) {
            try {
                $h = $inaRisk->perbaruiZonaRumah($id);
                $indeks = $h['indeks'] === null ? 'di luar area bahaya' : number_format($h['indeks'], 3);
                $this->line("  Rumah #$id → {$h['zona']} ($indeks)");
            } catch (\Throwable $e) {
                $gagal++;
                $this->warn("  Rumah #$id gagal: " . $e->getMessage());
            }
        }

        if ($gagal > 0) {
            $this->warn("$gagal rumah gagal (server InaRISK sibuk). Jalankan ulang perintah ini nanti.");
            return 1;
        }

        $this->info('Selesai.');
        return 0;
    }
}
