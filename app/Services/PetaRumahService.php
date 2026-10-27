<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Sumber data marker rumah untuk semua peta (Peta Sebaran & peta di setiap dashboard),
 * supaya hitungan lansia/balita/ibu hamil/disabilitas di semua peta selalu sama.
 */
class PetaRumahService
{
    // Umur dalam tahun; mendukung format tanggal Y-m-d, d-m-Y, d/m/Y
    private const UMUR_SQL = "
        CASE
          WHEN CAST(w.tanggal_lahir AS CHAR) REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2}' THEN TIMESTAMPDIFF(YEAR, STR_TO_DATE(w.tanggal_lahir, '%Y-%m-%d'), CURDATE())
          WHEN CAST(w.tanggal_lahir AS CHAR) REGEXP '^[0-9]{2}-[0-9]{2}-[0-9]{4}' THEN TIMESTAMPDIFF(YEAR, STR_TO_DATE(w.tanggal_lahir, '%d-%m-%Y'), CURDATE())
          WHEN CAST(w.tanggal_lahir AS CHAR) REGEXP '^[0-9]{2}/[0-9]{2}/[0-9]{4}' THEN TIMESTAMPDIFF(YEAR, STR_TO_DATE(w.tanggal_lahir, '%d/%m/%Y'), CURDATE())
          ELSE TIMESTAMPDIFF(YEAR, w.tanggal_lahir, CURDATE())
        END";

    private const TGL_VALID_SQL = "
        w.id_warga IS NOT NULL
        AND w.tanggal_lahir IS NOT NULL
        AND TRIM(CAST(w.tanggal_lahir AS CHAR)) <> ''
        AND TRIM(CAST(w.tanggal_lahir AS CHAR)) <> '0000-00-00'";

    public function ambil(WilayahAkses $akses): array
    {
        $whereSql = implode(" AND ", [
            "r.latitude IS NOT NULL",
            "r.longitude IS NOT NULL",
            "TRIM(r.latitude) <> ''",
            "TRIM(r.longitude) <> ''",
            $akses->sql('r'),
        ]);
        $umur = self::UMUR_SQL;
        $tglValid = self::TGL_VALID_SQL;

        try {
            $sql = "
                SELECT
                    r.id_rumah,
                    r.alamat_lengkap,
                    r.latitude,
                    r.longitude,
                    COALESCE(r.status_zona_tsunami, 'Hijau') AS status_zona,
                    r.indeks_bahaya,
                    r.zona_dianalisis_at,
                    COALESCE(rt.nomor_rt, '') AS nomor_rt,
                    COALESCE(rw.nomor_rw, '') AS nomor_rw,
                    COALESCE(d.nama_desa, '') AS nama_desa,
                    COUNT(DISTINCT w.id_warga) AS jumlah_warga,
                    MIN(w.nama_lengkap) AS nama_warga,

                    COUNT(DISTINCT CASE WHEN {$tglValid} AND ({$umur}) >= 60 THEN w.id_warga END) AS jumlah_lansia,
                    COUNT(DISTINCT CASE WHEN {$tglValid} AND ({$umur}) BETWEEN 0 AND 5 THEN w.id_warga END) AS jumlah_balita,

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
                GROUP BY r.id_rumah, r.alamat_lengkap, r.latitude, r.longitude, r.status_zona_tsunami, r.indeks_bahaya, r.zona_dianalisis_at, rt.nomor_rt, rw.nomor_rw, d.nama_desa
                ORDER BY rt.nomor_rt ASC, r.id_rumah ASC
            ";

            $rumahData = [];

            foreach (DB::select($sql) as $row) {
                $rawZona = strtolower(trim($row->status_zona ?? ''));
                if (in_array($rawZona, ['merah', 'rawan', 'tinggi', '1', 'ya', 'tsunami', 'zona merah'])) {
                    $zonaBencana = 'Merah';
                } elseif (in_array($rawZona, ['kuning', 'waspada', 'sedang', '2', 'zona kuning'])) {
                    $zonaBencana = 'Kuning';
                } else {
                    $zonaBencana = 'Hijau';
                }
                // Zona hanya dipercaya bila sudah dihitung dari InaRISK; selain itu dianalisis oleh halaman peta
                if (empty($row->zona_dianalisis_at)) {
                    $zonaBencana = 'Belum';
                }

                $namaRumah = !empty($row->nama_warga) ? "Kel. " . $row->nama_warga : "Rumah " . $row->id_rumah;
                $strRt = !empty($row->nomor_rt) ? "RT " . str_pad($row->nomor_rt, 3, "0", STR_PAD_LEFT) : "-";
                $strRw = !empty($row->nomor_rw) ? "RW " . str_pad($row->nomor_rw, 3, "0", STR_PAD_LEFT) : "";

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
                    "indeks_bahaya" => $row->indeks_bahaya !== null ? (float)$row->indeks_bahaya : null,
                    "perlu_analisis" => empty($row->zona_dianalisis_at),
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
