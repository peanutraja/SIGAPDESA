<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EvakuasiController extends Controller
{
    public function index()
    {
        $rumahData = [];

        // 1. Ambil Data Pengguna Login dari Session
        $adminSession = session('admin');
        if (!$adminSession && auth()->check()) {
            $adminSession = auth()->user();
        }

        // Ekstraksi Role & ID Wilayah
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

        // 2. Susun Filter SQL Pembatasan Wilayah Berdasarkan Peran Pejabat
        $whereClauses = [
            "r.latitude IS NOT NULL",
            "r.longitude IS NOT NULL",
            "TRIM(r.latitude) <> ''",
            "TRIM(r.longitude) <> ''"
        ];

        if ($role === 'bupati' && !empty($id_kabupaten)) {
            $whereClauses[] = "r.id_kabupaten = " . (int)$id_kabupaten;
        } elseif ($role === 'camat' && !empty($id_kecamatan)) {
            $whereClauses[] = "r.id_kecamatan = " . (int)$id_kecamatan;
        } elseif (in_array($role, ['kepala_desa', 'kades', 'sekdes']) && !empty($id_desa)) {
            $whereClauses[] = "r.id_desa = " . (int)$id_desa;
        } elseif (in_array($role, ['ketua_rw', 'rw']) && !empty($id_rw)) {
            $whereClauses[] = "r.id_rw = " . (int)$id_rw;
            if (!empty($id_desa)) {
                $whereClauses[] = "r.id_desa = " . (int)$id_desa;
            }
        } elseif (in_array($role, ['ketua_rt', 'rt']) && !empty($id_rt)) {
            $whereClauses[] = "r.id_rt = " . (int)$id_rt;
        }

        $whereSql = implode(" AND ", $whereClauses);

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
                    
                    COALESCE(SUM(CASE
                        WHEN w.id_warga IS NOT NULL THEN
                            CASE
                                WHEN w.tanggal_lahir IS NOT NULL 
                                     AND w.tanggal_lahir <> '0000-00-00'
                                     AND TIMESTAMPDIFF(YEAR, w.tanggal_lahir, CURDATE()) >= 60 THEN 1
                                WHEN w.tanggal_lahir IS NOT NULL 
                                     AND w.tanggal_lahir <> '0000-00-00'
                                     AND (
                                        w.is_kepala_keluarga = 1 
                                        OR LOWER(CAST(w.status_perkawinan AS CHAR)) IN ('kawin', 'janda', 'duda')
                                        OR (w.pekerjaan IS NOT NULL AND TRIM(w.pekerjaan) <> '' AND LOWER(TRIM(w.pekerjaan)) NOT IN ('-', 'belum/tidak bekerja', 'pelajar/mahasiswa', 'tidak ada'))
                                     )
                                     AND TIMESTAMPDIFF(YEAR, DATE_SUB(w.tanggal_lahir, INTERVAL 100 YEAR), CURDATE()) >= 60 THEN 1
                                WHEN w.tanggal_lahir IS NOT NULL 
                                     AND w.tanggal_lahir <> '0000-00-00'
                                     AND YEAR(w.tanggal_lahir) > YEAR(CURDATE())
                                     AND TIMESTAMPDIFF(YEAR, DATE_SUB(w.tanggal_lahir, INTERVAL 100 YEAR), CURDATE()) >= 60 THEN 1
                                ELSE 0
                            END
                        ELSE 0
                    END), 0) AS jumlah_lansia,

                    COALESCE(SUM(CASE
                        WHEN w.id_warga IS NOT NULL 
                          AND w.tanggal_lahir IS NOT NULL 
                          AND w.tanggal_lahir <> '0000-00-00'
                          AND (w.is_kepala_keluarga IS NULL OR w.is_kepala_keluarga = 0)
                          AND (w.status_perkawinan IS NULL OR LOWER(CAST(w.status_perkawinan AS CHAR)) NOT IN ('kawin', 'janda', 'duda'))
                          AND (w.pekerjaan IS NULL OR TRIM(w.pekerjaan) = '' OR LOWER(TRIM(w.pekerjaan)) IN ('-', 'belum/tidak bekerja', 'pelajar/mahasiswa', 'tidak ada'))
                          AND TIMESTAMPDIFF(YEAR, w.tanggal_lahir, CURDATE()) BETWEEN 0 AND 5
                          AND YEAR(w.tanggal_lahir) <= YEAR(CURDATE())
                        THEN 1 ELSE 0
                    END), 0) AS jumlah_balita,

                    COALESCE(SUM(CASE
                        WHEN w.status_kehamilan = 1 
                          OR w.status_kehamilan = '1' 
                          OR LOWER(CAST(w.status_kehamilan AS CHAR)) = 'on' 
                          OR LOWER(CAST(w.status_kehamilan AS CHAR)) = 'ya'
                        THEN 1 ELSE 0
                    END), 0) AS jumlah_ibu_hamil,

                    COALESCE(SUM(CASE
                        WHEN w.kategori_disabilitas IS NOT NULL
                          AND TRIM(w.kategori_disabilitas) <> ''
                          AND LOWER(TRIM(w.kategori_disabilitas)) <> 'tidak ada'
                          AND LOWER(TRIM(w.kategori_disabilitas)) <> '0'
                        THEN 1 ELSE 0
                    END), 0) AS jumlah_disabilitas

                FROM rumah r
                LEFT JOIN rt ON r.id_rt = rt.id_rt
                LEFT JOIN rw ON r.id_rw = rw.id_rw
                LEFT JOIN desa d ON r.id_desa = d.id_desa
                LEFT JOIN keluarga k ON k.id_rumah = r.id_rumah
                LEFT JOIN warga w ON (w.id_rumah = r.id_rumah OR (w.id_rumah IS NULL AND w.id_kk = k.id_kk))
                WHERE {$whereSql}
                GROUP BY
                    r.id_rumah,
                    r.alamat_lengkap,
                    r.latitude,
                    r.longitude,
                    r.status_zona_tsunami,
                    rt.nomor_rt,
                    rw.nomor_rw,
                    d.nama_desa
                ORDER BY rt.nomor_rt ASC, r.id_rumah ASC
            ";

            $result = DB::select($sql);

            foreach ($result as $row) {
                $kelompok = [];

                if ((int)$row->jumlah_lansia > 0) $kelompok[] = "lansia";
                if ((int)$row->jumlah_balita > 0) $kelompok[] = "balita";
                if ((int)$row->jumlah_ibu_hamil > 0) $kelompok[] = "ibu_hamil";
                if ((int)$row->jumlah_disabilitas > 0) $kelompok[] = "disabilitas";

                $rawZona = strtolower(trim($row->status_zona ?? ''));
                if (in_array($rawZona, ['merah', 'rawan', 'tinggi', '1', 'ya', 'tsunami', 'zona merah'])) {
                    $zonaBencana = 'Merah';
                } elseif (in_array($rawZona, ['kuning', 'waspada', 'sedang', '2', 'zona kuning'])) {
                    $zonaBencana = 'Kuning';
                } else {
                    $zonaBencana = 'Hijau';
                }

                $namaRumah = !empty($row->nama_warga)
                    ? "Kel. " . $row->nama_warga
                    : "Rumah " . $row->id_rumah;

                $strRt = !empty($row->nomor_rt) ? "RT " . str_pad($row->nomor_rt, 3, "0", STR_PAD_LEFT) : "-";
                $strRw = !empty($row->nomor_rw) ? "RW " . str_pad($row->nomor_rw, 3, "0", STR_PAD_LEFT) : "";

                $rumahData[] = [
                    "id" => (int)$row->id_rumah,
                    "nama" => $namaRumah,
                    "alamat" => $row->alamat_lengkap,
                    "desa" => $row->nama_desa,
                    "rt" => $strRt,
                    "rw" => $strRw,
                    "nomor_rw" => $row->nomor_rw,
                    "lat" => (float)$row->latitude,
                    "lng" => (float)$row->longitude,
                    "zona_bencana" => $zonaBencana,
                    "kelompok" => $kelompok,
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
        } catch (\Throwable $e) {
            // Penanganan error
        }

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