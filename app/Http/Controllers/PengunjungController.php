<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PengunjungController extends Controller
{
    public function showRegister()
    {
        // Ambil data provinsi untuk dropdown awal
        $provinsi = DB::table('provinsi')->orderBy('nama_provinsi', 'asc')->get();
        return view('auth.register_pengunjung', compact('provinsi'));
    }

    public function register(Request $request)
    {
        // Validasi input form pendaftaran
        $request->validate([
            'nik' => 'required|string|size:16|unique:pengunjung,nik',
            'nama_lengkap' => 'required|string|max:255',
            'nomor_telepon' => 'required|string|max:20',
            'tanggal_lahir' => 'required|date',
            'asal_daerah' => 'required|string|max:255',
            'tujuan_kunjungan' => 'required|string',
            'lama_kunjungan' => 'required|integer|min:1',
            'id_provinsi' => 'required',
            'id_kabupaten' => 'required',
            'id_kecamatan' => 'required',
            'id_desa' => 'required',
            'id_rw' => 'required',
            'id_rt' => 'required',
        ]);

        try {
            // Format tanggal_lahir (YYYY-MM-DD) menjadi ddmmyyyy untuk dijadikan password default
            $password_ddmmyyyy = date('dmY', strtotime($request->tanggal_lahir));

            DB::table('pengunjung')->insert([
                'nik' => $request->nik,
                'nama_lengkap' => $request->nama_lengkap,
                'nomor_telepon' => $request->nomor_telepon,
                'password' => md5($password_ddmmyyyy), // Gunakan tgl lahir sbg password
                'asal_daerah' => $request->asal_daerah,
                'tujuan_kunjungan' => $request->tujuan_kunjungan,
                'lama_kunjungan' => $request->lama_kunjungan,
                'id_provinsi' => $request->id_provinsi,
                'id_kabupaten' => $request->id_kabupaten,
                'id_kecamatan' => $request->id_kecamatan,
                'id_desa' => $request->id_desa,
                'id_rw' => $request->id_rw,
                'id_rt' => $request->id_rt,
                'status_laporan' => 'Menunggu',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Gunakan redirect()->back() agar tetap di halaman register
            return redirect()->back()->with('success', 'Laporan pendatang berhasil dikirim! Silakan menunggu konfirmasi dari RT/RW setempat.');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Gagal mendaftar: ' . $e->getMessage());
        }
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:Diketahui,Ditolak'
        ]);

        DB::table('pengunjung')->where('id_pengunjung', $id)->update([
            'status_laporan' => $request->status,
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Status laporan pendatang berhasil diperbarui!');
    }

    public function destroy($id)
    {
        try {
            // Cek apakah data ada
            $pengunjung = \Illuminate\Support\Facades\DB::table('pengunjung')->where('id_pengunjung', $id)->first();
            
            if (!$pengunjung) {
                return redirect()->back()->with('error', 'Data pengunjung tidak ditemukan.');
            }

            // Hapus data pengunjung
            \Illuminate\Support\Facades\DB::table('pengunjung')->where('id_pengunjung', $id)->delete();

            return redirect()->back()->with('success', 'Data pengunjung / pendatang berhasil dihapus!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus data pengunjung: ' . $e->getMessage());
        }
    }
}