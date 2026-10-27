<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\WilayahAkses;
use App\Services\DataYatim;

class WargaController extends Controller
{
    /**
     * Hanya GMLS atau RT pemilik wilayah yang boleh mengubah/menghapus data warga ini.
     * Mengembalikan pesan penolakan, atau null bila diizinkan.
     */
    private function cekHakKelolaWarga($idWarga): ?string
    {
        $akses = WilayahAkses::dariSession();
        if (!$akses->isGlobal() && !$akses->isRt()) {
            return 'Hanya Ketua RT yang dapat mengelola data penduduk.';
        }
        if (!$akses->mencakupWarga($idWarga)) {
            return 'Data warga tidak ditemukan atau berada di luar wilayah Anda.';
        }
        return null;
    }

    /**
     * Menampilkan daftar warga dan pendatang (Disaring sesuai wilayah/role yang login)
     */
    public function daftarWarga()
    {
        // 1. QUERY UTAMA WARGA TETAP
        $query = DB::table('warga as w')
            ->leftJoin('rumah as r', 'w.id_rumah', '=', 'r.id_rumah')
            ->leftJoin('desa as d', 'w.id_desa', '=', 'd.id_desa')
            ->leftJoin('kecamatan as c', 'w.id_kecamatan', '=', 'c.id_kecamatan')
            ->leftJoin('kabupaten as kb', 'w.id_kabupaten', '=', 'kb.id_kabupaten')
            ->leftJoin('provinsi as p', 'w.id_provinsi', '=', 'p.id_provinsi')
            ->leftJoin('rt', 'r.id_rt', '=', 'rt.id_rt')
            ->leftJoin('rw', 'r.id_rw', '=', 'rw.id_rw')
            ->select(
                'w.id_warga', 'w.nik', 'w.nama_lengkap', 'w.tempat_lahir', 'w.tanggal_lahir', 'w.jenis_kelamin',
                'w.golongan_darah', 'w.agama', 'w.status_perkawinan', 'w.pekerjaan', 'w.nomor_telepon',
                'w.status_kehamilan', 'w.kategori_disabilitas',
                'r.alamat_lengkap', 'd.nama_desa', 'c.nama_kecamatan', 'kb.nama_kabupaten', 'p.nama_provinsi',
                'rt.nomor_rt', 'rw.nomor_rw'
            );

        // 2. QUERY UTAMA PENDATANG (YANG SUDAH DI-ACC / DIKETAHUI)
        $queryPendatang = DB::table('pengunjung as pj')
            ->leftJoin('desa as d', 'pj.id_desa', '=', 'd.id_desa')
            ->leftJoin('kecamatan as c', 'pj.id_kecamatan', '=', 'c.id_kecamatan')
            ->leftJoin('kabupaten as kb', 'pj.id_kabupaten', '=', 'kb.id_kabupaten')
            ->leftJoin('provinsi as p', 'pj.id_provinsi', '=', 'p.id_provinsi')
            ->leftJoin('rt', 'pj.id_rt', '=', 'rt.id_rt')
            ->leftJoin('rw', 'pj.id_rw', '=', 'rw.id_rw')
            ->where('pj.status_laporan', 'Diketahui')
            ->select(
                'pj.*', 
                'd.nama_desa', 'c.nama_kecamatan', 'kb.nama_kabupaten', 'p.nama_provinsi',
                'rt.nomor_rt', 'rw.nomor_rw'
            );

        // Pembatasan wilayah sesuai akun (RT = desa + RW + RT, RW = desa + RW, dst.)
        $akses = WilayahAkses::dariSession();
        $akses->batasi($query, 'r');
        $akses->batasi($queryPendatang, 'pj');

        $warga = $query->orderBy('w.nama_lengkap', 'asc')->get();
        $pendatang = $queryPendatang->orderBy('pj.nama_lengkap', 'asc')->get();
        $provinsi = DB::table('provinsi')->orderBy('nama_provinsi', 'asc')->get();

        $viewPath = view()->exists('admin.daftar_warga') ? 'admin.daftar_warga' : 'daftar_warga';

        return view($viewPath, compact('warga', 'pendatang', 'provinsi'));
    }

