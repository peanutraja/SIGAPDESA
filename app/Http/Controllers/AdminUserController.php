<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\WilayahAkses;

class AdminUserController extends Controller
{
    // Role yang tersedia di kolom admin.role (enum)
    private const ROLE_VALID = ['gmls', 'bpbd', 'bupati', 'camat', 'pemerintah_desa', 'kepala_desa', 'ketua_rw', 'ketua_rt'];

    // Role yang boleh dibuat oleh akun desa yang diberi hak can_create_akun (hanya di desanya sendiri)
    private const ROLE_BAWAHAN_DESA = ['kepala_desa', 'ketua_rw', 'ketua_rt'];

    private function isGmls(array $userSession): bool
    {
        return in_array(strtolower($userSession['role'] ?? ''), ['gmls', 'admin'], true);
    }

    private function bolehKelolaAkun(array $userSession): bool
    {
        return $this->isGmls($userSession) || (int)($userSession['can_create_akun'] ?? 0) === 1;
    }

    public function index()
    {
        $userSession = (array) session('admin', []);
        $userRole = strtolower($userSession['role'] ?? '');
        $userDesa = $userSession['id_desa'] ?? null;

        // KONTROL AKSES: Hanya GMLS/Admin atau Pejabat Desa yang diberi hak akses can_create_akun == 1
        if (!$this->bolehKelolaAkun($userSession)) {
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

        // Selain GMLS: HANYA tampilkan akun di desanya sendiri
        if (!$this->isGmls($userSession)) {
            $query->where('a.id_desa', $userDesa ?: 0);
        }

        $users = $query->orderBy('a.id_admin', 'desc')->get();
        $namaDesaSaya = $userDesa ? DB::table('desa')->where('id_desa', $userDesa)->value('nama_desa') : null;

        return view('admin.kelola_user', compact('users', 'provinsi', 'userRole', 'namaDesaSaya'));
    }

    public function store(Request $request)
    {
        $userSession = (array) session('admin', []);
        if (!$this->bolehKelolaAkun($userSession)) {
            return redirect()->route('dashboard')->with('error', 'Anda tidak memiliki hak akses untuk mengelola akun!');
        }
        $isGmls = $this->isGmls($userSession);

        // 1. Tangkap Validasi secara Manual agar errornya bisa dikirim ke View
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'nama_pengguna' => 'required|string|max:255',
            'username'      => 'required|string|max:50|unique:admin,username',
            'password'      => 'required|string|min:6',
            'role'          => 'required|in:' . implode(',', $isGmls ? self::ROLE_VALID : self::ROLE_BAWAHAN_DESA),
        ]);

        if ($validator->fails()) {
            // Jika validasi gagal, kembalikan user dengan pesan alert merah
            return redirect()->back()->withInput()->with('error', 'Validasi Gagal: ' . $validator->errors()->first());
        }

        $role = $request->role;

        // 2. Tentukan wilayah akun. Akun desa hanya bisa membuat akun di desanya sendiri.
        if ($isGmls) {
            $wilayah = [
                'id_kabupaten' => $request->id_kabupaten ?: null,
                'id_kecamatan' => $request->id_kecamatan ?: null,
                'id_desa'      => $request->id_desa ?: null,
                'id_rw'        => $request->id_rw ?: null,
                'id_rt'        => $request->id_rt ?: null,
            ];
        } else {
            $wilayah = [
                'id_kabupaten' => $userSession['id_kabupaten'] ?? null,
                'id_kecamatan' => $userSession['id_kecamatan'] ?? null,
                'id_desa'      => $userSession['id_desa'] ?? null,
                'id_rw'        => $request->id_rw ?: null,
                'id_rt'        => $request->id_rt ?: null,
            ];
        }

        // Kosongkan tingkat wilayah yang tidak dipakai role ini (mis. Camat tidak punya desa/RW/RT)
        $urutan = ['id_kabupaten', 'id_kecamatan', 'id_desa', 'id_rw', 'id_rt'];
        $wajib = WilayahAkses::kolomWajib($role);
        $terdalam = $wajib ? max(array_map(fn($k) => array_search($k, $urutan), $wajib)) : -1;
        foreach ($urutan as $i => $kolom) {
            if ($i > $terdalam) {
                $wilayah[$kolom] = null;
            }
        }

        if ($pesan = $this->cekWilayahAkun($role, $wilayah)) {
            return redirect()->back()->withInput()->with('error', $pesan);
        }

