<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WargaController extends Controller
{
    /**
     * Helper privat untuk mengambil data session & wilayah admin secara aman
     */
    private function getAdminSessionData()
    {
        $adminSession = session('admin');
        if (!$adminSession && auth()->check()) {
            $adminSession = auth()->user();
        }

        if (is_array($adminSession)) {
            return [
                'role'         => $adminSession['role'] ?? 'gmls',
                'id_kabupaten' => $adminSession['id_kabupaten'] ?? null,
                'id_kecamatan' => $adminSession['id_kecamatan'] ?? null,
                'id_desa'      => $adminSession['id_desa'] ?? null,
                'id_rw'        => $adminSession['id_rw'] ?? $adminSession['rw'] ?? null,
                'id_rt'        => $adminSession['id_rt'] ?? $adminSession['rt'] ?? null,
            ];
        } else if (is_object($adminSession)) {
            return [
                'role'         => $adminSession->role ?? 'gmls',
                'id_kabupaten' => $adminSession->id_kabupaten ?? null,
                'id_kecamatan' => $adminSession->id_kecamatan ?? null,
                'id_desa'      => $adminSession->id_desa ?? null,
                'id_rw'        => $adminSession->id_rw ?? $adminSession->rw ?? null,
                'id_rt'        => $adminSession->id_rt ?? $adminSession->rt ?? null,
            ];
        }

        return [
            'role' => 'gmls',
            'id_kabupaten' => null,
            'id_kecamatan' => null,
            'id_desa' => null,
            'id_rw' => null,
            'id_rt' => null,
        ];
    }

    /**
     * Menampilkan daftar warga dan pendatang (Disaring sesuai wilayah/role yang login)
     */
    public function daftarWarga()
    {
        $sessionData  = $this->getAdminSessionData();
        $role         = $sessionData['role'];
        $id_kabupaten = $sessionData['id_kabupaten'];
        $id_kecamatan = $sessionData['id_kecamatan'];
        $id_desa      = $sessionData['id_desa'];
        $id_rw        = $sessionData['id_rw'];
        $id_rt        = $sessionData['id_rt'];

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

        // =========================================================================
        // PEMBATASAN WILAYAH BERDASARKAN ROLE PEJABAT SANGAT KETAT
        // =========================================================================
        if ($role === 'bupati') {
            if (!empty($id_kabupaten)) {
                $query->where('w.id_kabupaten', $id_kabupaten);
                $queryPendatang->where('pj.id_kabupaten', $id_kabupaten);
            }
        } elseif ($role === 'camat') {
            if (!empty($id_kecamatan)) {
                $query->where('w.id_kecamatan', $id_kecamatan);
                $queryPendatang->where('pj.id_kecamatan', $id_kecamatan);
            }
        } elseif (in_array($role, ['kepala_desa', 'kades', 'sekdes'])) {
            if (!empty($id_desa)) {
                $query->where('w.id_desa', $id_desa);
                $queryPendatang->where('pj.id_desa', $id_desa);
            }
        } elseif (in_array($role, ['ketua_rw', 'rw'])) {
            if (!empty($id_rw)) {
                $query->where('r.id_rw', $id_rw);
                $queryPendatang->where('pj.id_rw', $id_rw);
            }
            if (!empty($id_desa)) {
                $query->where('w.id_desa', $id_desa);
                $queryPendatang->where('pj.id_desa', $id_desa);
            }
        } elseif (in_array($role, ['ketua_rt', 'rt'])) {
            if (!empty($id_rt)) {
                $query->where('r.id_rt', $id_rt);
                $queryPendatang->where('pj.id_rt', $id_rt);
            }
            if (!empty($id_rw)) {
                $query->where('r.id_rw', $id_rw);
                $queryPendatang->where('pj.id_rw', $id_rw);
            }
            if (!empty($id_desa)) {
                $query->where('w.id_desa', $id_desa);
                $queryPendatang->where('pj.id_desa', $id_desa);
            }
        }

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
        $sessionData  = $this->getAdminSessionData();
        $role         = $sessionData['role'];
        $id_kabupaten = $sessionData['id_kabupaten'];
        $id_kecamatan = $sessionData['id_kecamatan'];
        $id_desa      = $sessionData['id_desa'];
        $id_rw        = $sessionData['id_rw'];
        $id_rt        = $sessionData['id_rt'];

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

        if ($role === 'bupati' && !empty($id_kabupaten)) {
            $query->where('w.id_kabupaten', $id_kabupaten);
        } elseif ($role === 'camat' && !empty($id_kecamatan)) {
            $query->where('w.id_kecamatan', $id_kecamatan);
        } elseif (in_array($role, ['kepala_desa', 'kades', 'sekdes']) && !empty($id_desa)) {
            $query->where('w.id_desa', $id_desa);
        } elseif (in_array($role, ['ketua_rw', 'rw'])) {
            if (!empty($id_rw)) $query->where('r.id_rw', $id_rw);
            if (!empty($id_desa)) $query->where('w.id_desa', $id_desa);
        } elseif (in_array($role, ['ketua_rt', 'rt'])) {
            if (!empty($id_rt)) $query->where('r.id_rt', $id_rt);
            if (!empty($id_rw)) $query->where('r.id_rw', $id_rw);
            if (!empty($id_desa)) $query->where('w.id_desa', $id_desa);
        }

        $pengajuan = $query->orderBy('p.created_at', 'desc')->get();
        $viewPath = view()->exists('admin.verifikasi_pengajuan') ? 'admin.verifikasi_pengajuan' : 'verifikasi_pengajuan';

        return view($viewPath, compact('pengajuan'));
    }

    /**
     * Menyetujui pengajuan perubahan data warga
     */
    public function setujuiPengajuan(Request $request, $id)
    {
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
        $selected_zona = ($rumah && !empty($rumah->status_zona_tsunami)) ? array_map('trim', explode(',', $rumah->status_zona_tsunami)) : [];

        $viewPath = view()->exists('admin.edit_warga') ? 'admin.edit_warga' : 'edit_warga';

        return view($viewPath, compact(
            'warga', 'provinsi', 'kota', 'rt', 'rw',
            'kabupaten_options', 'kecamatan_options', 'desa_options',
            'rumah', 'selected_zona'
        ));
    }

    /**
     * Memperbarui data warga di database
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'nik' => 'required|numeric|digits:16',
            'nama_lengkap' => 'required|string|max:255',
            'tempat_lahir' => 'required',
            'tanggal_lahir' => 'required|date',
            'jenis_kelamin' => 'required|in:L,P',
            'nomor_kk' => 'required|numeric|digits:16',
        ]);

        try {
            DB::transaction(function () use ($request, $id) {
                $oldWarga = DB::table('warga')->where('id_warga', $id)->first();
                if (!$oldWarga) throw new \Exception("Data warga tidak ditemukan.");

                $id_rumah = $oldWarga->id_rumah;
                $id_kk_lama = $oldWarga->id_kk;
                $statusZonaTsunami = $request->has('status_zona_tsunami') ? implode(', ', (array) $request->status_zona_tsunami) : null;

                if ($id_rumah) {
                    DB::table('rumah')->where('id_rumah', $id_rumah)->update([
                        'alamat_lengkap' => $request->alamat_lengkap, 'id_rt' => $request->id_rt, 'id_rw' => $request->id_rw,
                        'id_desa' => $request->desa, 'id_kecamatan' => $request->kecamatan, 'id_kabupaten' => $request->kabupaten,
                        'id_provinsi' => $request->provinsi, 'jenis_konstruksi' => $request->jenis_konstruksi,
                        'status_zona_tsunami' => $statusZonaTsunami, 'latitude' => $request->latitude, 'longitude' => $request->longitude,
                        'updated_at' => now(),
                    ]);
                } else {
                    $id_rumah = DB::table('rumah')->insertGetId([
                        'alamat_lengkap' => $request->alamat_lengkap, 'id_rt' => $request->id_rt, 'id_rw' => $request->id_rw,
                        'id_desa' => $request->desa, 'id_kecamatan' => $request->kecamatan, 'id_kabupaten' => $request->kabupaten,
                        'id_provinsi' => $request->provinsi, 'jenis_konstruksi' => $request->jenis_konstruksi,
                        'status_zona_tsunami' => $statusZonaTsunami, 'latitude' => $request->latitude, 'longitude' => $request->longitude,
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
                } elseif (!$isKepalaKeluarga && $id_kk_lama && $id_kk_lama != $id_kk) {
                    DB::table('keluarga')->where('id_kk', $id_kk_lama)->where('id_kepala_keluarga', $id)->update(['id_kepala_keluarga' => null, 'updated_at' => now()]);
                }
            });

            return redirect()->route('warga.edit', $id)->with('success', 'Data penduduk berhasil diperbarui!');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Gagal memperbarui data: ' . $e->getMessage());
        }
    }

    /**
     * Menghapus data warga
     */
    public function destroy($id)
    {
        try {
            DB::table('warga')->where('id_warga', $id)->delete();
            return redirect()->back()->with('success', 'Data warga berhasil dihapus!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus data warga: ' . $e->getMessage());
        }
    }
}