    /**
     * Menampilkan daftar pengajuan perubahan data dari warga (Disaring sesuai wilayah)
     */
    public function indexPengajuan()
    {
        $akses = WilayahAkses::dariSession();
        if (!$akses->isGlobal() && !$akses->isRt()) {
            return redirect()->route('dashboard')->with('error', 'Verifikasi data hanya untuk Ketua RT.');
        }

        $query = DB::table('pengajuan_update as p')
            ->join('warga as w', 'p.id_warga', '=', 'w.id_warga')
            ->leftJoin('rumah as r', 'w.id_rumah', '=', 'r.id_rumah')
            ->leftJoin('rt', 'r.id_rt', '=', 'rt.id_rt')
            ->leftJoin('rw', 'r.id_rw', '=', 'rw.id_rw')
            ->select(
                'p.*', 
                'w.nik', 
                'w.nama_lengkap as nama_asal', 
                'rt.nomor_rt', 
                'rw.nomor_rw'
            );

        $akses->batasi($query, 'r');

        $pengajuan = $query->orderBy('p.created_at', 'desc')->get();
        $viewPath = view()->exists('admin.verifikasi_pengajuan') ? 'admin.verifikasi_pengajuan' : 'verifikasi_pengajuan';

        return view($viewPath, compact('pengajuan'));
    }

    /**
     * Menyetujui pengajuan perubahan data warga
     */
    public function setujuiPengajuan(Request $request, $id)
    {
        $idWarga = DB::table('pengajuan_update')->where('id_pengajuan', $id)->value('id_warga');
        if ($tolak = $this->cekHakKelolaWarga($idWarga)) {
            return redirect()->back()->with('error', $tolak);
        }

        try {
            DB::transaction(function () use ($id, $request) {
                $item = DB::table('pengajuan_update')->where('id_pengajuan', $id)->first();
                if (!$item || $item->status !== 'pending') {
                    throw new \Exception("Pengajuan tidak ditemukan atau sudah diproses.");
                }

                $dataBaru = json_decode($item->data_baru, true);

                DB::table('warga')->where('id_warga', $item->id_warga)->update(array_merge($dataBaru, [
                    'updated_at' => now()
                ]));

                DB::table('pengajuan_update')->where('id_pengajuan', $id)->update([
                    'status' => 'disetujui',
                    'catatan_admin' => $request->catatan_admin ?? 'Data telah diverifikasi dan sesuai.',
                    'updated_at' => now()
                ]);
            });

            return redirect()->back()->with('success', 'Pengajuan perubahan data berhasil disetujui dan data warga telah terupdate!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal memproses pengajuan: ' . $e->getMessage());
        }
    }

    /**
     * Menolak pengajuan perubahan data warga
     */
    public function tolakPengajuan(Request $request, $id)
    {
        $idWarga = DB::table('pengajuan_update')->where('id_pengajuan', $id)->value('id_warga');
        if ($tolak = $this->cekHakKelolaWarga($idWarga)) {
            return redirect()->back()->with('error', $tolak);
        }

        try {
            $item = DB::table('pengajuan_update')->where('id_pengajuan', $id)->first();
            if (!$item || $item->status !== 'pending') {
                return redirect()->back()->with('error', 'Pengajuan tidak ditemukan atau sudah diproses.');
            }

            DB::table('pengajuan_update')->where('id_pengajuan', $id)->update([
                'status' => 'ditolak',
                'catatan_admin' => $request->catatan_admin ?? 'Data tidak sesuai atau berkas kurang valid.',
                'updated_at' => now()
            ]);

            return redirect()->back()->with('success', 'Pengajuan perubahan data telah ditolak.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menolak pengajuan: ' . $e->getMessage());
        }
    }

