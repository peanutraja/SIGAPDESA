<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\WilayahAkses;

class PendudukController extends Controller
{
    /**
     * Input data penduduk hanya untuk RT (wilayahnya dikunci) dan GMLS.
     * Mengembalikan [akses, wilayahRt] atau string pesan penolakan.
     */
    private function aksesInput()
    {
        $akses = WilayahAkses::dariSession();
        if ($akses->isGlobal()) {
            return [$akses, null];
        }
        if (!$akses->isRt()) {
            return 'Hanya Ketua RT yang dapat menginput data penduduk.';
        }
        $wilayahRt = $akses->wilayahRt();
        if (!$wilayahRt) {
            return 'Wilayah akun RT Anda belum lengkap (desa/RW/RT). Hubungi GMLS untuk memperbaiki akun.';
        }
        return [$akses, $wilayahRt];
    }

    /** Nomor RT/RW dari CSV ("1", "01", "001") → id di tabel rt/rw. */
    private function cariIdNomor(string $tabel, $nilai)
    {
        $nilai = trim((string) $nilai);
        if ($nilai === '' || !ctype_digit($nilai)) {
            return null;
        }
        return DB::table($tabel)->where("nomor_{$tabel}", str_pad((string)(int) $nilai, 3, '0', STR_PAD_LEFT))->value("id_{$tabel}");
    }

    public function create()
    {
        $hak = $this->aksesInput();
        if (is_string($hak)) {
            return redirect()->route('dashboard')->with('error', $hak);
        }
        [, $wilayahRt] = $hak;

        $provinsi = DB::table('provinsi')->orderBy('nama_provinsi', 'asc')->get();
        $kabupaten = DB::table('kabupaten')->orderBy('nama_kabupaten', 'asc')->get();
        $rt = DB::table('rt')->orderBy('nomor_rt', 'asc')->get();
        $rw = DB::table('rw')->orderBy('nomor_rw', 'asc')->get();

        return view('admin.input_data', compact('provinsi', 'kabupaten', 'rt', 'rw', 'wilayahRt'));
    }

