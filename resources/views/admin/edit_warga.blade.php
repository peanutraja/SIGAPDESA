@extends('layouts.admin')

@section('title', 'Edit Data Penduduk - SIGAPDESA')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .select2-container .select2-selection--single {
        height: 42px !important;
        border-color: #e5e7eb !important;
        border-radius: 0.75rem !important;
        padding: 6px 12px !important;
        background-color: #f9fafb !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 40px !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #111827 !important;
        line-height: 26px !important;
        font-weight: 500 !important;
    }
</style>
@endpush

@section('content')
<div class="max-w-7xl mx-auto space-y-4 sm:space-y-6">

    <!-- HEADER & AKSI UTAMA -->
    <div class="bg-white rounded-2xl p-4 sm:p-6 border border-gray-200 shadow-sm flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1 flex-wrap">
                <span class="badge bg-brandRed text-white font-bold text-xs uppercase px-3 py-1">Mode Edit Pak RT / Admin</span>
                <span class="text-xs text-gray-500 font-medium">NIK: {{ $warga->nik }}</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-brandDark tracking-tight uppercase">Edit Data Penduduk: {{ $warga->nama_lengkap }}</h1>
            <p class="text-xs text-gray-500">Perbarui informasi identitas, domisili, rumah, serta status kelompok rentan warga ini.</p>
        </div>

        <div class="flex items-center gap-3 w-full sm:w-auto">
            <a href="{{ route('warga.index') }}" class="btn bg-gray-100 hover:bg-gray-200 text-brandDark border border-gray-200 rounded-xl font-bold text-xs gap-2 w-full sm:w-auto justify-center">
                <i class="fas fa-arrow-left"></i> Kembali ke Daftar Warga
            </a>
        </div>
    </div>

    <!-- NOTIFIKASI ALERT -->
    @if(session('success'))
        <div class="alert alert-success bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl text-xs font-semibold p-4 flex items-center gap-2">
            <i class="fas fa-check-circle text-emerald-600"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-error bg-red-50 border border-red-200 text-brandRed rounded-2xl text-xs font-semibold p-4 flex items-center gap-2">
            <i class="fas fa-exclamation-triangle text-brandRed"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- FORM EDIT PENDUDUK -->
    <form method="POST" action="{{ route('warga.update', $warga->id_warga ?? $warga->id) }}" class="space-y-4 sm:space-y-6">
        @csrf
        @method('PUT')

        <!-- 1. DATA IDENTITAS -->
        <div class="card bg-white border border-gray-200 shadow-sm rounded-2xl p-4 sm:p-6 space-y-4">
            <div class="flex items-center gap-2 border-b border-gray-100 pb-3">
                <div class="w-8 h-8 rounded-xl bg-red-50 text-brandRed flex items-center justify-center font-bold text-sm">1</div>
                <h3 class="font-bold text-brandDark text-base uppercase tracking-wide">Data Identitas Diri</h3>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                <div>
                    <label class="label text-xs font-bold uppercase text-gray-600">NIK (Nomor Induk Kependudukan) <span class="text-brandRed">*</span></label>
                    <input type="text" name="nik" value="{{ old('nik', $warga->nik) }}" maxlength="16" required
                        class="input input-bordered w-full bg-gray-50 focus:bg-white text-xs font-medium" placeholder="16 digit NIK">
                </div>

                <div>
                    <label class="label text-xs font-bold uppercase text-gray-600">Nama Lengkap <span class="text-brandRed">*</span></label>
                    <input type="text" name="nama_lengkap" value="{{ old('nama_lengkap', $warga->nama_lengkap) }}" required
                        class="input input-bordered w-full bg-gray-50 focus:bg-white text-xs font-medium" placeholder="Sesuai KTP / KK">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
                <div>
                    <label class="label text-xs font-bold uppercase text-gray-600">Tempat Lahir <span class="text-brandRed">*</span></label>
                    <select name="tempat_lahir" required class="select2-search w-full bg-gray-50">
                        <option value="">-- Pilih Kota/Kabupaten --</option>
                        @foreach($kota as $k)
                            <option value="{{ $k->nama_kabupaten }}" {{ old('tempat_lahir', $warga->tempat_lahir) == $k->nama_kabupaten ? 'selected' : '' }}>
                                {{ $k->nama_kabupaten }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="label text-xs font-bold uppercase text-gray-600">Tanggal Lahir <span class="text-brandRed">*</span></label>
                    <input type="date" name="tanggal_lahir" value="{{ old('tanggal_lahir', $warga->tanggal_lahir) }}" required class="input input-bordered w-full bg-gray-50 text-xs font-medium">
                </div>

                <div>
                    <label class="label text-xs font-bold uppercase text-gray-600">Jenis Kelamin <span class="text-brandRed">*</span></label>
                    <select name="jenis_kelamin" id="jenis_kelamin" onchange="handleJenisKelaminChange()" required class="select select-bordered w-full bg-gray-50 text-xs font-semibold">
                        <option value="L" {{ old('jenis_kelamin', $warga->jenis_kelamin) == 'L' ? 'selected' : '' }}>Laki-laki</option>
                        <option value="P" {{ old('jenis_kelamin', $warga->jenis_kelamin) == 'P' ? 'selected' : '' }}>Perempuan</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- 2. DATA PRIBADI & PEKERJAAN -->
        <div class="card bg-white border border-gray-200 shadow-sm rounded-2xl p-4 sm:p-6 space-y-4">
            <div class="flex items-center gap-2 border-b border-gray-100 pb-3">
                <div class="w-8 h-8 rounded-xl bg-red-50 text-brandRed flex items-center justify-center font-bold text-sm">2</div>
                <h3 class="font-bold text-brandDark text-base uppercase tracking-wide">Data Pribadi & Pekerjaan</h3>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
                <div>
                    <label class="label text-xs font-bold uppercase text-gray-600">Golongan Darah <span class="text-brandRed">*</span></label>
                    <select name="golongan_darah" class="select select-bordered w-full bg-gray-50 text-xs font-semibold" required>
                        @foreach(['A','B','AB','O','-'] as $gol)
                            <option value="{{ $gol }}" {{ old('golongan_darah', $warga->golongan_darah) == $gol ? 'selected' : '' }}>{{ $gol == '-' ? 'Tidak Tahu' : $gol }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="label text-xs font-bold uppercase text-gray-600">Agama</label>
                    <select name="agama" class="select2-search w-full bg-gray-50">
                        @foreach(['Islam','Kristen','Katolik','Hindu','Buddha','Konghucu'] as $ag)
                            <option value="{{ $ag }}" {{ old('agama', $warga->agama) == $ag ? 'selected' : '' }}>{{ $ag }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="label text-xs font-bold uppercase text-gray-600">Status Perkawinan</label>
                    <select name="status_perkawinan" class="select select-bordered w-full bg-gray-50 text-xs font-semibold">
                        @foreach(['Belum Kawin','Kawin','Cerai Hidup','Cerai Mati'] as $sp)
                            <option value="{{ $sp }}" {{ old('status_perkawinan', $warga->status_perkawinan) == $sp ? 'selected' : '' }}>{{ $sp }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
                <div>
                    <label class="label text-xs font-bold uppercase text-gray-600">Nomor Telepon / WA</label>
                    <input type="tel" name="nomor_telepon" value="{{ old('nomor_telepon', $warga->nomor_telepon) }}" class="input input-bordered w-full bg-gray-50 text-xs font-medium" placeholder="08xxxxxxxxxx">
                </div>

                <div>
                    <label class="label text-xs font-bold uppercase text-gray-600">Pekerjaan Utama <span class="text-brandRed">*</span></label>
                    <select name="pekerjaan" required class="select2-search w-full bg-gray-50">
                        @foreach([
                            'Belum/Tidak Bekerja','Pelajar/Mahasiswa','Karyawan Honorer','Pegawai Negeri Sipil',
                            'Tentara Nasional Indonesia','Kepolisian RI','Karyawan Swasta','Buruh Harian',
                            'Perdagangan','Petani/Pekebun','Nelayan/Perikanan','Penata Busana','Guru','Perawat',
                            'Bidan','Dokter','Sopir','Driver Online','Mengurus Rumah Tangga','Wiraswasta','Pensiunan','Lainnya'
                        ] as $pek)
                            <option value="{{ $pek }}" {{ old('pekerjaan', $warga->pekerjaan) == $pek ? 'selected' : '' }}>{{ $pek }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="label text-xs font-bold uppercase text-gray-600">Jenis Penghasilan</label>
                    <select name="jenis_penghasilan" class="select select-bordered w-full bg-gray-50 text-xs font-semibold">
                        <option value="Tetap" {{ old('jenis_penghasilan', $warga->jenis_penghasilan) == 'Tetap' ? 'selected' : '' }}>Tetap</option>
                        <option value="Tidak Tetap" {{ old('jenis_penghasilan', $warga->jenis_penghasilan) == 'Tidak Tetap' ? 'selected' : '' }}>Tidak Tetap</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="label text-xs font-bold uppercase text-gray-600">Status Domisili Kependudukan <span class="text-brandRed">*</span></label>
                <select name="status_domisili" class="select select-bordered w-full bg-gray-50 text-xs font-semibold" required>
                    @foreach(['Menetap','Kerja di Luar','Pendatang','Tinggal Sementara'] as $dom)
                        <option value="{{ $dom }}" {{ old('status_domisili', $warga->status_domisili) == $dom ? 'selected' : '' }}>{{ $dom }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- 3. DATA KESEHATAN & DISABILITAS -->
        <div class="card bg-white border border-gray-200 shadow-sm rounded-2xl p-4 sm:p-6 space-y-4">
            <div class="flex items-center gap-2 border-b border-gray-100 pb-3">
                <div class="w-8 h-8 rounded-xl bg-red-50 text-brandRed flex items-center justify-center font-bold text-sm">3</div>
                <h3 class="font-bold text-brandDark text-base uppercase tracking-wide">Status Kesehatan & Kelompok Rentan</h3>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                <div id="kehamilan_wrapper" class="flex items-center pt-2">
                    <label class="cursor-pointer label gap-3 justify-start">
                        <input type="checkbox" name="status_kehamilan" id="status_kehamilan" value="1" class="checkbox checkbox-error"
                            onchange="togglePerkiraanLahir()" {{ old('status_kehamilan', $warga->status_kehamilan) == 1 ? 'checked' : '' }}>
                        <span class="label-text font-bold text-xs text-brandDark uppercase">Sedang Dalam Kondisi Hamil</span>
                    </label>
                </div>

                <div id="perkiraan_lahir_group" class="{{ old('status_kehamilan', $warga->status_kehamilan) == 1 ? '' : 'hidden' }}">
                    <label class="label text-xs font-bold uppercase text-gray-600">Perkiraan Lahir (HPL)</label>
                    <input type="date" name="perkiraan_lahir" id="perkiraan_lahir" value="{{ old('perkiraan_lahir', $warga->perkiraan_lahir) }}" class="input input-bordered w-full bg-gray-50 text-xs font-medium">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                <div>
                    <label class="label text-xs font-bold uppercase text-gray-600">Kategori Disabilitas</label>
                    <select name="kategori_disabilitas" id="kategori_disabilitas" onchange="toggleKeteranganDisabilitas()" class="select select-bordered w-full bg-gray-50 text-xs font-semibold">
                        <option value="Tidak Ada" {{ old('kategori_disabilitas', $warga->kategori_disabilitas) == 'Tidak Ada' ? 'selected' : '' }}>Tidak Ada</option>
                        <option value="Fisik" {{ old('kategori_disabilitas', $warga->kategori_disabilitas) == 'Fisik' ? 'selected' : '' }}>Fisik (Mobilitas Terbatas)</option>
                        <option value="Intelektual" {{ old('kategori_disabilitas', $warga->kategori_disabilitas) == 'Intelektual' ? 'selected' : '' }}>Intelektual</option>
                        <option value="Mental" {{ old('kategori_disabilitas', $warga->kategori_disabilitas) == 'Mental' ? 'selected' : '' }}>Mental</option>
                        <option value="Sensorik" {{ old('kategori_disabilitas', $warga->kategori_disabilitas) == 'Sensorik' ? 'selected' : '' }}>Sensorik (Pendengaran/Penglihatan)</option>
                    </select>
                </div>

                <div id="keterangan_disabilitas_group" class="{{ old('kategori_disabilitas', $warga->kategori_disabilitas) && old('kategori_disabilitas',$warga->kategori_disabilitas) != 'Tidak Ada' ? '' : 'hidden' }}">
                    <label class="label text-xs font-bold uppercase text-gray-600">Keterangan Alat Bantu / Disabilitas</label>
                    <textarea name="keterangan_disabilitas" id="keterangan_disabilitas" class="textarea textarea-bordered w-full bg-gray-50 text-xs font-medium" rows="2" placeholder="Contoh: Menggunakan kursi roda">{{ old('keterangan_disabilitas', $warga->keterangan_disabilitas) }}</textarea>
                </div>
            </div>
        </div>

        <!-- 4. DATA KARTU KELUARGA -->
        <div class="card bg-white border border-gray-200 shadow-sm rounded-2xl p-4 sm:p-6 space-y-4">
            <div class="flex items-center gap-2 border-b border-gray-100 pb-3">
                <div class="w-8 h-8 rounded-xl bg-red-50 text-brandRed flex items-center justify-center font-bold text-sm">4</div>
                <h3 class="font-bold text-brandDark text-base uppercase tracking-wide">Data Kartu Keluarga (KK)</h3>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                <div>
                    <label class="label text-xs font-bold uppercase text-gray-600">Nomor Kartu Keluarga (KK) <span class="text-brandRed">*</span></label>
                    <input type="text" name="nomor_kk" value="{{ old('nomor_kk', $warga->nomor_kk ?? $warga->nomor_kk_kk ?? '') }}" maxlength="16" required
                        class="input input-bordered w-full bg-gray-50 text-xs font-medium" placeholder="16 digit Nomor KK">
                </div>

                <div class="flex items-center pt-2 sm:pt-6">
                    <label class="cursor-pointer label gap-3 justify-start">
                        <input type="checkbox" name="is_kepala_keluarga" value="1" class="checkbox checkbox-error" {{ old('is_kepala_keluarga', $warga->is_kepala_keluarga) ? 'checked' : '' }}>
                        <span class="label-text font-bold text-xs text-brandDark uppercase">Warga Ini Adalah Kepala Keluarga (KK)</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- 5. DATA ALAMAT & WILAYAH -->
        <div class="card bg-white border border-gray-200 shadow-sm rounded-2xl p-4 sm:p-6 space-y-4">
            <div class="flex items-center gap-2 border-b border-gray-100 pb-3">
                <div class="w-8 h-8 rounded-xl bg-red-50 text-brandRed flex items-center justify-center font-bold text-sm">5</div>
                <h3 class="font-bold text-brandDark text-base uppercase tracking-wide">Data Alamat & Wilayah Domisili</h3>
            </div>

            <div>
                <label class="label text-xs font-bold uppercase text-gray-600">Alamat Lengkap (Jalan/Gang/Nomor Rumah) <span class="text-brandRed">*</span></label>
                <textarea name="alamat_lengkap" rows="2" required class="textarea textarea-bordered w-full bg-gray-50 text-xs font-medium" placeholder="Contoh: Jl. Mawar No. 12">{{ old('alamat_lengkap', $warga->alamat_lengkap ?? $rumah->alamat_lengkap ?? '') }}</textarea>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
                <div>
                    <label class="label text-xs font-bold uppercase text-gray-600">Provinsi <span class="text-brandRed">*</span></label>
                    <select name="provinsi" id="provinsi" required class="select2-search w-full bg-gray-50">
                        <option value="">-- Pilih Provinsi --</option>
                        @foreach($provinsi as$p)
                            <option value="{{ $p->id_provinsi }}" {{ old('provinsi', $warga->id_provinsi ?? '') == $p->id_provinsi ? 'selected' : '' }}>
                                {{ $p->nama_provinsi }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="label text-xs font-bold uppercase text-gray-600">Kabupaten / Kota <span class="text-brandRed">*</span></label>
                    <select name="kabupaten" id="kabupaten" required class="select2-search w-full bg-gray-50">
                        <option value="">-- Pilih Kabupaten --</option>
                        @foreach($kabupaten_options as$kb)
                            <option value="{{ $kb->id_kabupaten }}" {{ old('kabupaten', $warga->id_kabupaten) ==$kb->id_kabupaten ? 'selected' : '' }}>
                                {{ $kb->nama_kabupaten }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="label text-xs font-bold uppercase text-gray-600">Kecamatan <span class="text-brandRed">*</span></label>
                    <select name="kecamatan" id="kecamatan" required class="select2-search w-full bg-gray-50">
                        <option value="">-- Pilih Kecamatan --</option>
                        @foreach($kecamatan_options as$kc)
                            <option value="{{ $kc->id_kecamatan }}" {{ old('kecamatan', $warga->id_kecamatan) ==$kc->id_kecamatan ? 'selected' : '' }}>
                                {{ $kc->nama_kecamatan }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
                <div>
                    <label class="label text-xs font-bold uppercase text-gray-600">Desa / Kelurahan</label>
                    <select name="desa" id="desa" class="select2-search w-full bg-gray-50">
                        <option value="">-- Pilih Desa --</option>
                        @foreach($desa_options as$ds)
                            <option value="{{ $ds->id_desa }}" {{ old('desa', $warga->id_desa) ==$ds->id_desa ? 'selected' : '' }}>
                                {{ $ds->nama_desa }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="label text-xs font-bold uppercase text-gray-600">RT</label>
                    <select name="id_rt" class="select2-search w-full bg-gray-50">
                        <option value="">-- RT --</option>
                        @foreach($rt as$r)
                            <option value="{{ $r->id_rt }}" {{ old('id_rt', $warga->id_rt ?? $rumah->id_rt ?? '') == $r->id_rt ? 'selected' : '' }}>RT {{ $r->nomor_rt }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="label text-xs font-bold uppercase text-gray-600">RW</label>
                    <select name="id_rw" class="select2-search w-full bg-gray-50">
                        <option value="">-- RW --</option>
                        @foreach($rw as$w)
                            <option value="{{ $w->id_rw }}" {{ old('id_rw', $warga->id_rw ?? $rumah->id_rw ?? '') == $w->id_rw ? 'selected' : '' }}>RW {{ $w->nomor_rw }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <!-- 6. DATA BANGUNAN RUMAH & PETA KOORDINAT -->
        <div class="card bg-white border border-gray-200 shadow-sm rounded-2xl p-4 sm:p-6 space-y-4">
            <div class="flex items-center gap-2 border-b border-gray-100 pb-3">
                <div class="w-8 h-8 rounded-xl bg-red-50 text-brandRed flex items-center justify-center font-bold text-sm">6</div>
                <h3 class="font-bold text-brandDark text-base uppercase tracking-wide">Data Rumah & Koordinat Lokasi</h3>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                <div>
                    <label class="label text-xs font-bold uppercase text-gray-600">Jenis Konstruksi Bangunan</label>
                    <select name="jenis_konstruksi" class="select select-bordered w-full bg-gray-50 text-xs font-semibold">
                        <option value="Permanen" {{ old('jenis_konstruksi', $rumah->jenis_konstruksi ?? '') == 'Permanen' ? 'selected' : '' }}>Permanen</option>
                        <option value="Semi Permanen" {{ old('jenis_konstruksi', $rumah->jenis_konstruksi ?? '') == 'Semi Permanen' ? 'selected' : '' }}>Semi Permanen</option>
                        <option value="Panggung / Kayu" {{ old('jenis_konstruksi', $rumah->jenis_konstruksi ?? '') == 'Panggung / Kayu' ? 'selected' : '' }}>Panggung / Kayu</option>
                    </select>
                </div>

                <div>
                    <label class="label text-xs font-bold uppercase text-gray-600">Status Zona Tsunami / Kerentanan</label>
                    @php
                        $zonaArr =$selected_zona ?? (isset($rumah->status_zona_tsunami) ? array_map('trim', explode(',', $rumah->status_zona_tsunami)) : []);
                    @endphp
                    <div class="flex items-center gap-4 pt-2 flex-wrap">
                        <label class="cursor-pointer flex items-center gap-2 text-xs font-bold text-gray-700">
                            <input type="checkbox" name="status_zona_tsunami[]" value="Merah" class="checkbox checkbox-xs checkbox-error" {{ in_array('Merah', $zonaArr) ? 'checked' : '' }}> Zona Merah
                        </label>
                        <label class="cursor-pointer flex items-center gap-2 text-xs font-bold text-gray-700">
                            <input type="checkbox" name="status_zona_tsunami[]" value="Kuning" class="checkbox checkbox-xs checkbox-warning" {{ in_array('Kuning', $zonaArr) ? 'checked' : '' }}> Zona Kuning
                        </label>
                        <label class="cursor-pointer flex items-center gap-2 text-xs font-bold text-gray-700">
                            <input type="checkbox" name="status_zona_tsunami[]" value="Hijau" class="checkbox checkbox-xs checkbox-success" {{ in_array('Hijau', $zonaArr) ? 'checked' : '' }}> Zona Hijau
                        </label>
                    </div>
                </div>
            </div>

            <!-- PETA LEAFLET -->
            <div>
                <label class="label text-xs font-bold uppercase text-gray-600 mb-1">Titik Lokasi Rumah di Peta (Klik / Geser Penanda)</label>
                <div id="map" class="w-full h-56 sm:h-72 rounded-2xl border border-gray-200 overflow-hidden z-10 shadow-inner"></div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                <div>
                    <label class="label text-xs font-bold uppercase text-gray-600">Latitude</label>
                    <input type="text" name="latitude" id="latitude" readonly value="{{ old('latitude', $rumah->latitude ?? $warga->latitude ?? '') }}" class="input input-bordered w-full bg-gray-100 text-xs font-bold text-gray-600">
                </div>
                <div>
                    <label class="label text-xs font-bold uppercase text-gray-600">Longitude</label>
                    <input type="text" name="longitude" id="longitude" readonly value="{{ old('longitude', $rumah->longitude ?? $warga->longitude ?? '') }}" class="input input-bordered w-full bg-gray-100 text-xs font-bold text-gray-600">
                </div>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row items-center gap-3">
            <button type="submit" class="btn bg-brandRed hover:bg-brandDarkRed text-white border-none font-bold rounded-xl gap-2 px-8 shadow-sm w-full sm:w-auto justify-center">
                <i class="fas fa-save text-sm"></i> Simpan Pembaruan Data Warga
            </button>
            <a href="{{ route('warga.index') }}" class="btn btn-ghost rounded-xl text-xs w-full sm:w-auto justify-center">Batal</a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
function handleJenisKelaminChange() {
    let jk = $('#jenis_kelamin').val();
    if (jk === 'L') {
        $('#status_kehamilan').prop('checked', false).prop('disabled', true);
        $('#kehamilan_wrapper').addClass('opacity-50 pointer-events-none');
        $('#perkiraan_lahir_group').addClass('hidden');
        $('#perkiraan_lahir').val('');
    } else {
        $('#status_kehamilan').prop('disabled', false);
        $('#kehamilan_wrapper').removeClass('opacity-50 pointer-events-none');
    }
}

function togglePerkiraanLahir() {
    let chk = document.getElementById('status_kehamilan').checked;
    document.getElementById('perkiraan_lahir_group').classList.toggle('hidden', !chk);
    if (!chk) $('#perkiraan_lahir').val('');
}

function toggleKeteranganDisabilitas() {
    let val = document.getElementById('kategori_disabilitas').value;
    document.getElementById('keterangan_disabilitas_group').classList.toggle('hidden', val === 'Tidak Ada');
    if (val === 'Tidak Ada') $('#keterangan_disabilitas').val('');
}

$(document).ready(function() {$('.select2-search').select2({
        placeholder: "-- Pilih / Cari --",
        allowClear: true,
        width: '100%'
    });

    handleJenisKelaminChange();
    togglePerkiraanLahir();
    toggleKeteranganDisabilitas();

    // Leaflet Map Initialization dengan Koordinat Warga
    var curLat = parseFloat($('#latitude').val()) || -6.967000;
    var curLng = parseFloat($('#longitude').val()) || 106.333000;

    var map = L.map('map').setView([curLat, curLng], 14);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap'
    }).addTo(map);

    var marker = L.marker([curLat, curLng], { draggable: true }).addTo(map);

    marker.on('dragend', function(e) {
        var position = marker.getLatLng();
        $('#latitude').val(position.lat.toFixed(6));
        $('#longitude').val(position.lng.toFixed(6));
    });

    map.on('click', function(e) {
        var lat = e.latlng.lat.toFixed(6);
        var lng = e.latlng.lng.toFixed(6);

        marker.setLatLng(e.latlng);
        $('#latitude').val(lat);
        $('#longitude').val(lng);
    });

    // Cascading Dropdown Logic
    $('#provinsi').change(function() {
        let id = $(this).val();
        if (id) {
            $.get("{{ route('get.kabupaten') }}", { id_provinsi: id, provinsi_id: id }, function(data) {
                $('#kabupaten').html('<option value="">-- Pilih Kabupaten --</option>');
                data.forEach(item => $('#kabupaten').append(`<option value="${item.id_kabupaten}">${item.nama_kabupaten}</option>`));
                $('#kabupaten').trigger('change');
            });
        }
    });

    $('#kabupaten').change(function() {
        let id = $(this).val();
        if (id) {
            $.get("{{ route('get.kecamatan') }}", { id_kabupaten: id, kabupaten_id: id }, function(data) {
                $('#kecamatan').html('<option value="">-- Pilih Kecamatan --</option>');
                data.forEach(item => $('#kecamatan').append(`<option value="${item.id_kecamatan}">${item.nama_kecamatan}</option>`));
                $('#kecamatan').trigger('change');
            });
        }
    });

    $('#kecamatan').change(function() {
        let id = $(this).val();
        if (id) {
            $.get("{{ route('get.desa') }}", { id_kecamatan: id, kecamatan_id: id }, function(data) {
                $('#desa').html('<option value="">-- Pilih Desa --</option>');
                data.forEach(item => $('#desa').append(`<option value="${item.id_desa}">${item.nama_desa}</option>`));
                $('#desa').trigger('change');
            });
        }
    });
});
</script>
@endpush