        try {
            DB::table('admin')->insert(array_merge([
                'nama_pengguna'   => $request->nama_pengguna,
                'nama_lengkap'    => $request->nama_pengguna,
                'username'        => $request->username,
                'password'        => md5($request->password),
                'role'            => $role,
                'status_aktif'    => 1,
                // Hanya GMLS yang dapat memberi hak membuat akun, dan hanya untuk Pemerintah Desa
                'can_create_akun' => ($isGmls && $role === 'pemerintah_desa' && $request->has('can_create_akun')) ? 1 : 0,
                'created_at'      => now(),
                'updated_at'      => now(),
            ], $wilayah));

            return redirect()->back()->with('success', 'Akun admin wilayah berhasil diterbitkan!');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Gagal membuat akun: ' . $e->getMessage());
        }
    }

    /**
     * Pastikan wilayah akun lengkap & saling cocok (kecamatan di kabupaten itu, desa di kecamatan itu),
     * supaya akun hanya melihat data wilayahnya. Mengembalikan pesan error atau null.
     */
    private function cekWilayahAkun(string $role, array $wilayah): ?string
    {
        $label = [
            'id_kabupaten' => 'Kabupaten', 'id_kecamatan' => 'Kecamatan', 'id_desa' => 'Desa',
            'id_rw' => 'RW', 'id_rt' => 'RT',
        ];

        // Untuk akun di bawah kabupaten, seluruh jenjang di atasnya juga wajib diisi
        $urutan = array_keys($label);
        $wajib = WilayahAkses::kolomWajib($role) ?? [];
        $terdalam = $wajib ? max(array_map(fn($k) => array_search($k, $urutan), $wajib)) : -1;
        for ($i = 0; $i <= $terdalam; $i++) {
            if (empty($wilayah[$urutan[$i]])) {
                return "Wilayah {$label[$urutan[$i]]} wajib dipilih untuk akun ini.";
            }
        }

        if (!empty($wilayah['id_kecamatan']) && !DB::table('kecamatan')->where('id_kecamatan', $wilayah['id_kecamatan'])->where('id_kabupaten', $wilayah['id_kabupaten'])->exists()) {
            return 'Kecamatan yang dipilih tidak berada di kabupaten tersebut.';
        }
        if (!empty($wilayah['id_desa']) && !DB::table('desa')->where('id_desa', $wilayah['id_desa'])->where('id_kecamatan', $wilayah['id_kecamatan'])->exists()) {
            return 'Desa yang dipilih tidak berada di kecamatan tersebut.';
        }
        if (!empty($wilayah['id_rw']) && !DB::table('rw')->where('id_rw', $wilayah['id_rw'])->exists()) {
            return 'RW yang dipilih tidak valid.';
        }
        if (!empty($wilayah['id_rt']) && !DB::table('rt')->where('id_rt', $wilayah['id_rt'])->exists()) {
            return 'RT yang dipilih tidak valid.';
        }

        // Satu RT di satu desa cukup satu akun Ketua RT (mencegah dua akun berebut wilayah)
        if ($role === 'ketua_rt' && DB::table('admin')->where('role', 'ketua_rt')
                ->where('id_desa', $wilayah['id_desa'])->where('id_rw', $wilayah['id_rw'])->where('id_rt', $wilayah['id_rt'])->exists()) {
            return 'Akun Ketua RT untuk RT/RW di desa ini sudah ada.';
        }

        return null;
    }

    public function destroy($id)
    {
        $userSession = (array) session('admin', []);
        if (!$this->bolehKelolaAkun($userSession)) {
            return redirect()->route('dashboard')->with('error', 'Anda tidak memiliki hak akses untuk mengelola akun!');
        }

        $target = DB::table('admin')->where('id_admin', $id)->first();
        if (!$target) {
            return redirect()->back()->with('error', 'Akun admin tidak ditemukan.');
        }
        if ((int) $target->id_admin === (int) ($userSession['id_admin'] ?? 0)) {
            return redirect()->back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }
        // Akun desa hanya boleh menghapus akun bawahan di desanya sendiri
        if (!$this->isGmls($userSession)
            && (!in_array($target->role, self::ROLE_BAWAHAN_DESA, true) || (int) $target->id_desa !== (int) ($userSession['id_desa'] ?? 0))) {
            return redirect()->back()->with('error', 'Anda hanya dapat menghapus akun bawahan di desa Anda sendiri.');
        }

        try {
            DB::table('admin')->where('id_admin', $id)->delete();
            return redirect()->back()->with('success', 'Akun admin berhasil dihapus!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus akun: ' . $e->getMessage());
        }
    }
}