    public function store(Request $request)
    {
        $hak = $this->aksesInput();
        if (is_string($hak)) {
            return redirect()->route('dashboard')->with('error', $hak);
        }
        [$akses, $wilayahRt] = $hak;

        // Data yang diinput RT selalu masuk ke wilayah RT tersebut
        if ($wilayahRt) {
            $request->merge([
                'provinsi'  => $wilayahRt['id_provinsi'],
                'kabupaten' => $wilayahRt['id_kabupaten'],
                'kecamatan' => $wilayahRt['id_kecamatan'],
                'desa'      => $wilayahRt['id_desa'],
                'id_rw'     => $wilayahRt['id_rw'],
                'id_rt'     => $wilayahRt['id_rt'],
            ]);
        }

        // NIK / KK yang sudah terdaftar di wilayah lain tidak boleh ditimpa
        if (!$akses->isGlobal()) {
            $wargaAda = DB::table('warga')->where('nik', $request->nik)->value('id_warga');
            if ($wargaAda && !$akses->mencakupWarga($wargaAda)) {
                return redirect()->back()->withInput()->with('error', 'NIK ini sudah terdaftar di wilayah RT lain. Hubungi RT terkait atau GMLS.');
            }
            $rumahKk = DB::table('keluarga as k')->join('rumah as r', 'k.id_rumah', '=', 'r.id_rumah')
                ->where('k.nomor_kk', $request->nomor_kk)->first(['r.id_kabupaten', 'r.id_kecamatan', 'r.id_desa', 'r.id_rw', 'r.id_rt']);
            if ($rumahKk && !$akses->mencakup($rumahKk)) {
                return redirect()->back()->withInput()->with('error', 'Nomor KK ini sudah terdaftar di wilayah RT lain. Hubungi RT terkait atau GMLS.');
            }
        }

        $request->validate([
            'desa' => 'required',
            'id_rw' => 'required',
            'id_rt' => 'required',
            'nik' => 'required|numeric|digits:16',
            'nama_lengkap' => 'required|string|max:255',
            'tempat_lahir' => 'required',
            'tanggal_lahir' => 'required|date|before_or_equal:today',
            'jenis_kelamin' => 'required',
            'provinsi' => 'required',
            'kabupaten' => 'required',
            'kecamatan' => 'required',
            'alamat_lengkap' => 'required',
            'nomor_kk' => 'required|numeric|digits:16',
            // Titik rumah wajib: dipakai peta & penentuan zona InaRISK
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ], [
            'latitude.required' => 'Tandai lokasi rumah di peta terlebih dahulu.',
            'longitude.required' => 'Tandai lokasi rumah di peta terlebih dahulu.',
        ]);

        try {
            DB::transaction(function () use ($request) {
                $id_kk = null;
                $id_rumah = null;

                if ($request->nomor_kk) {
                    $existingKk = DB::table('keluarga')->where('nomor_kk', $request->nomor_kk)->first();
                    if ($existingKk) {
                        $id_kk = $existingKk->id_kk;
                        $id_rumah = $existingKk->id_rumah;
                    }
                }

                // Normalisasi Koordinat Input Manual
                $lat = $request->latitude ? trim($request->latitude) : null;
                $lng = $request->longitude ? trim($request->longitude) : null;

                // Auto-Correct jika Latitude & Longitude tertukar saat diinput
                if (is_numeric($lat) && is_numeric($lng)) {
                    if ($lat > 50 && $lng < 10) {
                        $temp = $lat;
                        $lat = $lng;
                        $lng = $temp;
                    }
                }

                if (!$id_rumah) {
                    $id_rumah = DB::table('rumah')->insertGetId([
                        'alamat_lengkap' => $request->alamat_lengkap,
                        'id_rt' => $request->id_rt,
                        'id_rw' => $request->id_rw,
                        'id_desa' => $request->desa,
                        'id_kecamatan' => $request->kecamatan,
                        'id_kabupaten' => $request->kabupaten,
                        'id_provinsi' => $request->provinsi,
                        'jenis_konstruksi' => $request->jenis_konstruksi,
                        // Zona dihitung otomatis dari InaRISK BNPB (lihat InaRiskService)
                        'status_zona_tsunami' => null,
                        'latitude' => $lat,
                        'longitude' => $lng,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    if ($lat && $lng) {
                        DB::table('rumah')->where('id_rumah', $id_rumah)->update([
                            'latitude' => $lat,
                            'longitude' => $lng,
                            'alamat_lengkap' => $request->alamat_lengkap,
                            'id_rt' => $request->id_rt ?: DB::raw('id_rt'),
                            'id_rw' => $request->id_rw ?: DB::raw('id_rw'),
                            'zona_dianalisis_at' => null, // koordinat diperbarui → zona dianalisis ulang
                            'updated_at' => now(),
                        ]);
                    }
                }

                if (!$id_kk && $request->nomor_kk) {
                    $id_kk = DB::table('keluarga')->insertGetId([
                        'nomor_kk' => $request->nomor_kk,
                        'id_rumah' => $id_rumah,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $isKepalaKeluarga = $request->has('is_kepala_keluarga') ? 1 : 0;

                $statusKehamilan = ($request->jenis_kelamin === 'P' && ($request->has('status_kehamilan') || $request->status_kehamilan == 1)) ? 1 : 0;
                $perkiraanLahir = ($statusKehamilan === 1) ? $request->perkiraan_lahir : null;

                $kategoriDisabilitas = $request->kategori_disabilitas ?: 'Tidak Ada';
                $keteranganDisabilitas = ($kategoriDisabilitas !== 'Tidak Ada') ? $request->keterangan_disabilitas : null;

                $existingWarga = DB::table('warga')->where('nik', $request->nik)->first();

                if ($existingWarga) {
                    DB::table('warga')->where('id_warga', $existingWarga->id_warga)->update([
                        'nama_lengkap' => $request->nama_lengkap,
                        'tempat_lahir' => $request->tempat_lahir,
                        'tanggal_lahir' => $request->tanggal_lahir,
                        'jenis_kelamin' => $request->jenis_kelamin,
                        'golongan_darah' => $request->golongan_darah,
                        'agama' => $request->agama,
                        'status_perkawinan' => $request->status_perkawinan,
                        'pekerjaan' => $request->pekerjaan,
                        'nomor_telepon' => $request->nomor_telepon,
                        'status_domisili' => $request->status_domisili,
                        'status_kehamilan' => $statusKehamilan,
                        'perkiraan_lahir' => $perkiraanLahir,
                        'kategori_disabilitas' => $kategoriDisabilitas,
                        'keterangan_disabilitas' => $keteranganDisabilitas,
                        'jenis_penghasilan' => $request->jenis_penghasilan,
                        'nomor_kk' => $request->nomor_kk,
                        'is_kepala_keluarga' => $isKepalaKeluarga,
                        'id_kk' => $id_kk,
                        'alamat_lengkap' => $request->alamat_lengkap,
                        'id_provinsi' => $request->provinsi,
                        'id_kabupaten' => $request->kabupaten,
                        'id_kecamatan' => $request->kecamatan,
                        'id_desa' => $request->desa,
                        'id_rumah' => $id_rumah,
                        'updated_at' => now(),
                    ]);
                    $id_warga = $existingWarga->id_warga;
                } else {
                    $id_warga = DB::table('warga')->insertGetId([
                        'nik' => $request->nik,
                        'nama_lengkap' => $request->nama_lengkap,
                        'tempat_lahir' => $request->tempat_lahir,
                        'tanggal_lahir' => $request->tanggal_lahir,
                        'jenis_kelamin' => $request->jenis_kelamin,
                        'golongan_darah' => $request->golongan_darah,
                        'agama' => $request->agama,
                        'status_perkawinan' => $request->status_perkawinan,
                        'pekerjaan' => $request->pekerjaan,
                        'nomor_telepon' => $request->nomor_telepon,
                        'status_domisili' => $request->status_domisili,
                        'status_kehamilan' => $statusKehamilan,
                        'perkiraan_lahir' => $perkiraanLahir,
                        'kategori_disabilitas' => $kategoriDisabilitas,
                        'keterangan_disabilitas' => $keteranganDisabilitas,
                        'jenis_penghasilan' => $request->jenis_penghasilan,
                        'nomor_kk' => $request->nomor_kk,
                        'is_kepala_keluarga' => $isKepalaKeluarga,
                        'id_kk' => $id_kk,
                        'alamat_lengkap' => $request->alamat_lengkap,
                        'id_provinsi' => $request->provinsi,
                        'id_kabupaten' => $request->kabupaten,
                        'id_kecamatan' => $request->kecamatan,
                        'id_desa' => $request->desa,
                        'id_rumah' => $id_rumah,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                if ($isKepalaKeluarga && $id_kk) {
                    DB::table('keluarga')
                        ->where('id_kk', $id_kk)
                        ->update([
                            'id_kepala_keluarga' => $id_warga,
                            'id_rumah' => $id_rumah,
                            'updated_at' => now(),
                        ]);
                }
            });

            return redirect()->route('penduduk.create')->with('success', 'Data penduduk berhasil disimpan!');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Gagal menyimpan data: ' . $e->getMessage());
        }
    }

    /**
     * DOWNLOAD TEMPLATE CSV BERSIH + 2 CONTOH DATA
     */
    public function downloadTemplateCsv()
    {
        $filename = "template_import_warga_sigapdesa.csv";

        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = [
            'NIK', 'NAMA_LENGKAP', 'TEMPAT_LAHIR', 'TANGGAL_LAHIR', 'JENIS_KELAMIN',
            'GOLONGAN_DARAH', 'AGAMA', 'STATUS_PERKAWINAN', 'PEKERJAAN', 'NOMOR_TELEPON',
            'JENIS_PENGHASILAN', 'STATUS_DOMISILI', 'STATUS_KEHAMILAN', 'PERKIRAAN_LAHIR',
            'KATEGORI_DISABILITAS', 'KETERANGAN_DISABILITAS', 'NOMOR_KK', 'IS_KEPALA_KELUARGA',
            'ALAMAT_LENGKAP', 'PROVINSI', 'KABUPATEN', 'KECAMATAN', 'DESA',
            'RT', 'RW', 'JENIS_KONSTRUKSI', 'STATUS_ZONA_TSUNAMI',
            'LATITUDE', 'LONGITUDE'
        ];

        $sampleData = [
            [
                '="3601234567890001"', 'Ahmad Fauzi', 'KABUPATEN LEBAK', '1990-05-15', 'L',
                'O', 'Islam', 'Kawin', 'Wiraswasta', '081234567890',
                'Tetap', 'Menetap', '0', '',
                'Tidak Ada', '', '="3601234567890001"', '1',
                'Jl. Merdeka No. 10', 'BANTEN', 'KABUPATEN LEBAK', 'CIBADAK', 'CIMENTENG',
                '001', '002', 'Permanen', '',
                '="-6.882100"', '="106.234100"'
            ],
            [
                '="3601234567890002"', 'Siti Rahmawati', 'KABUPATEN LEBAK', '1994-08-20', 'P',
                'A', 'Islam', 'Kawin', 'Mengurus Rumah Tangga', '081298765432',
                'Tidak Tetap', 'Menetap', '1', '2026-11-25',
                'Tidak Ada', '', '="3601234567890001"', '0',
                'Jl. Merdeka No. 10', 'BANTEN', 'KABUPATEN LEBAK', 'CIBADAK', 'CIMENTENG',
                '001', '002', 'Permanen', '',
                '="-6.882100"', '="106.234100"'
            ]
        ];

        $callback = function () use ($columns, $sampleData) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM untuk Excel
            fputcsv($file, $columns, ';');

            foreach ($sampleData as $row) {
                fputcsv($file, $row, ';');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * EXPORT SEMUA DATA WARGA KE CSV (MENGGUNAKAN SEMICOLON ';')
     */
    public function exportCsv()
    {
        $filename = "export_data_warga_" . date('Y-m-d_H-i') . ".csv";

        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $query = DB::table('warga as w')
            ->leftJoin('rumah as r', 'w.id_rumah', '=', 'r.id_rumah')
            ->leftJoin('desa as d', 'w.id_desa', '=', 'd.id_desa')
            ->leftJoin('kecamatan as c', 'w.id_kecamatan', '=', 'c.id_kecamatan')
            ->leftJoin('kabupaten as kb', 'w.id_kabupaten', '=', 'kb.id_kabupaten')
            ->leftJoin('provinsi as p', 'w.id_provinsi', '=', 'p.id_provinsi')
            ->leftJoin('rt', 'r.id_rt', '=', 'rt.id_rt')
            ->leftJoin('rw', 'r.id_rw', '=', 'rw.id_rw')
            ->select(
                'w.nik', 'w.nama_lengkap', 'w.tempat_lahir', 'w.tanggal_lahir', 'w.jenis_kelamin',
                'w.golongan_darah', 'w.agama', 'w.status_perkawinan', 'w.pekerjaan', 'w.nomor_telepon',
                'w.jenis_penghasilan', 'w.status_domisili', 'w.status_kehamilan', 'w.perkiraan_lahir',
                'w.kategori_disabilitas', 'w.keterangan_disabilitas', 'w.nomor_kk', 'w.is_kepala_keluarga',
                'r.alamat_lengkap', 'p.nama_provinsi', 'kb.nama_kabupaten', 'c.nama_kecamatan', 'd.nama_desa',
                'rt.nomor_rt', 'rw.nomor_rw', 'r.jenis_konstruksi', 'r.status_zona_tsunami', 'r.zona_dianalisis_at',
                'r.latitude', 'r.longitude'
            );

        WilayahAkses::dariSession()->batasi($query, 'r');

        $warga = $query->orderBy('w.nama_lengkap', 'asc')->get();

        $columns = [
            'NIK', 'NAMA_LENGKAP', 'TEMPAT_LAHIR', 'TANGGAL_LAHIR', 'JENIS_KELAMIN',
            'GOLONGAN_DARAH', 'AGAMA', 'STATUS_PERKAWINAN', 'PEKERJAAN', 'NOMOR_TELEPON',
            'JENIS_PENGHASILAN', 'STATUS_DOMISILI', 'STATUS_KEHAMILAN', 'PERKIRAAN_LAHIR',
            'KATEGORI_DISABILITAS', 'KETERANGAN_DISABILITAS', 'NOMOR_KK', 'IS_KEPALA_KELUARGA',
            'ALAMAT_LENGKAP', 'PROVINSI', 'KABUPATEN', 'KECAMATAN', 'DESA',
            'RT', 'RW', 'JENIS_KONSTRUKSI', 'STATUS_ZONA_TSUNAMI',
            'LATITUDE', 'LONGITUDE'
        ];

        $callback = function () use ($warga, $columns) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($file, $columns, ';');

            foreach ($warga as $row) {
                // Bersihkan enter dari alamat agar tidak merusak baris CSV
                $alamatClean = str_replace(["\r", "\n"], " ", $row->alamat_lengkap ?? '');

                fputcsv($file, [
                    '="' . $row->nik . '"',
                    $row->nama_lengkap,
                    $row->tempat_lahir,
                    $row->tanggal_lahir,
                    $row->jenis_kelamin,
                    $row->golongan_darah,
                    $row->agama,
                    $row->status_perkawinan,
                    $row->pekerjaan,
                    $row->nomor_telepon,
                    $row->jenis_penghasilan,
                    $row->status_domisili,
                    $row->status_kehamilan ? '1' : '0',
                    $row->perkiraan_lahir,
                    $row->kategori_disabilitas ?? 'Tidak Ada',
                    $row->keterangan_disabilitas,
                    '="' . $row->nomor_kk . '"',
                    $row->is_kepala_keluarga ? '1' : '0',
                    $alamatClean,
                    $row->nama_provinsi,
                    $row->nama_kabupaten,
                    $row->nama_kecamatan,
                    $row->nama_desa,
                    $row->nomor_rt,
                    $row->nomor_rw,
                    $row->jenis_konstruksi,
                    // Hanya zona hasil analisis InaRISK; zona lama yang diisi manual tidak diekspor
                    $row->zona_dianalisis_at ? $row->status_zona_tsunami : '',
                    // Format ="..." agar Excel (format angka Indonesia) tidak mengubah koordinat
                    $row->latitude !== null && $row->latitude !== '' ? '="' . $row->latitude . '"' : '',
                    $row->longitude !== null && $row->longitude !== '' ? '="' . $row->longitude . '"' : '',
                ], ';');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * IMPORT CSV DENGAN PEMERIKSAAN & AUTO-CORRECT KOORDINAT LAT/LNG
     */
    public function importCsv(Request $request)
    {
        $hak = $this->aksesInput();
        if (is_string($hak)) {
            return redirect()->route('dashboard')->with('error', $hak);
        }
        [$akses, $wilayahRt] = $hak;

        $request->validate([
            'file_csv' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        try {
            $file = $request->file('file_csv');
            $path = $file->getRealPath();

            $handle = fopen($path, 'r');
            if (!$handle) {
                return redirect()->back()->with('error', 'Gagal membuka file CSV.');
            }

            $bom = fread($handle, 3);
            if ($bom !== "\xEF\xBB\xBF") {
                rewind($handle);
            }

            $firstLine = fgets($handle);
            rewind($handle);
            if ($bom === "\xEF\xBB\xBF") {
                fread($handle, 3);
            }
            $delimiter = (substr_count($firstLine, ';') >= substr_count($firstLine, ',')) ? ';' : ',';

            $header = fgetcsv($handle, 2000, $delimiter);
            if (!$header) {
                fclose($handle);
                return redirect()->back()->with('error', 'File CSV kosong atau format header tidak valid.');
            }

            $headerMap = [];
            foreach ($header as $idx => $colName) {
                $cleanCol = strtoupper(trim(str_replace(["\xEF\xBB\xBF", '"', "'", '='], '', $colName)));
                $headerMap[$cleanCol] = $idx;
            }

            $addedCount = 0;
            $updatedCount = 0;
            $skippedCount = 0;
            $barisTidakValid = [];
            $baris = 1; // baris 1 = header

            DB::beginTransaction();

            while (($row = fgetcsv($handle, 2000, $delimiter)) !== false) {
                $baris++;
                if (empty($row) || count($row) < 2) continue;

                $getValue = function($key) use ($headerMap, $row) {
                    if (isset($headerMap[$key]) && isset($row[$headerMap[$key]])) {
                        $val = trim($row[$headerMap[$key]]);
                        if (str_starts_with($val, '="') && str_ends_with($val, '"')) {
                            $val = substr($val, 2, -1);
                        }
                        return ltrim(rtrim($val, '"'), "'");
                    }
                    return null;
                };

                $nik          = $getValue('NIK');
                $nama_lengkap = $getValue('NAMA_LENGKAP');

                if (empty($nik) || empty($nama_lengkap)) {
                    continue;
                }

                $tempat_lahir          = $getValue('TEMPAT_LAHIR');
                $tanggal_lahir         = $getValue('TANGGAL_LAHIR');

                // Baris dengan NIK tidak 16 digit atau tanggal lahir tidak valid/di masa depan tidak disimpan
                $tglLahirTs = $tanggal_lahir ? strtotime($tanggal_lahir) : false;
                if (!preg_match('/^\d{16}$/', $nik) || ($tanggal_lahir && ($tglLahirTs === false || $tglLahirTs > time()))) {
                    $barisTidakValid[] = $baris;
                    continue;
                }
                $jenis_kelamin         = strtoupper($getValue('JENIS_KELAMIN') ?? 'L');
                $golongan_darah        = $getValue('GOLONGAN_DARAH') ?? '-';
                $agama                 = $getValue('AGAMA');
                $status_perkawinan     = $getValue('STATUS_PERKAWINAN');
                $pekerjaan             = $getValue('PEKERJAAN');
                $nomor_telepon         = $getValue('NOMOR_TELEPON');
                $jenis_penghasilan     = $getValue('JENIS_PENGHASILAN');
                $status_domisili       = $getValue('STATUS_DOMISILI') ?? 'Menetap';
                $status_kehamilan      = (in_array($getValue('STATUS_KEHAMILAN'), ['1', 'YA', 'Hamil', '1.0'])) ? 1 : 0;
                $perkiraan_lahir       = $getValue('PERKIRAAN_LAHIR');
                $kategori_disabilitas  = $getValue('KATEGORI_DISABILITAS') ?: 'Tidak Ada';
                $keterangan_disabilitas= $getValue('KETERANGAN_DISABILITAS');
                $nomor_kk              = $getValue('NOMOR_KK');
                $is_kepala_keluarga    = (in_array($getValue('IS_KEPALA_KELUARGA'), ['1', 'YA', '1.0'])) ? 1 : 0;
                $alamat_lengkap        = $getValue('ALAMAT_LENGKAP') ?? 'Alamat Belum Diisi';
                
                $provinsi_val  = $getValue('PROVINSI');
                $kabupaten_val = $getValue('KABUPATEN');
                $kecamatan_val = $getValue('KECAMATAN');
                $desa_val      = $getValue('DESA');
                $rt_val        = $getValue('RT');
                $rw_val        = $getValue('RW');

                $jenis_konstruksi    = $getValue('JENIS_KONSTRUKSI');
                // STATUS_ZONA_TSUNAMI di CSV diabaikan: zona dihitung otomatis dari InaRISK BNPB
                
                $latitude  = $getValue('LATITUDE');
                $longitude = $getValue('LONGITUDE');

                // Validasi & Auto-Correct Latitude/Longitude jika tertukar atau non-numerik
                if (!is_numeric($latitude) || !is_numeric($longitude)) {
                    $latNum = filter_var($latitude, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                    $lngNum = filter_var($longitude, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                    $latitude = is_numeric($latNum) ? $latNum : null;
                    $longitude = is_numeric($lngNum) ? $lngNum : null;
                }

                // Otomatis menukar jika Latitude & Longitude terbalik
                if (is_numeric($latitude) && is_numeric($longitude)) {
                    if ($latitude > 50 && $longitude < 10) {
                        $temp = $latitude;
                        $latitude = $longitude;
                        $longitude = $temp;
                    }
                }

                // Mapping ID Wilayah
                if ($wilayahRt) {
                    // Akun RT: kolom wilayah di CSV diabaikan, semua baris masuk ke wilayah RT ini
                    $id_provinsi  = $wilayahRt['id_provinsi'];
                    $id_kabupaten = $wilayahRt['id_kabupaten'];
                    $id_kecamatan = $wilayahRt['id_kecamatan'];
                    $id_desa      = $wilayahRt['id_desa'];
                    $id_rw        = $wilayahRt['id_rw'];
                    $id_rt        = $wilayahRt['id_rt'];
                } else {
                    // Nama dicari berjenjang (kabupaten di provinsi, kecamatan di kabupaten, desa di kecamatan)
                    $id_provinsi = is_numeric($provinsi_val) ? $provinsi_val : DB::table('provinsi')->where('nama_provinsi', 'LIKE', "%$provinsi_val%")->value('id_provinsi');
                    $id_kabupaten = is_numeric($kabupaten_val) ? $kabupaten_val : DB::table('kabupaten')->when($id_provinsi, fn($q) => $q->where('id_provinsi', $id_provinsi))->where('nama_kabupaten', 'LIKE', "%$kabupaten_val%")->value('id_kabupaten');
                    $id_kecamatan = is_numeric($kecamatan_val) ? $kecamatan_val : DB::table('kecamatan')->when($id_kabupaten, fn($q) => $q->where('id_kabupaten', $id_kabupaten))->where('nama_kecamatan', 'LIKE', "%$kecamatan_val%")->value('id_kecamatan');
                    $id_desa = is_numeric($desa_val) ? $desa_val : DB::table('desa')->when($id_kecamatan, fn($q) => $q->where('id_kecamatan', $id_kecamatan))->where('nama_desa', 'LIKE', "%$desa_val%")->value('id_desa');

                    // RT/RW di CSV adalah NOMOR ("001"/"1"), bukan id
                    $id_rt = $this->cariIdNomor('rt', $rt_val);
                    $id_rw = $this->cariIdNomor('rw', $rw_val);
                }

                // NIK / KK yang sudah terdaftar di wilayah RT lain tidak ditimpa oleh RT
                if (!$akses->isGlobal()) {
                    $wargaAda = DB::table('warga')->where('nik', $nik)->value('id_warga');
                    $rumahKk = !empty($nomor_kk) ? DB::table('keluarga as k')->join('rumah as r', 'k.id_rumah', '=', 'r.id_rumah')
                        ->where('k.nomor_kk', $nomor_kk)->first(['r.id_kabupaten', 'r.id_kecamatan', 'r.id_desa', 'r.id_rw', 'r.id_rt']) : null;
                    if (($wargaAda && !$akses->mencakupWarga($wargaAda)) || ($rumahKk && !$akses->mencakup($rumahKk))) {
                        $skippedCount++;
                        continue;
                    }
                }

                // 1. Dapatkan / Buat Record Rumah
                $id_rumah = null;
                $id_kk = null;

                if (!empty($nomor_kk)) {
                    $existingKk = DB::table('keluarga')->where('nomor_kk', $nomor_kk)->first();
                    if ($existingKk) {
                        $id_kk = $existingKk->id_kk;
                        $id_rumah = $existingKk->id_rumah;
                    }
                }

                if (!$id_rumah) {
                    $id_rumah = DB::table('rumah')->insertGetId([
                        'alamat_lengkap'      => $alamat_lengkap,
                        'id_rt'               => $id_rt,
                        'id_rw'               => $id_rw,
                        'id_desa'             => $id_desa,
                        'id_kecamatan'        => $id_kecamatan,
                        'id_kabupaten'        => $id_kabupaten,
                        'id_provinsi'         => $id_provinsi,
                        'jenis_konstruksi'    => $jenis_konstruksi,
                        'status_zona_tsunami' => null,
                        'latitude'            => $latitude,
                        'longitude'           => $longitude,
                        'created_at'          => now(),
                        'updated_at'          => now(),
                    ]);
                } else if (!empty($latitude) && !empty($longitude)) {
                    DB::table('rumah')->where('id_rumah', $id_rumah)->update([
                        'latitude'  => $latitude,
                        'longitude' => $longitude,
                        'zona_dianalisis_at' => null,
                        'updated_at' => now(),
                    ]);
                }

                // 2. Dapatkan / Buat Record Keluarga (KK)
                if (!$id_kk && !empty($nomor_kk)) {
                    $id_kk = DB::table('keluarga')->insertGetId([
                        'nomor_kk'   => $nomor_kk,
                        'id_rumah'   => $id_rumah,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                // 3. Simpan / Update Data Warga
                $existingWarga = DB::table('warga')->where('nik', $nik)->first();

                if ($existingWarga) {
                    DB::table('warga')->where('id_warga', $existingWarga->id_warga)->update([
                        'nama_lengkap'           => $nama_lengkap,
                        'tempat_lahir'           => $tempat_lahir,
                        'tanggal_lahir'          => $tanggal_lahir ? date('Y-m-d', strtotime($tanggal_lahir)) : null,
                        'jenis_kelamin'          => $jenis_kelamin,
                        'golongan_darah'         => $golongan_darah,
                        'agama'                  => $agama,
                        'status_perkawinan'      => $status_perkawinan,
                        'pekerjaan'              => $pekerjaan,
                        'nomor_telepon'          => $nomor_telepon,
                        'status_domisili'        => $status_domisili,
                        'status_kehamilan'       => $status_kehamilan,
                        'perkiraan_lahir'        => $perkiraan_lahir ? date('Y-m-d', strtotime($perkiraan_lahir)) : null,
                        'kategori_disabilitas'   => $kategori_disabilitas,
                        'keterangan_disabilitas' => $keterangan_disabilitas,
                        'jenis_penghasilan'      => $jenis_penghasilan,
                        'nomor_kk'               => $nomor_kk,
                        'is_kepala_keluarga'     => $is_kepala_keluarga,
                        'id_kk'                  => $id_kk,
                        'alamat_lengkap'         => $alamat_lengkap,
                        'id_provinsi'            => $id_provinsi,
                        'id_kabupaten'           => $id_kabupaten,
                        'id_kecamatan'           => $id_kecamatan,
                        'id_desa'                => $id_desa,
                        'id_rumah'               => $id_rumah,
                        'updated_at'             => now(),
                    ]);
                    $id_warga = $existingWarga->id_warga;
                    $updatedCount++;
                } else {
                    $id_warga = DB::table('warga')->insertGetId([
                        'nik'                    => $nik,
                        'nama_lengkap'           => $nama_lengkap,
                        'tempat_lahir'           => $tempat_lahir,
                        'tanggal_lahir'          => $tanggal_lahir ? date('Y-m-d', strtotime($tanggal_lahir)) : null,
                        'jenis_kelamin'          => $jenis_kelamin,
                        'golongan_darah'         => $golongan_darah,
                        'agama'                  => $agama,
                        'status_perkawinan'      => $status_perkawinan,
                        'pekerjaan'              => $pekerjaan,
                        'nomor_telepon'          => $nomor_telepon,
                        'status_domisili'        => $status_domisili,
                        'status_kehamilan'       => $status_kehamilan,
                        'perkiraan_lahir'        => $perkiraan_lahir ? date('Y-m-d', strtotime($perkiraan_lahir)) : null,
                        'kategori_disabilitas'   => $kategori_disabilitas,
                        'keterangan_disabilitas' => $keterangan_disabilitas,
                        'jenis_penghasilan'      => $jenis_penghasilan,
                        'nomor_kk'               => $nomor_kk,
                        'is_kepala_keluarga'     => $is_kepala_keluarga,
                        'id_kk'                  => $id_kk,
                        'alamat_lengkap'         => $alamat_lengkap,
                        'id_provinsi'            => $id_provinsi,
                        'id_kabupaten'           => $id_kabupaten,
                        'id_kecamatan'           => $id_kecamatan,
                        'id_desa'                => $id_desa,
                        'id_rumah'               => $id_rumah,
                        'created_at'             => now(),
                        'updated_at'             => now(),
                    ]);
                    $addedCount++;
                }

                if ($is_kepala_keluarga && $id_kk) {
                    DB::table('keluarga')->where('id_kk', $id_kk)->update([
                        'id_kepala_keluarga' => $id_warga,
                        'id_rumah'           => $id_rumah,
                        'updated_at'         => now(),
                    ]);
                }
            }

            DB::commit();
            fclose($handle);

            $msg = "Import CSV Berhasil! {$addedCount} data warga baru ditambahkan";
            if ($updatedCount > 0) {
                $msg .= ", dan {$updatedCount} data warga diperbarui (karena NIK terdaftar sebelumnya).";
            } else {
                $msg .= ".";
            }
            if ($skippedCount > 0) {
                $msg .= " {$skippedCount} baris dilewati karena NIK/KK-nya sudah terdaftar di wilayah RT lain.";
            }
            if ($barisTidakValid) {
                $msg .= " " . count($barisTidakValid) . " baris dilewati karena NIK tidak 16 digit atau tanggal lahir tidak valid/melebihi hari ini (baris ke-" . implode(', ', $barisTidakValid) . ").";
            }

            return redirect()->back()->with('success', $msg);

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Terjadi kesalahan saat import CSV: ' . $e->getMessage());
        }
    }
}