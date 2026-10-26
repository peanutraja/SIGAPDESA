<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminUserController extends Controller
{
    public function index()
    {
        $userSession = session('admin');
        $userRole = strtolower($userSession['role'] ?? 'gmls');
        $userDesa = $userSession['id_desa'] ?? null;
        $canCreateAkun = (int)($userSession['can_create_akun'] ?? 0);

        // KONTROL AKSES: Hanya GMLS/Admin atau Pejabat Desa yang diberi hak akses can_create_akun == 1
        if (!in_array($userRole, ['gmls', 'admin']) && $canCreateAkun !== 1) {
            return redirect()->route('dashboard')->with('error', 'Anda tidak memiliki hak akses untuk mengelola akun!');
        }

        $provinsi = DB::table('provinsi')->orderBy('nama_provinsi', 'asc')->get();

        $query = DB::table('admin as a')
            ->leftJoin('kabupaten as kb', 'a.id_kabupaten', '=', 'kb.id_kabupaten')
            ->leftJoin('kecamatan as kc', 'a.id_kecamatan', '=', 'kc.id_kecamatan')
            ->leftJoin('desa as d', 'a.id_desa', '=', 'd.id_desa')
            ->leftJoin('rw', 'a.id_rw', '=', 'rw.id_rw')
            ->leftJoin('rt', 'a.id_rt', '=', 'rt.id_rt')
            ->select(
                'a.*', 
                'kb.nama_kabupaten', 
                'kc.nama_kecamatan', 
                'd.nama_desa', 
                'rw.nomor_rw', 
                'rt.nomor_rt'
            );

        // Filter otomatis: Jika yang login Pemerintah Desa, HANYA tampilkan user di desanya
        if (in_array($userRole, ['pemerintah_desa', 'kepala_desa', 'kades', 'sekdes'])) {
            $query->where('a.id_desa', $userDesa);
        }

        $users = $query->orderBy('a.id_admin', 'desc')->get();

        return view('admin.kelola_user', compact('users', 'provinsi', 'userRole'));
    }

    public function store(Request $request)
    {
        // Ambil data session
        $userSession = session('admin');
        $userRole = strtolower($userSession['role'] ?? 'gmls');

        // 1. Tangkap Validasi secara Manual agar errornya bisa dikirim ke View
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'nama_pengguna' => 'required|string|max:255',
            'username'      => 'required|string|max:100|unique:admin,username',
            'password'      => 'required|string|min:6',
            'role'          => 'required|in:gmls,bupati,camat,pemerintah_desa,kepala_desa,kades,sekdes,ketua_rw,rw,ketua_rt,rt',
        ]);

        if ($validator->fails()) {
            // Jika validasi gagal, kembalikan user dengan pesan alert merah
            return redirect()->back()->withInput()->with('error', 'Validasi Gagal: ' . $validator->errors()->first());
        }

        try {
            // Otomatisasi wilayah jika yang membuat adalah Pemerintah Desa
            $idKabupaten = $userRole === 'pemerintah_desa' ? ($userSession['id_kabupaten'] ?? null) : $request->id_kabupaten;
            $idKecamatan = $userRole === 'pemerintah_desa' ? ($userSession['id_kecamatan'] ?? null) : $request->id_kecamatan;
            $idDesa      = $userRole === 'pemerintah_desa' ? ($userSession['id_desa'] ?? null) : $request->id_desa;

            DB::table('admin')->insert([
                'nama_pengguna'   => $request->nama_pengguna,
                'nama_lengkap'    => $request->nama_pengguna,
                'username'        => $request->username,
                'password'        => md5($request->password), 
                'role'            => $request->role,
                
                // 2. PASTIKAN INPUT KOSONG MENJADI NULL (Agar tidak terjadi error tipe data integer di MySQL)
                'id_kabupaten'    => empty($idKabupaten) ? null : $idKabupaten,
                'id_kecamatan'    => empty($idKecamatan) ? null : $idKecamatan,
                'id_desa'         => empty($idDesa) ? null : $idDesa,
                'id_rw'           => empty($request->id_rw) ? null : $request->id_rw,
                'id_rt'           => empty($request->id_rt) ? null : $request->id_rt,
                
                'status_aktif'    => 1,
                'can_create_akun' => $request->has('can_create_akun') ? 1 : 0, 
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

            return redirect()->back()->with('success', 'Akun admin wilayah berhasil diterbitkan!');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Gagal membuat akun: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $deleted = DB::table('admin')
                ->where('id_admin', $id)
                ->orWhere('id_user', $id)
                ->orWhere('id', $id)
                ->delete();

            if ($deleted) {
                return redirect()->back()->with('success', 'Akun admin berhasil dihapus!');
            }

            return redirect()->back()->with('error', 'Akun admin tidak ditemukan.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus akun: ' . $e->getMessage());
        }
    }
}