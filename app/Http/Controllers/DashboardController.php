<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\PetaRumahService;
use App\Services\WilayahAkses;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // 1. Ambil Session User Login
        $adminSession = session('admin');
        if (!$adminSession && auth()->check()) {
            $adminSession = auth()->user();
        }

        if (!$adminSession) {
            return redirect()->route('login');
        }

        // 2. Ekstraksi Role & ID Wilayah secara Fleksibel
        if (is_array($adminSession)) {
            $role         = $adminSession['role'] ?? '';
            $id_kabupaten = $adminSession['id_kabupaten'] ?? null;
            $id_kecamatan = $adminSession['id_kecamatan'] ?? null;
            $id_desa      = $adminSession['id_desa'] ?? null;
            $id_rw        = $adminSession['id_rw'] ?? $adminSession['rw'] ?? null;
            $id_rt        = $adminSession['id_rt'] ?? $adminSession['rt'] ?? null;
        } else if (is_object($adminSession)) {
            $role         = $adminSession->role ?? '';
            $id_kabupaten = $adminSession->id_kabupaten ?? null;
            $id_kecamatan = $adminSession->id_kecamatan ?? null;
            $id_desa      = $adminSession->id_desa ?? null;
            $id_rw        = $adminSession->id_rw ?? $adminSession->rw ?? null;
            $id_rt        = $adminSession->id_rt ?? $adminSession->rt ?? null;
        } else {
            $role = '';
            $id_kabupaten = $id_kecamatan = $id_desa = $id_rw = $id_rt = null;
        }

        $admin = $adminSession;
        $akses = WilayahAkses::dariSession();

        // Ambil Data Rumah + Kelompok Rentan Terkunci Wilayah Pejabat
        $rumahData = app(PetaRumahService::class)->ambil($akses);

        // DATA LAPORAN PENDATANG (Difilter sesuai wilayah admin yang login)
        $qLaporan = DB::table('pengunjung as pj')->where('pj.status_laporan', 'Menunggu')->orderBy('pj.created_at', 'desc')->select('pj.*');
        $laporan_pendatang = $akses->batasi($qLaporan, 'pj')->get();

        // ----------------------------------------------------
        // 1. DASHBOARD GMLS / SUPER ADMIN (Seluruh Wilayah)
        // ----------------------------------------------------
        if ($role === 'gmls' || $role === 'admin') {
            // Filter wilayah dari query string (?provinsi=&kabupaten=&kecamatan=)
            $fProvinsi  = $request->query('provinsi');
            $fKabupaten = $request->query('kabupaten');
            $fKecamatan = $request->query('kecamatan');

            $provinsi       = DB::table('provinsi')->orderBy('nama_provinsi', 'asc')->get();
            $kabupatenList  = $fProvinsi ? DB::table('kabupaten')->where('id_provinsi', $fProvinsi)->orderBy('nama_kabupaten', 'asc')->get() : collect();
            $kecamatanList  = $fKabupaten ? DB::table('kecamatan')->where('id_kabupaten', $fKabupaten)->orderBy('nama_kecamatan', 'asc')->get() : collect();

            $qWarga = DB::table('warga')
                ->when($fProvinsi, fn($q) => $q->where('id_provinsi', $fProvinsi))
                ->when($fKabupaten, fn($q) => $q->where('id_kabupaten', $fKabupaten))
                ->when($fKecamatan, fn($q) => $q->where('id_kecamatan', $fKecamatan));

            $total_warga       = (clone $qWarga)->count();
            $total_kk          = DB::table('keluarga as k')
                ->when($fProvinsi || $fKabupaten || $fKecamatan, fn($q) => $q->join('rumah as r', 'k.id_rumah', '=', 'r.id_rumah'))
                ->when($fProvinsi, fn($q) => $q->where('r.id_provinsi', $fProvinsi))
                ->when($fKabupaten, fn($q) => $q->where('r.id_kabupaten', $fKabupaten))
                ->when($fKecamatan, fn($q) => $q->where('r.id_kecamatan', $fKecamatan))
                ->count();
            $jumlah_hamil      = (clone $qWarga)->where('status_kehamilan', 1)->count();
            $total_disabilitas = (clone $qWarga)
                ->whereNotNull('kategori_disabilitas')
                ->where('kategori_disabilitas', '!=', 'Tidak Ada')
                ->count();

            $genderQuery = (clone $qWarga)
                ->select(
                    DB::raw("SUM(CASE WHEN jenis_kelamin = 'L' THEN 1 ELSE 0 END) as laki"),
                    DB::raw("SUM(CASE WHEN jenis_kelamin = 'P' THEN 1 ELSE 0 END) as perempuan")
                )->first();

            $gender_labels = ['Laki-laki', 'Perempuan'];
            $gender_data   = [(int)($genderQuery->laki ?? 0), (int)($genderQuery->perempuan ?? 0)];

            $usiaQuery = (clone $qWarga)
                ->select(
                    DB::raw("SUM(CASE WHEN TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) < 18 THEN 1 ELSE 0 END) as anak"),
                    DB::raw("SUM(CASE WHEN TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) BETWEEN 18 AND 59 THEN 1 ELSE 0 END) as dewasa"),
                    DB::raw("SUM(CASE WHEN TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) >= 60 THEN 1 ELSE 0 END) as lansia")
                )->first();

            $usia_data = [
                'anak'   => (int)($usiaQuery->anak ?? 0),
                'dewasa' => (int)($usiaQuery->dewasa ?? 0),
                'lansia' => (int)($usiaQuery->lansia ?? 0)
            ];

            $rekapKabupaten = DB::table('kabupaten as kb')
                ->leftJoin('rumah as r', 'kb.id_kabupaten', '=', 'r.id_kabupaten')
                ->leftJoin('warga as w', 'r.id_rumah', '=', 'w.id_rumah')
                ->leftJoin('keluarga as k', 'r.id_rumah', '=', 'k.id_rumah')
                ->select(
                    'kb.id_kabupaten',
                    'kb.nama_kabupaten',
                    DB::raw('COUNT(DISTINCT w.id_warga) as total_warga'),
                    DB::raw('COUNT(DISTINCT COALESCE(k.id_kk, k.id_rumah)) as total_kk'),
                    DB::raw('SUM(CASE WHEN w.status_kehamilan = 1 THEN 1 ELSE 0 END) as total_hamil')
                )
                ->groupBy('kb.id_kabupaten', 'kb.nama_kabupaten')
                ->get();

            return view('admin.dashboard', compact(
                'role', 'provinsi', 'kabupatenList', 'kecamatanList', 'total_warga', 'total_kk', 'jumlah_hamil', 'total_disabilitas',
                'gender_labels', 'gender_data', 'usia_data', 'rekapKabupaten', 'adminSession', 'admin', 'rumahData', 'laporan_pendatang'
            ));
        }

        // ----------------------------------------------------
        // 2. DASHBOARD BUPATI
        // ----------------------------------------------------
        if ($role === 'bupati') {
            $infoWilayah = DB::table('kabupaten as kb')
                ->leftJoin('provinsi as p', 'kb.id_provinsi', '=', 'p.id_provinsi')
                ->select('kb.nama_kabupaten', 'p.nama_provinsi')
                ->where('kb.id_kabupaten', $id_kabupaten)
                ->first();

            $rekapWilayah = DB::table('kecamatan as kc')
                ->leftJoin('rumah as r', 'kc.id_kecamatan', '=', 'r.id_kecamatan')
                ->leftJoin('warga as w', 'r.id_rumah', '=', 'w.id_rumah')
                ->leftJoin('keluarga as k', 'r.id_rumah', '=', 'k.id_rumah')
                ->select(
                    'kc.id_kecamatan',
                    'kc.nama_kecamatan',
                    DB::raw('COUNT(DISTINCT w.id_warga) as total_warga'),
                    DB::raw('COUNT(DISTINCT COALESCE(k.id_kk, k.id_rumah)) as total_kk'),
                    DB::raw('SUM(CASE WHEN w.status_kehamilan = 1 THEN 1 ELSE 0 END) as total_hamil'),
                    DB::raw("SUM(CASE WHEN w.kategori_disabilitas IS NOT NULL AND w.kategori_disabilitas != 'Tidak Ada' THEN 1 ELSE 0 END) as total_disabilitas")
                )
                ->where('kc.id_kabupaten', $id_kabupaten)
                ->groupBy('kc.id_kecamatan', 'kc.nama_kecamatan')
                ->get();

            $total_warga       = $rekapWilayah->sum('total_warga');
            $total_kk          = $rekapWilayah->sum('total_kk');
            $total_hamil       = $rekapWilayah->sum('total_hamil');
            $total_disabilitas = $rekapWilayah->sum('total_disabilitas');
            $total_kecamatan   = $rekapWilayah->count();

            return view('dashboard.bupati', compact(
                'role', 'rekapWilayah', 'infoWilayah', 'total_warga', 'total_kk', 'total_hamil', 'total_disabilitas', 'total_kecamatan', 'adminSession', 'admin', 'rumahData', 'laporan_pendatang'
            ));
        }

        // ----------------------------------------------------
        // 3. DASHBOARD CAMAT
        // ----------------------------------------------------
        if ($role === 'camat') {
            $infoWilayah = DB::table('kecamatan as kc')
                ->leftJoin('kabupaten as kb', 'kc.id_kabupaten', '=', 'kb.id_kabupaten')
                ->leftJoin('provinsi as p', 'kb.id_provinsi', '=', 'p.id_provinsi')
                ->select('kc.nama_kecamatan', 'kb.nama_kabupaten', 'p.nama_provinsi')
                ->where('kc.id_kecamatan', $id_kecamatan)
                ->first();

            $rekapWilayah = DB::table('desa as d')
                ->leftJoin('rumah as r', 'd.id_desa', '=', 'r.id_desa')
                ->leftJoin('warga as w', 'r.id_rumah', '=', 'w.id_rumah')
                ->leftJoin('keluarga as k', 'r.id_rumah', '=', 'k.id_rumah')
                ->select(
                    'd.id_desa',
                    'd.nama_desa',
                    DB::raw('COUNT(DISTINCT w.id_warga) as total_warga'),
                    DB::raw('COUNT(DISTINCT COALESCE(k.id_kk, k.id_rumah)) as total_kk'),
                    DB::raw('SUM(CASE WHEN w.status_kehamilan = 1 THEN 1 ELSE 0 END) as total_hamil'),
                    DB::raw("SUM(CASE WHEN w.kategori_disabilitas IS NOT NULL AND w.kategori_disabilitas != 'Tidak Ada' THEN 1 ELSE 0 END) as total_disabilitas")
                )
                ->where('d.id_kecamatan', $id_kecamatan)
                ->groupBy('d.id_desa', 'd.nama_desa')
                ->get();

            $total_warga       = $rekapWilayah->sum('total_warga');
            $total_kk          = $rekapWilayah->sum('total_kk');
            $total_hamil       = $rekapWilayah->sum('total_hamil');
            $total_disabilitas = $rekapWilayah->sum('total_disabilitas');
            $total_desa        = $rekapWilayah->count();

            return view('dashboard.camat', compact(
                'role', 'rekapWilayah', 'infoWilayah', 'total_warga', 'total_kk', 'total_hamil', 'total_disabilitas', 'total_desa', 'adminSession', 'admin', 'rumahData', 'laporan_pendatang'
            ));
        }

        // ----------------------------------------------------
        // 4. DASHBOARD KEPALA DESA / KADES / SEKDES
        // ----------------------------------------------------
        if (in_array($role, ['kepala_desa', 'kades', 'sekdes', 'pemerintah_desa'])) {
            $infoWilayah = DB::table('desa as d')
                ->leftJoin('kecamatan as kc', 'd.id_kecamatan', '=', 'kc.id_kecamatan')
                ->leftJoin('kabupaten as kb', 'kc.id_kabupaten', '=', 'kb.id_kabupaten')
                ->select('d.nama_desa', 'kc.nama_kecamatan', 'kb.nama_kabupaten')
                ->where('d.id_desa', $id_desa)
                ->first();

            $rekapWilayah = DB::table('rw')
                ->leftJoin('rumah as r', 'rw.id_rw', '=', 'r.id_rw')
                ->leftJoin('warga as w', 'r.id_rumah', '=', 'w.id_rumah')
                ->leftJoin('keluarga as k', 'r.id_rumah', '=', 'k.id_rumah')
                ->select(
                    'rw.id_rw',
                    'rw.nomor_rw',
                    DB::raw('COUNT(DISTINCT w.id_warga) as total_warga'),
                    DB::raw('COUNT(DISTINCT COALESCE(k.id_kk, k.id_rumah)) as total_kk'),
                    DB::raw('SUM(CASE WHEN w.status_kehamilan = 1 THEN 1 ELSE 0 END) as total_hamil'),
                    DB::raw("SUM(CASE WHEN w.kategori_disabilitas IS NOT NULL AND w.kategori_disabilitas != 'Tidak Ada' THEN 1 ELSE 0 END) as total_disabilitas")
                )
                ->where('r.id_desa', $id_desa)
                ->groupBy('rw.id_rw', 'rw.nomor_rw')
                ->orderBy('rw.nomor_rw', 'asc')
                ->get();

            $total_warga       = $rekapWilayah->sum('total_warga');
            $total_kk          = $rekapWilayah->sum('total_kk');
            $total_hamil       = $rekapWilayah->sum('total_hamil');
            $total_disabilitas = $rekapWilayah->sum('total_disabilitas');
            $total_rw          = $rekapWilayah->count();

            return view('dashboard.kades', compact(
                'role', 'rekapWilayah', 'infoWilayah', 'total_warga', 'total_kk', 'total_hamil', 'total_disabilitas', 'total_rw', 'adminSession', 'admin', 'rumahData', 'laporan_pendatang'
            ));
        }

        // ----------------------------------------------------
        // 5. DASHBOARD KETUA RW / RW
        // ----------------------------------------------------
        if (in_array($role, ['ketua_rw', 'rw'])) {
            $id_admin_curr = is_array($adminSession) ? ($adminSession['id_admin'] ?? 0) : ($adminSession->id_admin ?? 0);

            $infoWilayah = DB::table('admin as a')
                ->leftJoin('rw', 'a.id_rw', '=', 'rw.id_rw')
                ->leftJoin('desa as d', 'a.id_desa', '=', 'd.id_desa')
                ->leftJoin('kecamatan as kc', 'a.id_kecamatan', '=', 'kc.id_kecamatan')
                ->select('rw.nomor_rw', 'd.nama_desa', 'kc.nama_kecamatan')
                ->where('a.id_admin', $id_admin_curr)
                ->first();

            $rekapWilayah = DB::table('rt')
                ->leftJoin('rumah as r', 'rt.id_rt', '=', 'r.id_rt')
                ->leftJoin('warga as w', 'r.id_rumah', '=', 'w.id_rumah')
                ->leftJoin('keluarga as k', 'r.id_rumah', '=', 'k.id_rumah')
                ->select(
                    'rt.id_rt',
                    'rt.nomor_rt',
                    DB::raw('COUNT(DISTINCT w.id_warga) as total_warga'),
                    DB::raw('COUNT(DISTINCT COALESCE(k.id_kk, k.id_rumah)) as total_kk'),
                    DB::raw('SUM(CASE WHEN w.status_kehamilan = 1 THEN 1 ELSE 0 END) as total_hamil'),
                    DB::raw("SUM(CASE WHEN w.kategori_disabilitas IS NOT NULL AND w.kategori_disabilitas != 'Tidak Ada' THEN 1 ELSE 0 END) as total_disabilitas")
                )
                ->where('r.id_desa', $id_desa)
                ->where('r.id_rw', $id_rw)
                ->groupBy('rt.id_rt', 'rt.nomor_rt')
                ->orderBy('rt.nomor_rt', 'asc')
                ->get();

            $total_warga       = $rekapWilayah->sum('total_warga');
            $total_kk          = $rekapWilayah->sum('total_kk');
            $total_hamil       = $rekapWilayah->sum('total_hamil');
            $total_disabilitas = $rekapWilayah->sum('total_disabilitas');
            $total_rt          = $rekapWilayah->count();

            return view('dashboard.rw', compact(
                'role', 'rekapWilayah', 'infoWilayah', 'total_warga', 'total_kk', 'total_hamil', 'total_disabilitas', 'total_rt', 'adminSession', 'admin', 'rumahData', 'laporan_pendatang'
            ));
        }

        // ----------------------------------------------------
        // 6. DASHBOARD KETUA RT / RT
        // ----------------------------------------------------
        if (in_array($role, ['ketua_rt', 'rt'])) {
            $id_admin_curr = is_array($adminSession) ? ($adminSession['id_admin'] ?? 0) : ($adminSession->id_admin ?? 0);

            $lokasiRT = DB::table('admin as a')
                ->leftJoin('rt', 'a.id_rt', '=', 'rt.id_rt')
                ->leftJoin('rw', 'a.id_rw', '=', 'rw.id_rw')
                ->leftJoin('desa as d', 'a.id_desa', '=', 'd.id_desa')
                ->leftJoin('kecamatan as kc', 'a.id_kecamatan', '=', 'kc.id_kecamatan')
                ->leftJoin('kabupaten as kb', 'a.id_kabupaten', '=', 'kb.id_kabupaten')
                ->leftJoin('provinsi as p', 'kb.id_provinsi', '=', 'p.id_provinsi')
                ->select('rt.nomor_rt', 'rw.nomor_rw', 'd.nama_desa', 'kc.nama_kecamatan', 'kb.nama_kabupaten', 'p.nama_provinsi')
                ->where('a.id_admin', $id_admin_curr)
                ->first();

            // Wilayah RT = desa + RW + RT (nomor RT sama bisa ada di banyak desa)
            $qWargaRT = $akses->batasi(
                DB::table('warga as w')->leftJoin('rumah as r', 'w.id_rumah', '=', 'r.id_rumah'),
                'r'
            );

            $total_warga = (clone $qWargaRT)->count();

            $tetap_count = (clone $qWargaRT)->where(function($q) {
                $q->whereNull('w.status_domisili')->orWhere('w.status_domisili', 'LIKE', '%Tetap%');
            })->count();

            $sementara_count = (clone $qWargaRT)->where(function($q) {
                $q->where('w.status_domisili', 'LIKE', '%Sementara%')
                  ->orWhere('w.status_domisili', 'LIKE', '%Tidak Tetap%');
            })->count();

            $gender_tetap = (clone $qWargaRT)->where(function($q) {
                $q->whereNull('w.status_domisili')->orWhere('w.status_domisili', 'LIKE', '%Tetap%');
            })->select(
                DB::raw("SUM(CASE WHEN w.jenis_kelamin = 'L' THEN 1 ELSE 0 END) as laki"),
                DB::raw("SUM(CASE WHEN w.jenis_kelamin = 'P' THEN 1 ELSE 0 END) as perempuan")
            )->first();

            $gender_sementara = (clone $qWargaRT)->where(function($q) {
                $q->where('w.status_domisili', 'LIKE', '%Sementara%')
                  ->orWhere('w.status_domisili', 'LIKE', '%Tidak Tetap%');
            })->select(
                DB::raw("SUM(CASE WHEN w.jenis_kelamin = 'L' THEN 1 ELSE 0 END) as laki"),
                DB::raw("SUM(CASE WHEN w.jenis_kelamin = 'P' THEN 1 ELSE 0 END) as perempuan")
            )->first();

            $domisili = (clone $qWargaRT)->select(
                DB::raw("SUM(CASE WHEN w.status_domisili LIKE '%Tinggal%' OR w.status_domisili IS NULL THEN 1 ELSE 0 END) as tinggal_di_desa"),
                DB::raw("SUM(CASE WHEN w.status_domisili LIKE '%Tidak Tinggal%' THEN 1 ELSE 0 END) as tidak_tinggal_di_desa")
            )->first();

            $usia_tetap = (clone $qWargaRT)->where(function($q) {
                $q->whereNull('w.status_domisili')->orWhere('w.status_domisili', 'LIKE', '%Tetap%');
            })->select(
                DB::raw("SUM(CASE WHEN TIMESTAMPDIFF(YEAR, w.tanggal_lahir, CURDATE()) < 18 THEN 1 ELSE 0 END) as anak"),
                DB::raw("SUM(CASE WHEN TIMESTAMPDIFF(YEAR, w.tanggal_lahir, CURDATE()) BETWEEN 18 AND 59 THEN 1 ELSE 0 END) as dewasa"),
                DB::raw("SUM(CASE WHEN TIMESTAMPDIFF(YEAR, w.tanggal_lahir, CURDATE()) >= 60 THEN 1 ELSE 0 END) as lansia")
            )->first();

            $usia_sementara = (clone $qWargaRT)->where(function($q) {
                $q->where('w.status_domisili', 'LIKE', '%Sementara%')
                  ->orWhere('w.status_domisili', 'LIKE', '%Tidak Tetap%');
            })->select(
                DB::raw("SUM(CASE WHEN TIMESTAMPDIFF(YEAR, w.tanggal_lahir, CURDATE()) < 18 THEN 1 ELSE 0 END) as anak"),
                DB::raw("SUM(CASE WHEN TIMESTAMPDIFF(YEAR, w.tanggal_lahir, CURDATE()) BETWEEN 18 AND 59 THEN 1 ELSE 0 END) as dewasa"),
                DB::raw("SUM(CASE WHEN TIMESTAMPDIFF(YEAR, w.tanggal_lahir, CURDATE()) >= 60 THEN 1 ELSE 0 END) as lansia")
            )->first();

            $ekonomi = (clone $qWargaRT)->select(
                DB::raw("SUM(CASE WHEN w.jenis_penghasilan = 'Tetap' THEN 1 ELSE 0 END) as tetap"),
                DB::raw("SUM(CASE WHEN w.jenis_penghasilan = 'Tidak Tetap' OR w.jenis_penghasilan IS NULL THEN 1 ELSE 0 END) as tidak_tetap")
            )->first();

            $kerentanan = (clone $qWargaRT)->select(
                DB::raw("SUM(CASE WHEN w.status_kehamilan = 1 THEN 1 ELSE 0 END) as hamil"),
                DB::raw("SUM(CASE WHEN w.kategori_disabilitas IS NOT NULL AND w.kategori_disabilitas != 'Tidak Ada' THEN 1 ELSE 0 END) as disabilitas"),
                DB::raw("SUM(CASE WHEN TIMESTAMPDIFF(YEAR, w.tanggal_lahir, CURDATE()) >= 60 THEN 1 ELSE 0 END) as lansia"),
                DB::raw("SUM(CASE WHEN TIMESTAMPDIFF(YEAR, w.tanggal_lahir, CURDATE()) <= 5 THEN 1 ELSE 0 END) as balita")
            )->first();

            // NOTIFIKASI PENGAJUAN PERUBAHAN DATA DARI WARGA (status pending)
            $pengajuan_pending = $akses->batasi(DB::table('pengajuan_update as p')
                ->join('warga as w', 'p.id_warga', '=', 'w.id_warga')
                ->leftJoin('rumah as r', 'w.id_rumah', '=', 'r.id_rumah')
                ->where('p.status', 'pending'), 'r')
                ->select('p.id_pengajuan', 'p.kategori_perubahan', 'p.alasan_perubahan', 'p.created_at', 'w.nik', 'w.nama_lengkap')
                ->orderBy('p.created_at', 'desc')
                ->get();

            return view('dashboard.rt', compact(
                'role', 'lokasiRT', 'total_warga', 'tetap_count', 'sementara_count',
                'gender_tetap', 'gender_sementara', 'domisili',
                'usia_tetap', 'usia_sementara', 'ekonomi', 'kerentanan', 'adminSession', 'admin', 'rumahData', 'laporan_pendatang',
                'pengajuan_pending'
            ));
        }

        return redirect()->route('login');
    }
}
