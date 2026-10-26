<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PengunjungDashboardController extends Controller
{
    public function index()
    {
        $sessionPengunjung = session('pengunjung');
        
        if (!$sessionPengunjung) {
            return redirect()->route('login')->with('error', 'Sesi telah habis, silakan login kembali.');
        }

        // Kita gunakan murni id_pengunjung sesuai dengan database Anda
        $id_pengunjung = $sessionPengunjung['id_pengunjung'] ?? $sessionPengunjung['id'] ?? null;

        if (!$id_pengunjung) {
            return redirect()->route('login')->with('error', 'ID Pengunjung tidak valid.');
        }

        try {
            // Pencarian data pengunjung (tanpa orWhere 'id' yang bikin error)
            $pengunjung = DB::table('pengunjung')
                ->leftJoin('desa', 'pengunjung.id_desa', '=', 'desa.id_desa')
                ->leftJoin('rt', 'pengunjung.id_rt', '=', 'rt.id_rt')
                ->leftJoin('rw', 'pengunjung.id_rw', '=', 'rw.id_rw')
                ->where('pengunjung.id_pengunjung', $id_pengunjung)
                ->select('pengunjung.*', 'desa.nama_desa', 'rt.nomor_rt', 'rw.nomor_rw')
                ->first();

            if (!$pengunjung) {
                return redirect()->route('login')->with('error', 'Data pengunjung tidak ditemukan di database.');
            }

            return view('dashboard.pengunjung', compact('pengunjung'));
        } catch (\Exception $e) {
            return redirect()->route('login')->with('error', 'Terjadi kesalahan sistem: ' . $e->getMessage());
        }
    }

    public function laporPulang(Request $request)
    {
        $sessionPengunjung = session('pengunjung');
        $id_pengunjung = $sessionPengunjung['id_pengunjung'] ?? $sessionPengunjung['id'] ?? null;

        try {
            DB::table('pengunjung')
                ->where('id_pengunjung', $id_pengunjung)
                ->delete();

            $request->session()->flush();

            return redirect()->route('login')->with('success', 'Laporan kepulangan berhasil dikirim. Data kunjungan Anda telah dihapus secara otomatis. Terima kasih!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal memproses laporan: ' . $e->getMessage());
        }
    }
}