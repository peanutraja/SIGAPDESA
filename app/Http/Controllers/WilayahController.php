<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WilayahController extends Controller
{
    public function getKabupaten(Request $request)
    {
        $id_provinsi = $request->id_provinsi ?? $request->provinsi_id ?? $request->provinsi;

        $kabupaten = DB::table('kabupaten')
            ->where('id_provinsi', $id_provinsi)
            ->orderBy('nama_kabupaten', 'asc')
            ->get(['id_kabupaten', 'nama_kabupaten']);

        return response()->json($kabupaten);
    }

    public function getKecamatan(Request $request)
    {
        $id_kabupaten = $request->id_kabupaten ?? $request->kabupaten_id ?? $request->kabupaten;

        $kecamatan = DB::table('kecamatan')
            ->where('id_kabupaten', $id_kabupaten)
            ->orderBy('nama_kecamatan', 'asc')
            ->get(['id_kecamatan', 'nama_kecamatan']);

        return response()->json($kecamatan);
    }

    public function getDesa(Request $request)
    {
        $id_kecamatan = $request->id_kecamatan ?? $request->kecamatan_id ?? $request->kecamatan;

        $desa = DB::table('desa')
            ->where('id_kecamatan', $id_kecamatan)
            ->orderBy('nama_desa', 'asc')
            ->get(['id_desa', 'nama_desa']);

        return response()->json($desa);
    }

    public function getRw()
    {
        $rw = DB::table('rw')->orderBy('nomor_rw', 'asc')->get(['id_rw', 'nomor_rw']);
        return response()->json($rw);
    }

    public function getRt()
    {
        $rt = DB::table('rt')->orderBy('nomor_rt', 'asc')->get(['id_rt', 'nomor_rt']);
        return response()->json($rt);
    }
}