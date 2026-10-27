<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $tipe = $request->input('tipe');
        $username = $request->input('username');
        $password = $request->input('password');

        if ($tipe == 'admin') {
            $admin = DB::table('admin')
                ->where('username', $username)
                ->where('password', md5($password))
                ->first();

            if ($admin && !(int) $admin->status_aktif) {
                return redirect()->route('login')->with('error', 'Akun Anda sedang dinonaktifkan. Hubungi GMLS.');
            }

            if ($admin) {
                $request->session()->regenerate();
                session([
                    'is_login'  => true,
                    'username'  => $admin->username,
                    'user_role' => 'admin',
                    'admin'     => (array) $admin
                ]);

                return redirect()->route('dashboard');
            }

            return redirect()->route('login')->with('error', 'Username atau password admin salah!');

        } elseif ($tipe == 'warga') {
            if (strlen($password) != 8) {
                return redirect()->route('login')->with('error', 'Format tanggal salah! Gunakan format ddmmyyyy');
            }

            $tgl_db = substr($password, 4, 4) . '-' . substr($password, 2, 2) . '-' . substr($password, 0, 2);

            // Cek ke tabel Warga
            $warga = DB::table('warga')
                ->where('nik', $username)
                ->where('tanggal_lahir', $tgl_db)
                ->first();

            if ($warga) {
                $request->session()->regenerate();
                session([
                    'is_login'  => true,
                    'username'  => $warga->nik,
                    'user_role' => 'warga',
                    'warga'     => (array) $warga
                ]);

                return redirect()->route('warga.dashboard');
            }

            // Jika bukan Warga, Cek ke tabel Pengunjung
            $pengunjung = DB::table('pengunjung')
                ->where('nik', $username)
                ->where('password', md5($password))
                ->first();

            if ($pengunjung) {
                if ($pengunjung->status_laporan === 'Menunggu') {
                    return redirect()->route('login')->with('error', 'Akun pendatang Anda sedang menunggu konfirmasi RT/RW.');
                } elseif ($pengunjung->status_laporan === 'Ditolak') {
                    return redirect()->route('login')->with('error', 'Laporan pendatang Anda ditolak oleh RT/RW.');
                }

                // Jika sudah 'Diketahui' (diterima)
                $request->session()->regenerate();
                session([
                    'is_login'  => true,
                    'username'  => $pengunjung->nik,
                    'user_role' => 'pengunjung',
                    'pengunjung'=> (array) $pengunjung
                ]);

                // KODE YANG DIUBAH: Diarahkan ke dashboard pengunjung (agar tidak terjadi loop)
                return redirect()->route('pengunjung.dashboard')->with('success', 'Selamat datang, pengunjung!');
            }

            return redirect()->route('login')->with('error', 'NIK atau tanggal lahir salah!');
        }

        return redirect()->route('login')->with('error', 'Pilih tipe akun terlebih dahulu!');
    }

    public function logout(Request $request)
    {
        $request->session()->flush();
        return redirect()->route('login');
    }
}