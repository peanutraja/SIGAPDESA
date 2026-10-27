<?php

namespace App\Http\Controllers;

use App\Services\InaRiskService;
use Illuminate\Http\Request;

class InaRiskController extends Controller
{
    /**
     * Tingkat bahaya satu layer di titik yang diklik pada peta.
     * GET /inarisk/hazard-at?id=banjir&lat=-6.37&lng=105.83
     */
    public function hazardAt(Request $request, InaRiskService $inaRisk)
    {
        $request->validate([
            'id'  => 'required|in:' . implode(',', array_keys(config('inarisk.layers'))),
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
        ]);

        set_time_limit(180);

        try {
            $indeks = $inaRisk->indeksDi($request->id, (float) $request->lat, (float) $request->lng);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Server InaRISK BNPB sedang sibuk. Coba lagi beberapa saat.'], 503);
        }

        return response()->json([
            'id'     => $request->id,
            'label'  => config("inarisk.layers.{$request->id}.label"),
            'indeks' => $indeks,
            'level'  => $inaRisk->levelDari($indeks),
        ]);
    }

    /**
     * Hitung ulang zona satu rumah dari InaRISK (dipanggil otomatis oleh
     * halaman peta untuk rumah yang belum dianalisis).
     * POST /inarisk/zona/{id}
     */
    public function analisisZona($id, InaRiskService $inaRisk)
    {
        set_time_limit(180);

        $akses = \App\Services\WilayahAkses::dariSession();
        $rumah = \Illuminate\Support\Facades\DB::table('rumah')->where('id_rumah', (int) $id)->first();
        if (!$rumah || (!$akses->isGlobal() && !$akses->mencakup($rumah))) {
            return response()->json(['message' => 'Rumah tidak ditemukan atau di luar wilayah Anda.'], 404);
        }

        try {
            return response()->json($inaRisk->perbaruiZonaRumah((int) $id));
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 503);
        }
    }
}
