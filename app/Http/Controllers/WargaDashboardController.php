<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WargaDashboardController extends Controller
{
    public function index()
    {
        $wargaSession = session('warga');
        if (!$wargaSession || !isset($wargaSession['id_warga'])) {
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu.');
        }

        $id_warga = $wargaSession['id_warga'];
        $warga = DB::table('warga')->where('id_warga', $id_warga)->first();
        $kabupaten = DB::table('kabupaten')->orderBy('nama_kabupaten', 'asc')->get();

        $riwayatPengajuan = DB::table('pengajuan_update')
            ->where('id_warga', $id_warga)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('warga.dashboard', compact('warga', 'kabupaten', 'riwayatPengajuan'));
    }

    public function storePengajuan(Request $request)
    {
        $wargaSession = session('warga');
        if (!$wargaSession || !isset($wargaSession['id_warga'])) {
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu.');
        }

        $id_warga = $wargaSession['id_warga'];
        $wargaLama = DB::table('warga')->where('id_warga', $id_warga)->first();

        if (!$wargaLama) {
            return redirect()->back()->with('error', 'Data warga tidak ditemukan.');
        }

        $request->validate([
            'nama_lengkap' => 'required|string|max:255',
            'tempat_lahir' => 'required|string|max:255',
            'tanggal_lahir' => 'required|date|before_or_equal:today',
            'jenis_kelamin' => 'required|in:L,P',
            'golongan_darah' => 'required|string',
            'agama' => 'nullable|string',
            'status_perkawinan' => 'nullable|string',
            'nomor_telepon' => 'nullable|string|max:20',
            'pekerjaan' => 'required|string|max:255',
            'status_domisili' => 'required|string',
            'alasan_perubahan' => 'required|string|max:500',
        ]);

        // Rekap Data Lama
        $dataLama = [
            'nama_lengkap' => $wargaLama->nama_lengkap,
            'tempat_lahir' => $wargaLama->tempat_lahir,
            'tanggal_lahir' => $wargaLama->tanggal_lahir,
            'jenis_kelamin' => $wargaLama->jenis_kelamin,
            'golongan_darah' => $wargaLama->golongan_darah,
            'agama' => $wargaLama->agama,
            'status_perkawinan' => $wargaLama->status_perkawinan,
            'nomor_telepon' => $wargaLama->nomor_telepon,
            'pekerjaan' => $wargaLama->pekerjaan,
            'status_domisili' => $wargaLama->status_domisili,
            'status_kehamilan' => (int) $wargaLama->status_kehamilan,
            'perkiraan_lahir' => $wargaLama->perkiraan_lahir,
            'kategori_disabilitas' => $wargaLama->kategori_disabilitas ?? 'Tidak Ada',
            'keterangan_disabilitas' => $wargaLama->keterangan_disabilitas,
        ];

        // Validasi khusus Kelompok Rentan
        $statusKehamilan = ($request->jenis_kelamin === 'P' && ($request->has('status_kehamilan') || $request->status_kehamilan == 1)) ? 1 : 0;
        $perkiraanLahir = ($statusKehamilan === 1) ? $request->perkiraan_lahir : null;
        $kategoriDisabilitas = $request->kategori_disabilitas ?: 'Tidak Ada';
        $keteranganDisabilitas = ($kategoriDisabilitas !== 'Tidak Ada') ? $request->keterangan_disabilitas : null;

        // Rekap Data Usulan Baru
        $dataBaru = [
            'nama_lengkap' => $request->nama_lengkap,
            'tempat_lahir' => $request->tempat_lahir,
            'tanggal_lahir' => $request->tanggal_lahir,
            'jenis_kelamin' => $request->jenis_kelamin,
            'golongan_darah' => $request->golongan_darah,
            'agama' => $request->agama,
            'status_perkawinan' => $request->status_perkawinan,
            'nomor_telepon' => $request->nomor_telepon,
            'pekerjaan' => $request->pekerjaan,
            'status_domisili' => $request->status_domisili,
            'status_kehamilan' => $statusKehamilan,
            'perkiraan_lahir' => $perkiraanLahir,
            'kategori_disabilitas' => $kategoriDisabilitas,
            'keterangan_disabilitas' => $keteranganDisabilitas,
        ];

        DB::table('pengajuan_update')->insert([
            'id_warga' => $id_warga,
            'kategori_perubahan' => 'Pembaruan Data Pribadi & Kesehatan',
            'data_lama' => json_encode($dataLama),
            'data_baru' => json_encode($dataBaru),
            'alasan_perubahan' => $request->alasan_perubahan,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('warga.dashboard')->with('success', 'Permohonan perubahan data berhasil dikirim! Menunggu verifikasi dari Ketua RT / Admin.');
    }
}