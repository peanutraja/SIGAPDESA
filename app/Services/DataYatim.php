<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Membersihkan Kartu Keluarga & rumah yang sudah tidak punya penghuni.
 * KK dihapus bila tidak ada warga di KK itu; rumah dihapus bila tidak ada warga
 * maupun KK yang menempatinya.
 */
class DataYatim
{
    /** Bersihkan KK & rumah tertentu (dipanggil setelah warga dihapus/pindah KK). */
    public static function bersihkan($idKk, $idRumah): void
    {
        if ($idKk && !DB::table('warga')->where('id_kk', $idKk)->exists()) {
            DB::table('keluarga')->where('id_kk', $idKk)->delete();
        }
        if ($idRumah
            && !DB::table('warga')->where('id_rumah', $idRumah)->exists()
            && !DB::table('keluarga')->where('id_rumah', $idRumah)->exists()) {
            DB::table('rumah')->where('id_rumah', $idRumah)->delete();
        }
    }

    /** Bersihkan semua KK & rumah tanpa penghuni. Mengembalikan jumlah yang dihapus. */
    public static function bersihkanSemua(): array
    {
        // MariaDB tidak menerima alias tabel pada DELETE, jadi pakai NOT IN (subquery ke tabel lain)
        $kk = DB::table('keluarga')
            ->whereNotIn('id_kk', DB::table('warga')->whereNotNull('id_kk')->select('id_kk'))
            ->delete();

        $rumah = DB::table('rumah')
            ->whereNotIn('id_rumah', DB::table('warga')->whereNotNull('id_rumah')->select('id_rumah'))
            ->whereNotIn('id_rumah', DB::table('keluarga')->whereNotNull('id_rumah')->select('id_rumah'))
            ->delete();

        $pengajuan = DB::table('pengajuan_update')
            ->whereNotIn('id_warga', DB::table('warga')->select('id_warga'))
            ->delete();

        return compact('kk', 'rumah', 'pengajuan');
    }
}
