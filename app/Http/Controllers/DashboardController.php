<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            $role         = $adminSession['role'] ?? 'gmls';
            $id_kabupaten = $adminSession['id_kabupaten'] ?? null;
            $id_kecamatan = $adminSession['id_kecamatan'] ?? null;
            $id_desa      = $adminSession['id_desa'] ?? null;
            $id_rw        = $adminSession['id_rw'] ?? $adminSession['rw'] ?? null;
            $id_rt        = $adminSession['id_rt'] ?? $adminSession['rt'] ?? null;
        } else if (is_object($adminSession)) {
            $role         = $adminSession->role ?? 'gmls';
            $id_kabupaten = $adminSession->id_kabupaten ?? null;
            $id_kecamatan = $adminSession->id_kecamatan ?? null;
            $id_desa      = $adminSession->id_desa ?? null;
            $id_rw        = $adminSession->id_rw ?? $adminSession->rw ?? null;
            $id_rt        = $adminSession->id_rt ?? $adminSession->rt ?? null;
        } else {
            $role = 'gmls';
            $id_kabupaten = $id_kecamatan = $id_desa = $id_rw = $id_rt = null;
        }

        $admin = $adminSession;

        // Ambil Data Rumah + Kelompok Rentan Terkunci Wilayah Pejabat
        $rumahData = $this->getRumahDataMap($role, $id_kabupaten, $id_kecamatan, $id_desa, $id_rw, $id_rt);

        // DATA LAPORAN PENDATANG (Difilter sesuai wilayah admin yang login)
        $qLaporan = DB::table('pengunjung')->where('status_laporan', 'Menunggu')->orderBy('created_at', 'desc');
        if ($role === 'camat') {
            $qLaporan->where('id_kecamatan', $id_kecamatan);
        } elseif (in_array($role, ['kepala_desa', 'kades', 'sekdes', 'pemerintah_desa'])) {
            $qLaporan->where('id_desa', $id_desa);
        } elseif (in_array($role, ['ketua_rw', 'rw'])) {
            $qLaporan->where('id_desa', $id_desa)->where('id_rw', $id_rw);
        } elseif (in_array($role, ['ketua_rt', 'rt'])) {
            $qLaporan->where('id_desa', $id_desa)->where('id_rw', $id_rw)->where('id_rt', $id_rt);
        } elseif ($role === 'bupati') {
            $qLaporan->where('id_kabupaten', $id_kabupaten);
        }
        $laporan_pendatang = $qLaporan->get();

        // ----------------------------------------------------
        // 1. DASHBOARD GMLS / SUPER ADMIN (Seluruh Wilayah)
        // ----------------------------------------------------
        if ($role === 'gmls' || $role === 'admin') {
            $provinsi          = DB::table('provinsi')->orderBy('nama_provinsi', 'asc')->get();
            $total_warga       = DB::table('warga')->count();
            $total_kk          = DB::table('keluarga')->count();
            $jumlah_hamil      = DB::table('warga')->where('status_kehamilan', 1)->count();
            $total_disabilitas = DB::table('warga')
                ->whereNotNull('kategori_disabilitas')
                ->where('kategori_disabilitas', '!=', 'Tidak Ada')
                ->count();

            $genderQuery = DB::table('warga')
                ->select(
                    DB::raw("SUM(CASE WHEN jenis_kelamin = 'L' THEN 1 ELSE 0 END) as laki"),
                    DB::raw("SUM(CASE WHEN jenis_kelamin = 'P' THEN 1 ELSE 0 END) as perempuan")
                )->first();

            $gender_labels = ['Laki-laki', 'Perempuan'];
            $gender_data   = [(int)($genderQuery->laki ?? 0), (int)($genderQuery->perempuan ?? 0)];

            $usiaQuery = DB::table('warga')
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
                'role', 'provinsi', 'total_warga', 'total_kk', 'jumlah_hamil', 'total_disabilitas', 
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

            $qWargaRT = DB::table('warga as w')
                ->leftJoin('rumah as r', 'w.id_rumah', '=', 'r.id_rumah')
                ->when(!empty($id_rt), function($q) use ($id_rt) {
                    return $q->where('r.id_rt', $id_rt);
                })
                ->when(empty($id_rt) && !empty($id_desa), function($q) use ($id_desa) {
                    return $q->where('w.id_desa', $id_desa);
                });

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

            return view('dashboard.rt', compact(
                'role', 'lokasiRT', 'total_warga', 'tetap_count', 'sementara_count',
                'gender_tetap', 'gender_sementara', 'domisili',
                'usia_tetap', 'usia_sementara', 'ekonomi', 'kerentanan', 'adminSession', 'admin', 'rumahData', 'laporan_pendatang'
            ));
        }

        return redirect()->route('login');
    }

    private function getRumahDataMap($role, $id_kabupaten = null, $id_kecamatan = null, $id_desa = null, $id_rw = null, $id_rt = null)
    {
        $whereMap = [
            "r.latitude IS NOT NULL",
            "r.longitude IS NOT NULL",
            "TRIM(r.latitude) <> ''",
            "TRIM(r.longitude) <> ''"
        ];

        if ($role === 'bupati' && !empty($id_kabupaten)) {
            $whereMap[] = "r.id_kabupaten = " . (int)$id_kabupaten;
        } elseif ($role === 'camat' && !empty($id_kecamatan)) {
            $whereMap[] = "r.id_kecamatan = " . (int)$id_kecamatan;
        } elseif (in_array($role, ['kepala_desa', 'kades', 'sekdes']) && !empty($id_desa)) {
            $whereMap[] = "r.id_desa = " . (int)$id_desa;
        } elseif (in_array($role, ['ketua_rw', 'rw']) && !empty($id_rw)) {
            $whereMap[] = "r.id_rw = " . (int)$id_rw;
            if (!empty($id_desa)) {
                $whereMap[] = "r.id_desa = " . (int)$id_desa;
            }
        } elseif (in_array($role, ['ketua_rt', 'rt']) && !empty($id_rt)) {
            $whereMap[] = "r.id_rt = " . (int)$id_rt;
        }

        $whereSql = implode(" AND ", $whereMap);

        try {
            $sql = "
                SELECT
                    r.id_rumah,
                    r.alamat_lengkap,
                    r.latitude,
                    r.longitude,
                    COALESCE(r.status_zona_tsunami, 'Hijau') AS status_zona,
                    COALESCE(rt.nomor_rt, '') AS nomor_rt,
                    COALESCE(rw.nomor_rw, '') AS nomor_rw,
                    COALESCE(d.nama_desa, '') AS nama_desa,
                    COUNT(DISTINCT w.id_warga) AS jumlah_warga,
                    MIN(w.nama_lengkap) AS nama_warga,
                    
                    COUNT(DISTINCT CASE
                        WHEN w.id_warga IS NOT NULL 
                          AND w.tanggal_lahir IS NOT NULL 
                          AND TRIM(CAST(w.tanggal_lahir AS CHAR)) <> ''
                          AND TRIM(CAST(w.tanggal_lahir AS CHAR)) <> '0000-00-00'
                          AND (
                            CASE 
                              WHEN CAST(w.tanggal_lahir AS CHAR) REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2}' THEN TIMESTAMPDIFF(YEAR, STR_TO_DATE(w.tanggal_lahir, '%Y-%m-%d'), CURDATE())
                              WHEN CAST(w.tanggal_lahir AS CHAR) REGEXP '^[0-9]{2}-[0-9]{2}-[0-9]{4}' THEN TIMESTAMPDIFF(YEAR, STR_TO_DATE(w.tanggal_lahir, '%d-%m-%Y'), CURDATE())
                              WHEN CAST(w.tanggal_lahir AS CHAR) REGEXP '^[0-9]{2}/[0-9]{2}/[0-9]{4}' THEN TIMESTAMPDIFF(YEAR, STR_TO_DATE(w.tanggal_lahir, '%d/%m/%Y'), CURDATE())
                              ELSE TIMESTAMPDIFF(YEAR, w.tanggal_lahir, CURDATE())
                            END
                          ) >= 60
                        THEN w.id_warga
                    END) AS jumlah_lansia,

                    COUNT(DISTINCT CASE
                        WHEN w.id_warga IS NOT NULL 
                          AND w.tanggal_lahir IS NOT NULL 
                          AND TRIM(CAST(w.tanggal_lahir AS CHAR)) <> ''
                          AND TRIM(CAST(w.tanggal_lahir AS CHAR)) <> '0000-00-00'
                          AND (
                            CASE 
                              WHEN CAST(w.tanggal_lahir AS CHAR) REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2}' THEN TIMESTAMPDIFF(YEAR, STR_TO_DATE(w.tanggal_lahir, '%Y-%m-%d'), CURDATE())
                              WHEN CAST(w.tanggal_lahir AS CHAR) REGEXP '^[0-9]{2}-[0-9]{2}-[0-9]{4}' THEN TIMESTAMPDIFF(YEAR, STR_TO_DATE(w.tanggal_lahir, '%d-%m-%Y'), CURDATE())
                              WHEN CAST(w.tanggal_lahir AS CHAR) REGEXP '^[0-9]{2}/[0-9]{2}/[0-9]{4}' THEN TIMESTAMPDIFF(YEAR, STR_TO_DATE(w.tanggal_lahir, '%d/%m/%Y'), CURDATE())
                              ELSE TIMESTAMPDIFF(YEAR, w.tanggal_lahir, CURDATE())
                            END
                          ) BETWEEN 0 AND 5
                        THEN w.id_warga
                    END) AS jumlah_balita,

                    COUNT(DISTINCT CASE
                        WHEN w.id_warga IS NOT NULL AND (
                          w.status_kehamilan = 1 
                          OR w.status_kehamilan = '1' 
                          OR LOWER(CAST(w.status_kehamilan AS CHAR)) = 'on' 
                          OR LOWER(CAST(w.status_kehamilan AS CHAR)) = 'ya'
                        )
                        THEN w.id_warga
                    END) AS jumlah_ibu_hamil,

                    COUNT(DISTINCT CASE
                        WHEN w.id_warga IS NOT NULL
                          AND w.kategori_disabilitas IS NOT NULL
                          AND TRIM(CAST(w.kategori_disabilitas AS CHAR)) <> ''
                          AND LOWER(TRIM(CAST(w.kategori_disabilitas AS CHAR))) <> 'tidak ada'
                          AND LOWER(TRIM(CAST(w.kategori_disabilitas AS CHAR))) <> '0'
                        THEN w.id_warga
                    END) AS jumlah_disabilitas

                FROM rumah r
                LEFT JOIN rt ON r.id_rt = rt.id_rt
                LEFT JOIN rw ON r.id_rw = rw.id_rw
                LEFT JOIN desa d ON r.id_desa = d.id_desa
                LEFT JOIN keluarga k ON k.id_rumah = r.id_rumah
                LEFT JOIN warga w ON (w.id_rumah = r.id_rumah OR (w.id_rumah IS NULL AND w.id_kk = k.id_kk))
                WHERE {$whereSql}
                GROUP BY r.id_rumah, r.alamat_lengkap, r.latitude, r.longitude, r.status_zona_tsunami, rt.nomor_rt, rw.nomor_rw, d.nama_desa
                ORDER BY rt.nomor_rt ASC, r.id_rumah ASC
            ";

            $result = DB::select($sql);
            $rumahData = [];

            foreach ($result as $row) {
                $rawZona = strtolower(trim($row->status_zona ?? ''));
                if (in_array($rawZona, ['merah', 'rawan', 'tinggi', '1', 'ya', 'tsunami', 'zona merah'])) {
                    $zonaBencana = 'Merah';
                } elseif (in_array($rawZona, ['kuning', 'waspada', 'sedang', '2', 'zona kuning'])) {
                    $zonaBencana = 'Kuning';
                } else {
                    $zonaBencana = 'Hijau';
                }

                $namaRumah = !empty($row->nama_warga) ? "Kel. " . $row->nama_warga : "Rumah " . $row->id_rumah;
                $strRt = !empty($row->nomor_rt) ? "RT " . str_pad($row->nomor_rt, 3, "0", STR_PAD_LEFT) : "-";
                $strRw = !empty($row->nomor_rw) ? "RW " . str_pad($row->nomor_rw, 3, "0", STR_PAD_LEFT) : "Semua";

                $rumahData[] = [
                    "id" => (int)$row->id_rumah,
                    "nama" => $namaRumah,
                    "alamat" => $row->alamat_lengkap ?? '-',
                    "desa" => $row->nama_desa ?? '-',
                    "rt" => $strRt,
                    "rw" => $strRw,
                    "nomor_rw" => $row->nomor_rw ?? '',
                    "lat" => (float)$row->latitude,
                    "lng" => (float)$row->longitude,
                    "zona_bencana" => $zonaBencana,
                    "total_warga" => (int)$row->jumlah_warga,
                    "total_lansia" => (int)$row->jumlah_lansia,
                    "total_balita" => (int)$row->jumlah_balita,
                    "total_ibu_hamil" => (int)$row->jumlah_ibu_hamil,
                    "total_disabilitas" => (int)$row->jumlah_disabilitas,
                    "anggota" => (int)$row->jumlah_warga,
                    "detail_warga" => [
                        "lansia" => (int)$row->jumlah_lansia,
                        "balita" => (int)$row->jumlah_balita,
                        "ibu_hamil" => (int)$row->jumlah_ibu_hamil,
                        "disabilitas" => (int)$row->jumlah_disabilitas
                    ]
                ];
            }
            return $rumahData;
        } catch (\Throwable $e) {
            return [];
        }
    }
}