    /**
     * Menampilkan form edit data warga (Pak RT / Admin)
     */
    public function edit($id)
    {
        if ($tolak = $this->cekHakKelolaWarga($id)) {
            return redirect()->route('warga.index')->with('error', $tolak);
        }

        $warga = DB::table('warga as w')
            ->leftJoin('keluarga as k', 'w.id_kk', '=', 'k.id_kk')
            ->leftJoin('kabupaten as kb', 'w.id_kabupaten', '=', 'kb.id_kabupaten')
            ->leftJoin('kecamatan as c', 'w.id_kecamatan', '=', 'c.id_kecamatan')
            ->leftJoin('desa as d', 'w.id_desa', '=', 'd.id_desa')
            ->where('w.id_warga', $id)
            ->select('w.*', 'k.nomor_kk as nomor_kk_kk', 'k.id_kepala_keluarga', 'kb.nama_kabupaten', 'c.nama_kecamatan', 'd.nama_desa')
            ->first();

        if (!$warga) {
            return redirect()->back()->with('error', 'Data warga tidak ditemukan.');
        }

        $provinsi = DB::table('provinsi')->orderBy('nama_provinsi', 'asc')->get();
        $kota = DB::table('kabupaten')->orderBy('nama_kabupaten', 'asc')->get();
        $rt = DB::table('rt')->orderBy('nomor_rt', 'asc')->get();
        $rw = DB::table('rw')->orderBy('nomor_rw', 'asc')->get();

        $kabupaten_options = [];
        if (!empty($warga->id_provinsi)) {
            $kabupaten_options = DB::table('kabupaten')->where('id_provinsi', $warga->id_provinsi)->orderBy('nama_kabupaten', 'asc')->get();
        }
        $kecamatan_options = [];
        if (!empty($warga->id_kabupaten)) {
            $kecamatan_options = DB::table('kecamatan')->where('id_kabupaten', $warga->id_kabupaten)->orderBy('nama_kecamatan', 'asc')->get();
        }
        $desa_options = [];
        if (!empty($warga->id_kecamatan)) {
            $desa_options = DB::table('desa')->where('id_kecamatan', $warga->id_kecamatan)->orderBy('nama_desa', 'asc')->get();
        }
        $rumah = !empty($warga->id_rumah) ? DB::table('rumah')->where('id_rumah', $warga->id_rumah)->first() : null;
        $akses = WilayahAkses::dariSession();
        $wilayahRt = $akses->isRt() ? $akses->wilayahRt() : null;

        $viewPath = view()->exists('admin.edit_warga') ? 'admin.edit_warga' : 'edit_warga';

        return view($viewPath, compact(
            'warga', 'provinsi', 'kota', 'rt', 'rw',
            'kabupaten_options', 'kecamatan_options', 'desa_options',
            'rumah', 'wilayahRt'
        ));
    }

