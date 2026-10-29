<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Satu-satunya sumber aturan "akun ini boleh melihat/mengubah data wilayah mana".
 *
 * Nomor RT & RW di tabel rt/rw bersifat global (RT 001 ada di setiap desa), jadi
 * wilayah RT = desa + RW + RT, dan wilayah RW = desa + RW.
 * Akun yang data wilayahnya tidak lengkap atau rolenya tidak dikenal tidak mendapat data apa pun.
 */
class WilayahAkses
{
    // Kolom wilayah yang wajib terisi untuk tiap role (kolom ini ada di tabel rumah & pengunjung)
    private const KOLOM_WAJIB = [
        'gmls'            => [],
        'admin'           => [],
        'bpbd'            => [], // seluruh Indonesia, tetapi hanya melihat (lihat hanyaLihat())
        'bupati'          => ['id_kabupaten'],
        'camat'           => ['id_kecamatan'],
        'pemerintah_desa' => ['id_desa'],
        'kepala_desa'     => ['id_desa'],
        'kades'           => ['id_desa'],
        'sekdes'          => ['id_desa'],
        'ketua_rw'        => ['id_desa', 'id_rw'],
        'rw'              => ['id_desa', 'id_rw'],
        'ketua_rt'        => ['id_desa', 'id_rw', 'id_rt'],
        'rt'              => ['id_desa', 'id_rw', 'id_rt'],
    ];

    public string $role;
    public array $wilayah;

    public function __construct(string $role, array $wilayah)
    {
        $this->role = strtolower($role);
        $this->wilayah = $wilayah;
    }

    public static function dariSession(): self
    {
        $s = (array) session('admin', []);

        return new self($s['role'] ?? '', [
            'id_kabupaten' => $s['id_kabupaten'] ?? null,
            'id_kecamatan' => $s['id_kecamatan'] ?? null,
            'id_desa'      => $s['id_desa'] ?? null,
            'id_rw'        => $s['id_rw'] ?? null,
            'id_rt'        => $s['id_rt'] ?? null,
        ]);
    }

    public static function kolomWajib(string $role): ?array
    {
        return self::KOLOM_WAJIB[strtolower($role)] ?? null;
    }

    public function isGlobal(): bool
    {
        return in_array($this->role, ['gmls', 'admin'], true);
    }

    /** BPBD memantau data seluruh Indonesia tanpa hak mengubah apa pun. */
    public function hanyaLihat(): bool
    {
        return $this->role === 'bpbd';
    }

    public function isRt(): bool
    {
        return in_array($this->role, ['ketua_rt', 'rt'], true);
    }

    /** Pasangan kolom => nilai yang membatasi akun ini, atau null bila akun tidak boleh melihat apa pun. */
    public function batasan(): ?array
    {
        $kolom = self::kolomWajib($this->role);
        if ($kolom === null) {
            return null;
        }

        $batas = [];
        foreach ($kolom as $k) {
            if (empty($this->wilayah[$k])) {
                return null;
            }
            $batas[$k] = (int) $this->wilayah[$k];
        }
        return $batas;
    }

    /** Batasi query builder; $alias = tabel yang punya kolom id_kabupaten..id_rt (rumah atau pengunjung). */
    public function batasi($query, string $alias = 'r')
    {
        $batas = $this->batasan();
        if ($batas === null) {
            return $query->whereRaw('1 = 0');
        }
        foreach ($batas as $kolom => $nilai) {
            $query->where("{$alias}.{$kolom}", $nilai);
        }
        return $query;
    }

    /** Versi SQL mentah dari batasi(), untuk query DB::select. */
    public function sql(string $alias = 'r'): string
    {
        $batas = $this->batasan();
        if ($batas === null) {
            return '1 = 0';
        }
        $syarat = ['1 = 1'];
        foreach ($batas as $kolom => $nilai) {
            $syarat[] = "{$alias}.{$kolom} = {$nilai}";
        }
        return implode(' AND ', $syarat);
    }

    /** Apakah satu baris (rumah/pengunjung) berada di wilayah akun ini. */
    public function mencakup($baris): bool
    {
        $batas = $this->batasan();
        if ($batas === null || !$baris) {
            return false;
        }
        foreach ($batas as $kolom => $nilai) {
            if ((int) data_get($baris, $kolom) !== $nilai) {
                return false;
            }
        }
        return true;
    }

    /** Apakah warga ini (lewat rumahnya) berada di wilayah akun ini. */
    public function mencakupWarga($idWarga): bool
    {
        if ($this->isGlobal()) {
            return DB::table('warga')->where('id_warga', $idWarga)->exists();
        }
        $rumah = DB::table('warga as w')
            ->join('rumah as r', 'w.id_rumah', '=', 'r.id_rumah')
            ->where('w.id_warga', $idWarga)
            ->first(['r.id_kabupaten', 'r.id_kecamatan', 'r.id_desa', 'r.id_rw', 'r.id_rt']);

        return $this->mencakup($rumah);
    }

    /**
     * Wilayah lengkap milik akun RT (dipakai untuk mengunci input data penduduk).
     * Mengembalikan null bila akun RT belum diatur lengkap.
     */
    public function wilayahRt(): ?array
    {
        if ($this->batasan() === null) {
            return null;
        }
        $desa = DB::table('desa as d')
            ->join('kecamatan as kc', 'd.id_kecamatan', '=', 'kc.id_kecamatan')
            ->join('kabupaten as kb', 'kc.id_kabupaten', '=', 'kb.id_kabupaten')
            ->leftJoin('provinsi as p', 'kb.id_provinsi', '=', 'p.id_provinsi')
            ->where('d.id_desa', $this->wilayah['id_desa'])
            ->first(['d.id_desa', 'd.nama_desa', 'kc.id_kecamatan', 'kc.nama_kecamatan', 'kb.id_kabupaten', 'kb.nama_kabupaten', 'p.id_provinsi', 'p.nama_provinsi']);
        if (!$desa) {
            return null;
        }

        return [
            'id_provinsi'    => $desa->id_provinsi,
            'id_kabupaten'   => $desa->id_kabupaten,
            'id_kecamatan'   => $desa->id_kecamatan,
            'id_desa'        => $desa->id_desa,
            'id_rw'          => (int) $this->wilayah['id_rw'],
            'id_rt'          => (int) $this->wilayah['id_rt'],
            'nama_provinsi'  => $desa->nama_provinsi,
            'nama_kabupaten' => $desa->nama_kabupaten,
            'nama_kecamatan' => $desa->nama_kecamatan,
            'nama_desa'      => $desa->nama_desa,
            'nomor_rw'       => DB::table('rw')->where('id_rw', $this->wilayah['id_rw'])->value('nomor_rw'),
            'nomor_rt'       => DB::table('rt')->where('id_rt', $this->wilayah['id_rt'])->value('nomor_rt'),
        ];
    }
}
