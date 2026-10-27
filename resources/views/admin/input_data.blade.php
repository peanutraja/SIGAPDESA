@extends('layouts.admin')

@section('title', 'Input Data Penduduk - SIGAPDESA')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .select2-container .select2-selection--single {
        height: 42px !important;
        border: 1px solid #cbd5e1 !important;
        border-radius: 0.75rem !important;
        padding: 6px 12px !important;
        background-color: #ffffff !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 40px !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #0f172a !important;
        line-height: 26px !important;
        font-weight: 600 !important;
        font-size: 0.75rem !important;
    }
</style>
@endpush

@section('content')
<div class="max-w-7xl mx-auto space-y-5" data-theme="light">

    <!-- HEADER & AKSI UTAMA -->
    <div class="bg-white rounded-2xl p-5 sm:p-6 border border-slate-200 shadow-sm flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1.5 flex-wrap">
                <span class="px-3 py-1 rounded-full bg-rose-600 text-white font-extrabold text-[11px] uppercase tracking-wider shadow-xs">Form Pendataan</span>
                <span class="text-xs text-slate-500 font-bold">Sistem Pendataan Warga Terintegrasi</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight uppercase">Input Data Penduduk Baru</h1>
            <p class="text-xs text-slate-500 font-medium">Lengkapi formulir di bawah ini dengan akurat atau unduh template CSV untuk pengisian masal.</p>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            <!-- Tombol 1: Download Template CSV -->
            <a href="{{ route('penduduk.template.csv') }}" class="inline-flex items-center justify-center gap-2 px-3.5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold text-xs transition shadow-xs">
                <i class="fas fa-file-excel text-sm"></i> Download Template CSV
            </a>

            <!-- Tombol 2: Upload CSV -->
            <button type="button" onclick="toggleModalCSV(true)" class="inline-flex items-center justify-center gap-2 px-3.5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold text-xs transition shadow-xs cursor-pointer">
                <i class="fas fa-file-csv text-sm"></i> Upload CSV
            </button>

            <!-- Tombol 3: Export Data -->
            <a href="{{ route('penduduk.export.csv') }}" class="inline-flex items-center justify-center gap-2 px-3.5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-800 border border-slate-300 rounded-xl font-bold text-xs transition">
                <i class="fas fa-file-export text-slate-600 text-sm"></i> Export Data
            </a>

            <!-- Tombol 4: Lihat Daftar Warga -->
            <a href="{{ route('warga.index') }}" class="inline-flex items-center justify-center gap-2 px-3.5 py-2.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl font-bold text-xs transition shadow-xs">
                <i class="fas fa-list text-sm"></i> Lihat Warga
            </a>
        </div>
    </div>

    <!-- NOTIFIKASI ALERT -->
    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl text-xs font-bold p-4 flex items-center gap-2 shadow-xs">
            <i class="fas fa-check-circle text-base text-emerald-600"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-rose-50 border border-rose-200 text-rose-800 rounded-2xl text-xs font-bold p-4 flex items-center gap-2 shadow-xs">
            <i class="fas fa-exclamation-triangle text-base text-rose-600"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="bg-rose-50 border border-rose-200 text-rose-800 rounded-2xl text-xs font-bold p-4 flex items-start gap-2 shadow-xs">
            <i class="fas fa-exclamation-triangle text-base text-rose-600"></i>
            <ul class="list-disc pl-4 space-y-0.5">
                @foreach($errors->all() as $pesan)
                    <li>{{ $pesan }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- FORM INPUT PENDUDUK MANUAL -->
    <form method="POST" action="{{ route('penduduk.store') }}" class="space-y-5">
        @csrf

        <!-- 1. DATA IDENTITAS -->
        <div class="bg-white border border-slate-200 shadow-sm rounded-2xl p-5 sm:p-6 space-y-4">
            <div class="flex items-center gap-3 border-b border-slate-100 pb-3">
                <div class="w-8 h-8 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center font-black text-sm">1</div>
                <h3 class="font-extrabold text-slate-900 text-base uppercase tracking-wide">Data Identitas Diri</h3>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-700 mb-1">NIK (Nomor Induk Kependudukan) <span class="text-rose-600">*</span></label>
                    <input type="text" name="nik" value="{{ old('nik') }}" maxlength="16" required
                        class="w-full bg-white text-slate-900 border border-slate-300 rounded-xl px-3 py-2.5 text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-rose-500" placeholder="16 digit NIK sesuai KTP">
                    <p class="text-[10px] text-slate-400 font-medium mt-1">Harus terdiri dari 16 digit angka</p>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase text-slate-700 mb-1">Nama Lengkap <span class="text-rose-600">*</span></label>
                    <input type="text" name="nama_lengkap" value="{{ old('nama_lengkap') }}" required
                        class="w-full bg-white text-slate-900 border border-slate-300 rounded-xl px-3 py-2.5 text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-rose-500" placeholder="Sesuai KTP / KK">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-700 mb-1">Tempat Lahir <span class="text-rose-600">*</span></label>
                    <select name="tempat_lahir" required class="select2-search w-full bg-white">
                        <option value="">-- Pilih Kota/Kabupaten --</option>
                        @foreach($kabupaten as $k)
                            <option value="{{ $k->nama_kabupaten }}" {{ old('tempat_lahir') == $k->nama_kabupaten ? 'selected' : '' }}>
                                {{ $k->nama_kabupaten }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase text-slate-700 mb-1">Tanggal Lahir <span class="text-rose-600">*</span></label>
                    <input type="date" name="tanggal_lahir" value="{{ old('tanggal_lahir') }}" required class="w-full bg-white text-slate-900 border border-slate-300 rounded-xl px-3 py-2.5 text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-rose-500">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase text-slate-700 mb-1">Jenis Kelamin <span class="text-rose-600">*</span></label>
                    <select name="jenis_kelamin" id="jenis_kelamin" onchange="handleJenisKelaminChange()" required class="w-full bg-white text-slate-900 border border-slate-300 rounded-xl px-3 py-2.5 text-xs font-bold focus:outline-none focus:ring-2 focus:ring-rose-500">
                        <option value="">-- Pilih --</option>
                        <option value="L" {{ old('jenis_kelamin') == 'L' ? 'selected' : '' }}>Laki-laki</option>
                        <option value="P" {{ old('jenis_kelamin') == 'P' ? 'selected' : '' }}>Perempuan</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- 2. DATA PRIBADI & PEKERJAAN -->
        <div class="bg-white border border-slate-200 shadow-sm rounded-2xl p-5 sm:p-6 space-y-4">
            <div class="flex items-center gap-3 border-b border-slate-100 pb-3">
                <div class="w-8 h-8 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center font-black text-sm">2</div>
                <h3 class="font-extrabold text-slate-900 text-base uppercase tracking-wide">Data Pribadi & Pekerjaan</h3>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-700 mb-1">Golongan Darah <span class="text-rose-600">*</span></label>
                    <select name="golongan_darah" class="w-full bg-white text-slate-900 border border-slate-300 rounded-xl px-3 py-2.5 text-xs font-bold focus:outline-none focus:ring-2 focus:ring-rose-500" required>
                        <option value="">-- Pilih --</option>
                        <option value="A" {{ old('golongan_darah') == 'A' ? 'selected' : '' }}>A</option>
                        <option value="B" {{ old('golongan_darah') == 'B' ? 'selected' : '' }}>B</option>
                        <option value="AB" {{ old('golongan_darah') == 'AB' ? 'selected' : '' }}>AB</option>
                        <option value="O" {{ old('golongan_darah') == 'O' ? 'selected' : '' }}>O</option>
                        <option value="-" {{ old('golongan_darah') == '-' ? 'selected' : '' }}>Tidak Tahu</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase text-slate-700 mb-1">Agama</label>
                    <select name="agama" class="select2-search w-full bg-white">
                        <option value="">-- Pilih Agama --</option>
                        @foreach(['Islam','Kristen','Katolik','Hindu','Buddha','Konghucu'] as $ag)
                            <option value="{{ $ag }}" {{ old('agama') == $ag ? 'selected' : '' }}>{{ $ag }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase text-slate-700 mb-1">Status Perkawinan</label>
                    <select name="status_perkawinan" class="w-full bg-white text-slate-900 border border-slate-300 rounded-xl px-3 py-2.5 text-xs font-bold focus:outline-none focus:ring-2 focus:ring-rose-500">
                        <option value="">-- Pilih Status --</option>
                        @foreach(['Belum Kawin','Kawin','Cerai Hidup','Cerai Mati'] as $sp)
                            <option value="{{ $sp }}" {{ old('status_perkawinan') == $sp ? 'selected' : '' }}>{{ $sp }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-700 mb-1">Nomor Telepon / WA</label>
                    <input type="tel" name="nomor_telepon" value="{{ old('nomor_telepon') }}" class="w-full bg-white text-slate-900 border border-slate-300 rounded-xl px-3 py-2.5 text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-rose-500" placeholder="08xxxxxxxxxx">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase text-slate-700 mb-1">Pekerjaan Utama <span class="text-rose-600">*</span></label>
                    <select name="pekerjaan" required class="select2-search w-full bg-white">
                        <option value="">-- Pilih Pekerjaan --</option>
                        @foreach([
                            'Belum/Tidak Bekerja','Pelajar/Mahasiswa','Karyawan Honorer','Pegawai Negeri Sipil',
                            'Tentara Nasional Indonesia','Kepolisian RI','Karyawan Swasta','Buruh Harian',
                            'Perdagangan','Petani/Pekebun','Nelayan/Perikanan','Penata Busana','Guru','Perawat',
                            'Bidan','Dokter','Sopir','Driver Online','Mengurus Rumah Tangga','Wiraswasta','Pensiunan','Lainnya'
                        ] as $pek)
                            <option value="{{ $pek }}" {{ old('pekerjaan') == $pek ? 'selected' : '' }}>{{ $pek }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase text-slate-700 mb-1">Jenis Penghasilan</label>
                    <select name="jenis_penghasilan" class="w-full bg-white text-slate-900 border border-slate-300 rounded-xl px-3 py-2.5 text-xs font-bold focus:outline-none focus:ring-2 focus:ring-rose-500">
                        <option value="">-- Pilih Penghasilan --</option>
                        <option value="Tetap" {{ old('jenis_penghasilan') == 'Tetap' ? 'selected' : '' }}>Tetap</option>
                        <option value="Tidak Tetap" {{ old('jenis_penghasilan') == 'Tidak Tetap' ? 'selected' : '' }}>Tidak Tetap</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase text-slate-700 mb-1">Status Domisili Kependudukan <span class="text-rose-600">*</span></label>
                <select name="status_domisili" class="w-full bg-white text-slate-900 border border-slate-300 rounded-xl px-3 py-2.5 text-xs font-bold focus:outline-none focus:ring-2 focus:ring-rose-500" required>
                    @foreach(['Menetap','Kerja di Luar','Pendatang','Tinggal Sementara'] as $dom)
                        <option value="{{ $dom }}" {{ old('status_domisili') == $dom ? 'selected' : '' }}>{{ $dom }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- 3. DATA KESEHATAN & DISABILITAS -->
        <div class="bg-white border border-slate-200 shadow-sm rounded-2xl p-5 sm:p-6 space-y-4">
            <div class="flex items-center gap-3 border-b border-slate-100 pb-3">
                <div class="w-8 h-8 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center font-black text-sm">3</div>
                <h3 class="font-extrabold text-slate-900 text-base uppercase tracking-wide">Status Kesehatan & Kelompok Rentan</h3>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div id="kehamilan_wrapper" class="flex items-center pt-2">
                    <label class="cursor-pointer flex items-center gap-3">
                        <input type="checkbox" name="status_kehamilan" id="status_kehamilan" value="1" class="w-4 h-4 text-rose-600 rounded border-slate-300 focus:ring-rose-500"
                            onchange="togglePerkiraanLahir()" {{ old('status_kehamilan') ? 'checked' : '' }}>
                        <span class="font-bold text-xs text-slate-800 uppercase">Sedang Dalam Kondisi Hamil</span>
                    </label>
                </div>

                <div id="perkiraan_lahir_group" class="{{ old('status_kehamilan') ? '' : 'hidden' }}">
                    <label class="block text-xs font-bold uppercase text-slate-700 mb-1">Perkiraan Lahir (HPL)</label>
                    <input type="date" name="perkiraan_lahir" id="perkiraan_lahir" value="{{ old('perkiraan_lahir') }}" class="w-full bg-white text-slate-900 border border-slate-300 rounded-xl px-3 py-2.5 text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-rose-500">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-700 mb-1">Kategori Disabilitas</label>
                    <select name="kategori_disabilitas" id="kategori_disabilitas" onchange="toggleKeteranganDisabilitas()" class="w-full bg-white text-slate-900 border border-slate-300 rounded-xl px-3 py-2.5 text-xs font-bold focus:outline-none focus:ring-2 focus:ring-rose-500">
                        <option value="Tidak Ada" {{ old('kategori_disabilitas') == 'Tidak Ada' ? 'selected' : '' }}>Tidak Ada</option>
                        <option value="Fisik" {{ old('kategori_disabilitas') == 'Fisik' ? 'selected' : '' }}>Fisik (Mobilitas Terbatas)</option>
                        <option value="Intelektual" {{ old('kategori_disabilitas') == 'Intelektual' ? 'selected' : '' }}>Intelektual</option>
                        <option value="Mental" {{ old('kategori_disabilitas') == 'Mental' ? 'selected' : '' }}>Mental</option>
                        <option value="Sensorik" {{ old('kategori_disabilitas') == 'Sensorik' ? 'selected' : '' }}>Sensorik (Pendengaran/Penglihatan)</option>
                    </select>
                </div>

                <div id="keterangan_disabilitas_group" class="{{ old('kategori_disabilitas') && old('kategori_disabilitas') != 'Tidak Ada' ? '' : 'hidden' }}">
                    <label class="block text-xs font-bold uppercase text-slate-700 mb-1">Keterangan Alat Bantu / Disabilitas</label>
                    <textarea name="keterangan_disabilitas" id="keterangan_disabilitas" class="w-full bg-white text-slate-900 border border-slate-300 rounded-xl p-3 text-xs font-medium focus:outline-none focus:ring-2 focus:ring-rose-500" rows="2" placeholder="Contoh: Menggunakan kursi roda">{{ old('keterangan_disabilitas') }}</textarea>
                </div>
            </div>
        </div>

        <!-- 4. DATA KARTU KELUARGA -->
        <div class="bg-white border border-slate-200 shadow-sm rounded-2xl p-5 sm:p-6 space-y-4">
            <div class="flex items-center gap-3 border-b border-slate-100 pb-3">
                <div class="w-8 h-8 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center font-black text-sm">4</div>
                <h3 class="font-extrabold text-slate-900 text-base uppercase tracking-wide">Data Kartu Keluarga (KK)</h3>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-700 mb-1">Nomor Kartu Keluarga (KK) <span class="text-rose-600">*</span></label>
                    <input type="text" name="nomor_kk" value="{{ old('nomor_kk') }}" maxlength="16" required
                        class="w-full bg-white text-slate-900 border border-slate-300 rounded-xl px-3 py-2.5 text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-rose-500" placeholder="16 digit Nomor KK">
                    <p class="text-[10px] text-slate-400 font-medium mt-1">Harus terdiri dari 16 digit angka</p>
                </div>

                <div class="flex items-center pt-2 sm:pt-6">
                    <label class="cursor-pointer flex items-center gap-3">
                        <input type="checkbox" name="is_kepala_keluarga" class="w-4 h-4 text-rose-600 rounded border-slate-300 focus:ring-rose-500" {{ old('is_kepala_keluarga') ? 'checked' : '' }}>
                        <span class="font-bold text-xs text-slate-800 uppercase">Warga Ini Adalah Kepala Keluarga (KK)</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- 5. DATA ALAMAT & WILAYAH -->
        <div class="bg-white border border-slate-200 shadow-sm rounded-2xl p-5 sm:p-6 space-y-4">
            <div class="flex items-center gap-3 border-b border-slate-100 pb-3">
                <div class="w-8 h-8 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center font-black text-sm">5</div>
                <h3 class="font-extrabold text-slate-900 text-base uppercase tracking-wide">Data Alamat & Wilayah Domisili</h3>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase text-slate-700 mb-1">Alamat Lengkap (Jalan/Gang/Nomor Rumah) <span class="text-rose-600">*</span></label>
                <textarea name="alamat_lengkap" rows="2" required class="w-full bg-white text-slate-900 border border-slate-300 rounded-xl p-3 text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-rose-500" placeholder="Contoh: Jl. Mawar No. 12">{{ old('alamat_lengkap') }}</textarea>
            </div>

            @if(!empty($wilayahRt))
                @include('admin.partials.wilayah_terkunci')
            @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-700 mb-1">Provinsi <span class="text-rose-600">*</span></label>
                    <select name="provinsi" id="provinsi" required class="select2-search w-full bg-white">
                        <option value="">-- Pilih Provinsi --</option>
                        @foreach($provinsi as $p)
                            <option value="{{ $p->id_provinsi }}">{{ $p->nama_provinsi }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase text-slate-700 mb-1">Kabupaten / Kota <span class="text-rose-600">*</span></label>
                    <select name="kabupaten" id="kabupaten" required disabled class="select2-search w-full bg-white">
                        <option value="">-- Pilih Kabupaten --</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase text-slate-700 mb-1">Kecamatan <span class="text-rose-600">*</span></label>
                    <select name="kecamatan" id="kecamatan" required disabled class="select2-search w-full bg-white">
                        <option value="">-- Pilih Kecamatan --</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-700 mb-1">Desa / Kelurahan</label>
                    <select name="desa" id="desa" disabled class="select2-search w-full bg-white">
                        <option value="">-- Pilih Desa --</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase text-slate-700 mb-1">RT</label>
                    <select name="id_rt" class="select2-search w-full bg-white">
                        <option value="">-- RT --</option>
                        @foreach($rt as $r)
                            <option value="{{ $r->id_rt }}">RT {{ $r->nomor_rt }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase text-slate-700 mb-1">RW</label>
                    <select name="id_rw" class="select2-search w-full bg-white">
                        <option value="">-- RW --</option>
                        @foreach($rw as $w)
                            <option value="{{ $w->id_rw }}">RW {{ $w->nomor_rw }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            @endif
        </div>

        <!-- 6. DATA BANGUNAN RUMAH & PETA KOORDINAT -->
        <div class="bg-white border border-slate-200 shadow-sm rounded-2xl p-5 sm:p-6 space-y-4">
            <div class="flex items-center gap-3 border-b border-slate-100 pb-3">
                <div class="w-8 h-8 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center font-black text-sm">6</div>
                <h3 class="font-extrabold text-slate-900 text-base uppercase tracking-wide">Data Rumah & Koordinat Lokasi</h3>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-700 mb-1">Jenis Konstruksi Bangunan</label>
                    <select name="jenis_konstruksi" class="w-full bg-white text-slate-900 border border-slate-300 rounded-xl px-3 py-2.5 text-xs font-bold focus:outline-none focus:ring-2 focus:ring-rose-500">
                        <option value="">-- Pilih Jenis --</option>
                        <option value="Permanen">Permanen</option>
                        <option value="Semi Permanen">Semi Permanen</option>
                        <option value="Panggung / Kayu">Panggung / Kayu</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase text-slate-700 mb-1">Zona Risiko Bencana</label>
                    <div class="flex items-start gap-2 p-3 rounded-xl bg-sky-50 border border-sky-200 text-[11px] text-sky-800 font-semibold">
                        <i class="fas fa-satellite-dish mt-0.5"></i>
                        <span>Ditentukan otomatis dari data <b>InaRISK BNPB</b> berdasarkan titik koordinat rumah — tidak perlu diisi manual. Zona muncul di peta setelah dianalisis.</span>
                    </div>
                </div>
            </div>

            <!-- PETA LEAFLET -->
            <div>
                <label class="block text-xs font-bold uppercase text-slate-700 mb-1.5">Pilih Titik Lokasi Rumah di Peta (Klik Pada Peta)</label>
                <div id="map" class="w-full h-60 sm:h-72 rounded-2xl border border-slate-300 overflow-hidden z-10 shadow-inner"></div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-700 mb-1">Latitude</label>
                    <input type="text" name="latitude" id="latitude" readonly class="w-full bg-slate-100 text-slate-700 border border-slate-300 rounded-xl px-3 py-2.5 text-xs font-bold">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-700 mb-1">Longitude</label>
                    <input type="text" name="longitude" id="longitude" readonly class="w-full bg-slate-100 text-slate-700 border border-slate-300 rounded-xl px-3 py-2.5 text-xs font-bold">
                </div>
            </div>
        </div>

        <button type="submit" class="w-full sm:w-auto px-8 py-3 bg-rose-600 hover:bg-rose-700 text-white font-extrabold rounded-xl gap-2 shadow-md transition inline-flex items-center justify-center text-xs cursor-pointer">
            <i class="fas fa-save text-sm"></i> Simpan Data Penduduk
        </button>
    </form>
</div>

<!-- MODAL UPLOAD CSV -->
<div id="modalCSV" class="fixed inset-0 bg-slate-900/60 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-11/12 max-w-md p-6 space-y-4 border border-slate-200">
        <div class="flex justify-between items-center border-b pb-3">
            <h3 class="text-base font-extrabold text-slate-900 flex items-center gap-2">
                <i class="fas fa-file-csv text-blue-600"></i> Upload Data Via CSV
            </h3>
            <button type="button" onclick="toggleModalCSV(false)" class="text-slate-400 hover:text-slate-700 font-bold p-1">✕</button>
        </div>

        <form action="{{ route('penduduk.import.csv') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold uppercase text-slate-700 mb-2">Pilih File CSV Data Warga</label>
                <input type="file" name="file_csv" accept=".csv" required class="w-full bg-white text-slate-900 border border-slate-300 rounded-xl p-2 text-xs font-semibold focus:outline-none" />
                <p class="text-[11px] text-slate-500 mt-2 leading-relaxed font-medium">
                    💡 <b>Petunjuk:</b> Gunakan tombol <b>Download Template CSV</b> terlebih dahulu untuk mendapatkan format isian yang siap diedit di Excel.
                </p>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="toggleModalCSV(false)" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition">Batal</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition shadow-xs">Upload & Import File</button>
            </div>
        </form>
    </div>
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

function toggleModalCSV(show) {
    document.getElementById('modalCSV').classList.toggle('hidden', !show);
    document.getElementById('modalCSV').classList.toggle('flex', show);
}

$(document).ready(function() {
    $('.select2-search').select2({
        placeholder: "-- Pilih / Cari --",
        allowClear: true,
        width: '100%'
    });

    handleJenisKelaminChange();

    var defaultLat = -6.967000;
    var defaultLng = 106.333000;
    var map = L.map('map').setView([defaultLat, defaultLng], 12);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap'
    }).addTo(map);

    var marker;
    map.on('click', function(e) {
        var lat = e.latlng.lat.toFixed(6);
        var lng = e.latlng.lng.toFixed(6);

        if (marker) {
            marker.setLatLng(e.latlng);
        } else {
            marker = L.marker(e.latlng).addTo(map);
        }

        $('#latitude').val(lat);
        $('#longitude').val(lng);
    });

    $('#provinsi').change(function() {
        let id = $(this).val();
        $('#kabupaten').html('<option value="">-- Pilih Kabupaten --</option>').prop('disabled', true).trigger('change');
        $('#kecamatan').html('<option value="">-- Pilih Kecamatan --</option>').prop('disabled', true).trigger('change');
        $('#desa').html('<option value="">-- Pilih Desa --</option>').prop('disabled', true).trigger('change');

        if (id) {
            $.get("{{ route('get.kabupaten') }}", { id_provinsi: id, provinsi_id: id }, function(data) {
                $('#kabupaten').prop('disabled', false);
                data.forEach(item => $('#kabupaten').append(`<option value="${item.id_kabupaten}">${item.nama_kabupaten}</option>`));
                $('#kabupaten').trigger('change');
            });
        }
    });

    $('#kabupaten').change(function() {
        let id = $(this).val();$('#kecamatan').html('<option value="">-- Pilih Kecamatan --</option>').prop('disabled', true).trigger('change');
        $('#desa').html('<option value="">-- Pilih Desa --</option>').prop('disabled', true).trigger('change');

        if (id) {
            $.get("{{ route('get.kecamatan') }}", { id_kabupaten: id, kabupaten_id: id }, function(data) {
                $('#kecamatan').prop('disabled', false);
                data.forEach(item => $('#kecamatan').append(`<option value="${item.id_kecamatan}">${item.nama_kecamatan}</option>`));
                $('#kecamatan').trigger('change');
            });
        }
    });

    $('#kecamatan').change(function() {
        let id = $(this).val();$('#desa').html('<option value="">-- Pilih Desa --</option>').prop('disabled', true).trigger('change');

        if (id) {
            $.get("{{ route('get.desa') }}", { id_kecamatan: id, kecamatan_id: id }, function(data) {
                $('#desa').prop('disabled', false);
                data.forEach(item => $('#desa').append(`<option value="${item.id_desa}">${item.nama_desa}</option>`));
                $('#desa').trigger('change');
            });
        }
    });
});
</script>
@endpush