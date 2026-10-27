<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\PetaRumahService;
use App\Services\WilayahAkses;

class EvakuasiController extends Controller
{
    public function index()
    {
        // Data rumah untuk peta (sumber & pembatasan wilayah yang sama dengan peta di dashboard)
        $rumahData = app(PetaRumahService::class)->ambil(WilayahAkses::dariSession());

        $viewName = view()->exists('admin.peta_sebaran') ? 'admin.peta_sebaran' : 'peta_sebaran';
        return view($viewName, compact('rumahData'));
    }

    public function getEvakuasi()
    {
        try {
            $data = DB::table('titik_evakuasi')->get()->map(function($item) {
                return [
                    'id' => $item->id_evakuasi ?? $item->id,
                    'nama' => $item->nama_lokasi,
                    'lat' => (float)$item->latitude,
                    'lng' => (float)$item->longitude,
                    'kapasitas' => (int)($item->kapasitas ?? 0),
                    'keterangan' => $item->keterangan ?? ''
                ];
            });

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'data' => []]);
        }
    }

    public function simpanEvakuasi(Request $request)
    {
        $request->validate([
            'nama_lokasi' => 'required|string|max:255',
            'latitude' => 'required',
            'longitude' => 'required',
        ]);

        try {
            DB::table('titik_evakuasi')->insert([
                'nama_lokasi' => $request->nama_lokasi,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'kapasitas' => $request->kapasitas ?? 0,
                'keterangan' => $request->keterangan ?? '',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json(['success' => true, 'message' => 'Titik evakuasi berhasil disimpan!']);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