    /**
     * Memperbarui data warga di database
     */
    public function update(Request $request, $id)
    {
        if ($tolak = $this->cekHakKelolaWarga($id)) {
            return redirect()->route('warga.index')->with('error', $tolak);
        }

        // RT tidak bisa memindahkan warga ke wilayah lain: wilayah dikunci ke wilayah akunnya
        $akses = WilayahAkses::dariSession();
        if ($akses->isRt()) {
            $wilayahRt = $akses->wilayahRt();
            if (!$wilayahRt) {
                return redirect()->back()->withInput()->with('error', 'Wilayah akun RT Anda belum lengkap. Hubungi GMLS.');
            }
            $request->merge([
                'provinsi'  => $wilayahRt['id_provinsi'],
                'kabupaten' => $wilayahRt['id_kabupaten'],
                'kecamatan' => $wilayahRt['id_kecamatan'],
                'desa'      => $wilayahRt['id_desa'],
                'id_rw'     => $wilayahRt['id_rw'],
                'id_rt'     => $wilayahRt['id_rt'],
            ]);
        }

        $request->validate([
            'nik' => 'required|numeric|digits:16',
            'nama_lengkap' => 'required|string|max:255',
            'tempat_lahir' => 'required',
            'tanggal_lahir' => 'required|date|before_or_equal:today',
            'jenis_kelamin' => 'required|in:L,P',
            'nomor_kk' => 'required|numeric|digits:16',
        ]);

        // Bila nomor KK diganti ke KK yang sudah terdaftar di rumah lain, warga pindah ke rumah KK itu
        $kkTujuan = DB::table('keluarga as k')->join('rumah as r', 'k.id_rumah', '=', 'r.id_rumah')
            ->where('k.nomor_kk', $request->nomor_kk)
            ->first(['k.id_kk', 'r.id_rumah', 'r.alamat_lengkap', 'r.id_provinsi', 'r.id_kabupaten', 'r.id_kecamatan', 'r.id_desa', 'r.id_rw', 'r.id_rt']);
        $rumahSekarang = DB::table('warga')->where('id_warga', $id)->value('id_rumah');
        $pindahRumah = $kkTujuan && (int) $kkTujuan->id_rumah !== (int) $rumahSekarang;
        if ($pindahRumah && !$akses->isGlobal() && !$akses->mencakup($kkTujuan)) {
            return redirect()->back()->withInput()->with('error', 'Nomor KK ini sudah terdaftar di wilayah RT lain.');
        }

        try {
            DB::transaction(function () use ($request, $id, $kkTujuan, $pindahRumah) {
                $oldWarga = DB::table('warga')->where('id_warga', $id)->first();
                if (!$oldWarga) throw new \Exception("Data warga tidak ditemukan.");

                $id_rumah = $oldWarga->id_rumah;
                $id_kk_lama = $oldWarga->id_kk;
                if ($pindahRumah) {
                    // Ikut rumah KK tujuan; data rumah pada form diabaikan
                    $id_rumah = $kkTujuan->id_rumah;
                    $request->merge([
                        'alamat_lengkap' => $kkTujuan->alamat_lengkap, 'provinsi' => $kkTujuan->id_provinsi,
                        'kabupaten' => $kkTujuan->id_kabupaten, 'kecamatan' => $kkTujuan->id_kecamatan, 'desa' => $kkTujuan->id_desa,
                    ]);
                } elseif ($id_rumah) {
                    // Zona dihitung otomatis dari InaRISK BNPB; dianalisis ulang bila koordinat berubah
                    $rumahLama = DB::table('rumah')->where('id_rumah', $id_rumah)->first(['latitude', 'longitude']);
                    $koordinatBerubah = !$rumahLama
                        || (float) $rumahLama->latitude !== (float) $request->latitude
                        || (float) $rumahLama->longitude !== (float) $request->longitude;

                    DB::table('rumah')->where('id_rumah', $id_rumah)->update(array_merge([
                        'alamat_lengkap' => $request->alamat_lengkap, 'id_rt' => $request->id_rt, 'id_rw' => $request->id_rw,
                        'id_desa' => $request->desa, 'id_kecamatan' => $request->kecamatan, 'id_kabupaten' => $request->kabupaten,
                        'id_provinsi' => $request->provinsi, 'jenis_konstruksi' => $request->jenis_konstruksi,
                        'latitude' => $request->latitude, 'longitude' => $request->longitude,
                        'updated_at' => now(),
                    ], $koordinatBerubah ? ['zona_dianalisis_at' => null] : []));
                } else {
                    $id_rumah = DB::table('rumah')->insertGetId([
                        'alamat_lengkap' => $request->alamat_lengkap, 'id_rt' => $request->id_rt, 'id_rw' => $request->id_rw,
                        'id_desa' => $request->desa, 'id_kecamatan' => $request->kecamatan, 'id_kabupaten' => $request->kabupaten,
                        'id_provinsi' => $request->provinsi, 'jenis_konstruksi' => $request->jenis_konstruksi,
                        'status_zona_tsunami' => null, 'latitude' => $request->latitude, 'longitude' => $request->longitude,
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                }

                $id_kk = null;
                if ($request->nomor_kk) {
                    $existingKk = DB::table('keluarga')->where('nomor_kk', $request->nomor_kk)->first();
                    if ($existingKk) {
                        $id_kk = $existingKk->id_kk;
                    } else {
                        $id_kk = DB::table('keluarga')->insertGetId([
                            'nomor_kk' => $request->nomor_kk, 'id_rumah' => $id_rumah, 'created_at' => now(), 'updated_at' => now(),
                        ]);
                    }
                }

                $isKepalaKeluarga = $request->has('is_kepala_keluarga') ? 1 : 0;
                $statusKehamilan = ($request->jenis_kelamin === 'P' && ($request->has('status_kehamilan') || $request->status_kehamilan == 1)) ? 1 : 0;
                $kategoriDisabilitas = $request->kategori_disabilitas ?: 'Tidak Ada';

                DB::table('warga')->where('id_warga', $id)->update([
                    'nik' => $request->nik, 'nama_lengkap' => $request->nama_lengkap, 'tempat_lahir' => $request->tempat_lahir,
                    'tanggal_lahir' => $request->tanggal_lahir, 'jenis_kelamin' => $request->jenis_kelamin, 'golongan_darah' => $request->golongan_darah,
                    'agama' => $request->agama, 'status_perkawinan' => $request->status_perkawinan, 'pekerjaan' => $request->pekerjaan,
                    'nomor_telepon' => $request->nomor_telepon, 'status_domisili' => $request->status_domisili,
                    'status_kehamilan' => $statusKehamilan, 'perkiraan_lahir' => ($statusKehamilan === 1) ? $request->perkiraan_lahir : null,
                    'kategori_disabilitas' => $kategoriDisabilitas, 'keterangan_disabilitas' => ($kategoriDisabilitas !== 'Tidak Ada') ? $request->keterangan_disabilitas : null,
                    'jenis_penghasilan' => $request->jenis_penghasilan, 'nomor_kk' => $request->nomor_kk, 'is_kepala_keluarga' => $isKepalaKeluarga,
                    'id_kk' => $id_kk, 'alamat_lengkap' => $request->alamat_lengkap, 'id_provinsi' => $request->provinsi,
                    'id_kabupaten' => $request->kabupaten, 'id_kecamatan' => $request->kecamatan, 'id_desa' => $request->desa,
                    'id_rumah' => $id_rumah, 'updated_at' => now(),
                ]);

                if ($isKepalaKeluarga && $id_kk) {
                    DB::table('keluarga')->where('id_kk', $id_kk)->update(['id_kepala_keluarga' => $id, 'id_rumah' => $id_rumah, 'updated_at' => now()]);
                    // Satu KK hanya punya satu kepala keluarga
                    DB::table('warga')->where('id_kk', $id_kk)->where('id_warga', '!=', $id)->update(['is_kepala_keluarga' => 0]);
                } elseif (!$isKepalaKeluarga && $id_kk) {
                    DB::table('keluarga')->where('id_kk', $id_kk)->where('id_kepala_keluarga', $id)->update(['id_kepala_keluarga' => null, 'updated_at' => now()]);
                }
                if ($id_kk_lama && $id_kk_lama != $id_kk) {
                    DB::table('keluarga')->where('id_kk', $id_kk_lama)->where('id_kepala_keluarga', $id)->update(['id_kepala_keluarga' => null, 'updated_at' => now()]);
                }

                // KK / rumah lama yang kini kosong ikut dihapus
                DataYatim::bersihkan($id_kk_lama, $oldWarga->id_rumah);
            });

            $pesan = $pindahRumah
                ? 'Data penduduk berhasil diperbarui! Warga dipindahkan ke rumah sesuai nomor KK yang baru.'
                : 'Data penduduk berhasil diperbarui!';
            return redirect()->route('warga.edit', $id)->with('success', $pesan);
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Gagal memperbarui data: ' . $e->getMessage());
        }
    }

    /**
     * Menghapus data warga
     */
    public function destroy($id)
    {
        if ($tolak = $this->cekHakKelolaWarga($id)) {
            return redirect()->back()->with('error', $tolak);
        }

        try {
            $hapus = DB::transaction(function () use ($id) {
                $warga = DB::table('warga')->where('id_warga', $id)->first(['id_kk', 'id_rumah']);
                DB::table('pengajuan_update')->where('id_warga', $id)->delete();
                DB::table('warga')->where('id_warga', $id)->delete();

                // KK & rumah ikut terhapus bila warga ini penghuni terakhirnya
                $kkAda = $warga->id_kk && DB::table('keluarga')->where('id_kk', $warga->id_kk)->exists();
                $rumahAda = $warga->id_rumah && DB::table('rumah')->where('id_rumah', $warga->id_rumah)->exists();
                DataYatim::bersihkan($warga->id_kk, $warga->id_rumah);
                return [
                    'kk' => $kkAda && !DB::table('keluarga')->where('id_kk', $warga->id_kk)->exists(),
                    'rumah' => $rumahAda && !DB::table('rumah')->where('id_rumah', $warga->id_rumah)->exists(),
                ];
            });

            $pesan = 'Data warga berhasil dihapus!';
            if ($hapus['kk'] || $hapus['rumah']) {
                $pesan .= ' ' . implode(' dan ', array_filter([$hapus['kk'] ? 'Kartu Keluarga' : null, $hapus['rumah'] ? 'data rumah' : null]))
                    . ' ikut dihapus karena tidak ada anggota lain.';
            }
            return redirect()->back()->with('success', $pesan);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus data warga: ' . $e->getMessage());
        }
    }
}