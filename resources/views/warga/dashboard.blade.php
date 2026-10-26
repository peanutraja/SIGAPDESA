@extends('layouts.warga')

@section('title', 'Dashboard Mandiri Warga - SIGAPDESA')

@section('content')
<div class="max-w-7xl mx-auto space-y-4 sm:space-y-6">

    <!-- HERO PROFILE WARGA -->
    <div class="bg-white border border-gray-200 rounded-2xl p-4 sm:p-6 shadow-sm flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 sm:gap-4">
            <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl bg-brandRed text-white flex items-center justify-center font-black text-xl sm:text-2xl shadow-sm">
                {{ strtoupper(substr($warga->nama_lengkap ?? 'W', 0, 1)) }}
            </div>
            <div>
                <div class="flex items-center gap-2 mb-1 flex-wrap">
                    <span class="badge bg-brandRed text-white font-bold text-[10px] uppercase px-2.5 py-1">Akun Warga Mandiri</span>
                    <span class="text-xs font-semibold text-gray-400">NIK: {{ $warga->nik ?? '-' }}</span>
                </div>
                <h1 class="text-xl sm:text-2xl font-black text-brandDark tracking-tight">Halo, {{ $warga->nama_lengkap ?? 'Warga' }}</h1>
                <p class="text-xs text-gray-500">Selamat datang di portal mandiri kependudukan SIGAPDESA.</p>
            </div>
        </div>
    </div>

    <!-- NOTIFIKASI ALERT -->
    @if(session('success'))
        <div class="alert alert-success bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl text-xs font-semibold">
            <i class="fas fa-check-circle text-emerald-600"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-error bg-red-50 border border-red-200 text-brandRed rounded-2xl text-xs font-semibold">
            <i class="fas fa-exclamation-circle text-brandRed"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- GRID UTAMA CONTAINER -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">

        <!-- FORM PENGAJUAN PERUBAHAN DATA (2 KOLOM) -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-200 shadow-sm p-4 sm:p-6 space-y-5 sm:space-y-6">
            <div class="border-b border-gray-100 pb-4">
                <h3 class="text-base sm:text-lg font-black text-brandDark uppercase tracking-wide flex items-center gap-2">
                    <i class="fas fa-user-edit text-brandRed"></i> Form Pengajuan Perubahan Data Mandiri
                </h3>
                <p class="text-xs text-gray-500 mt-1">Ajukan pembaruan data kependudukan Anda untuk ditinjau dan diverifikasi oleh Ketua RT / Admin.</p>
            </div>

            <form method="POST" action="{{ route('warga.dashboard.pengajuan') }}" class="space-y-5">
                @csrf

                <!-- 1. IDENTITAS DIRI -->
                <div class="space-y-3">
                    <h4 class="text-xs font-bold text-brandRed uppercase tracking-wider border-b pb-1">1. Identitas Diri</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                        <div>
                            <label class="label text-xs font-bold uppercase text-gray-600">NIK (Sesuai KTP)</label>
                            <input type="text" value="{{ $warga->nik ?? '' }}" readonly class="input input-bordered w-full bg-gray-100 text-xs font-bold text-gray-500 cursor-not-allowed">
                        </div>
                        <div>
                            <label class="label text-xs font-bold uppercase text-gray-600">Nama Lengkap <span class="text-brandRed">*</span></label>
                            <input type="text" name="nama_lengkap" value="{{ old('nama_lengkap', $warga->nama_lengkap ?? '') }}" required class="input input-bordered w-full bg-gray-50 text-xs font-medium">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
                        <div>
                            <label class="label text-xs font-bold uppercase text-gray-600">Tempat Lahir <span class="text-brandRed">*</span></label>
                            <select name="tempat_lahir" required class="select select-bordered w-full bg-gray-50 text-xs font-semibold">
                                <option value="">-- Pilih Kota/Kabupaten --</option>
                                @foreach($kabupaten as $k)
                                    <option value="{{ $k->nama_kabupaten }}" {{ old('tempat_lahir', $warga->tempat_lahir ?? '') == $k->nama_kabupaten ? 'selected' : '' }}>
                                        {{ $k->nama_kabupaten }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="label text-xs font-bold uppercase text-gray-600">Tanggal Lahir <span class="text-brandRed">*</span></label>
                            <input type="date" name="tanggal_lahir" value="{{ old('tanggal_lahir', $warga->tanggal_lahir ?? '') }}" required class="input input-bordered w-full bg-gray-50 text-xs font-medium">
                        </div>
                        <div>
                            <label class="label text-xs font-bold uppercase text-gray-600">Jenis Kelamin <span class="text-brandRed">*</span></label>
                            <select name="jenis_kelamin" id="jenis_kelamin" onchange="handleJenisKelaminChange()" required class="select select-bordered w-full bg-gray-50 text-xs font-semibold">
                                <option value="L" {{ old('jenis_kelamin', $warga->jenis_kelamin ?? '') == 'L' ? 'selected' : '' }}>Laki-laki</option>
                                <option value="P" {{ old('jenis_kelamin', $warga->jenis_kelamin ?? '') == 'P' ? 'selected' : '' }}>Perempuan</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- 2. DATA PRIBADI & PEKERJAAN -->
                <div class="space-y-3">
                    <h4 class="text-xs font-bold text-brandRed uppercase tracking-wider border-b pb-1">2. Data Pribadi & Pekerjaan</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
                        <div>
                            <label class="label text-xs font-bold uppercase text-gray-600">Golongan Darah <span class="text-brandRed">*</span></label>
                            <select name="golongan_darah" class="select select-bordered w-full bg-gray-50 text-xs font-semibold" required>
                                @foreach(['A','B','AB','O','-'] as $gol)
                                    <option value="{{ $gol }}" {{ old('golongan_darah', $warga->golongan_darah ?? '') == $gol ? 'selected' : '' }}>{{ $gol == '-' ? 'Tidak Tahu' : $gol }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="label text-xs font-bold uppercase text-gray-600">Agama</label>
                            <select name="agama" class="select select-bordered w-full bg-gray-50 text-xs font-semibold">
                                @foreach(['Islam','Kristen','Katolik','Hindu','Buddha','Konghucu'] as $ag)
                                    <option value="{{ $ag }}" {{ old('agama', $warga->agama ?? '') == $ag ? 'selected' : '' }}>{{ $ag }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="label text-xs font-bold uppercase text-gray-600">Status Kawin</label>
                            <select name="status_perkawinan" class="select select-bordered w-full bg-gray-50 text-xs font-semibold">
                                @foreach(['Belum Kawin','Kawin','Cerai Hidup','Cerai Mati'] as $sp)
                                    <option value="{{ $sp }}" {{ old('status_perkawinan', $warga->status_perkawinan ?? '') == $sp ? 'selected' : '' }}>{{ $sp }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
                        <div>
                            <label class="label text-xs font-bold uppercase text-gray-600">Nomor HP / WA</label>
                            <input type="text" name="nomor_telepon" value="{{ old('nomor_telepon', $warga->nomor_telepon ?? '') }}" class="input input-bordered w-full bg-gray-50 text-xs font-medium" placeholder="08xxxxxxxxxx">
                        </div>
                        <div>
                            <label class="label text-xs font-bold uppercase text-gray-600">Pekerjaan <span class="text-brandRed">*</span></label>
                            <select name="pekerjaan" required class="select select-bordered w-full bg-gray-50 text-xs font-semibold">
                                @foreach([
                                    'Belum/Tidak Bekerja','Pelajar/Mahasiswa','Karyawan Honorer','Pegawai Negeri Sipil',
                                    'Tentara Nasional Indonesia','Kepolisian RI','Karyawan Swasta','Buruh Harian',
                                    'Perdagangan','Petani/Pekebun','Nelayan/Perikanan','Penata Busana','Guru','Perawat',
                                    'Bidan','Dokter','Sopir','Driver Online','Mengurus Rumah Tangga','Wiraswasta','Pensiunan','Lainnya'
                                ] as $pek)
                                    <option value="{{ $pek }}" {{ old('pekerjaan', $warga->pekerjaan ?? '') == $pek ? 'selected' : '' }}>{{ $pek }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="label text-xs font-bold uppercase text-gray-600">Status Domisili <span class="text-brandRed">*</span></label>
                            <select name="status_domisili" required class="select select-bordered w-full bg-gray-50 text-xs font-semibold">
                                @foreach(['Menetap','Kerja di Luar','Pendatang','Tinggal Sementara'] as $dom)
                                    <option value="{{ $dom }}" {{ old('status_domisili', $warga->status_domisili ?? '') == $dom ? 'selected' : '' }}>{{ $dom }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <!-- 3. STATUS KESEHATAN & KELOMPOK RENTAN -->
                <div class="space-y-3">
                    <h4 class="text-xs font-bold text-brandRed uppercase tracking-wider border-b pb-1">3. Status Kesehatan & Kelompok Rentan</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                        <div id="kehamilan_wrapper" class="flex items-center pt-2">
                            <label class="cursor-pointer label gap-3 justify-start">
                                <input type="checkbox" name="status_kehamilan" id="status_kehamilan" value="1" class="checkbox checkbox-error"
                                    onchange="togglePerkiraanLahir()" {{ old('status_kehamilan', $warga->status_kehamilan ?? 0) == 1 ? 'checked' : '' }}>
                                <span class="label-text font-bold text-xs text-brandDark uppercase">Sedang Dalam Kondisi Hamil</span>
                            </label>
                        </div>

                        <div id="perkiraan_lahir_group" class="{{ old('status_kehamilan', $warga->status_kehamilan ?? 0) == 1 ? '' : 'hidden' }}">
                            <label class="label text-xs font-bold uppercase text-gray-600">Perkiraan Lahir (HPL)</label>
                            <input type="date" name="perkiraan_lahir" id="perkiraan_lahir" value="{{ old('perkiraan_lahir', $warga->perkiraan_lahir ?? '') }}" class="input input-bordered w-full bg-gray-50 text-xs font-medium">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                        <div>
                            <label class="label text-xs font-bold uppercase text-gray-600">Kategori Disabilitas</label>
                            <select name="kategori_disabilitas" id="kategori_disabilitas" onchange="toggleKeteranganDisabilitas()" class="select select-bordered w-full bg-gray-50 text-xs font-semibold">
                                <option value="Tidak Ada" {{ old('kategori_disabilitas', $warga->kategori_disabilitas ?? 'Tidak Ada') == 'Tidak Ada' ? 'selected' : '' }}>Tidak Ada</option>
                                <option value="Fisik" {{ old('kategori_disabilitas', $warga->kategori_disabilitas ?? '') == 'Fisik' ? 'selected' : '' }}>Fisik (Mobilitas Terbatas)</option>
                                <option value="Intelektual" {{ old('kategori_disabilitas', $warga->kategori_disabilitas ?? '') == 'Intelektual' ? 'selected' : '' }}>Intelektual</option>
                                <option value="Mental" {{ old('kategori_disabilitas', $warga->kategori_disabilitas ?? '') == 'Mental' ? 'selected' : '' }}>Mental</option>
                                <option value="Sensorik" {{ old('kategori_disabilitas', $warga->kategori_disabilitas ?? '') == 'Sensorik' ? 'selected' : '' }}>Sensorik (Pendengaran/Penglihatan)</option>
                            </select>
                        </div>

                        <div id="keterangan_disabilitas_group" class="{{ old('kategori_disabilitas', $warga->kategori_disabilitas ?? 'Tidak Ada') != 'Tidak Ada' ? '' : 'hidden' }}">
                            <label class="label text-xs font-bold uppercase text-gray-600">Keterangan Disabilitas / Alat Bantu</label>
                            <input type="text" name="keterangan_disabilitas" id="keterangan_disabilitas" value="{{ old('keterangan_disabilitas', $warga->keterangan_disabilitas ?? '') }}" class="input input-bordered w-full bg-gray-50 text-xs font-medium" placeholder="Contoh: Menggunakan kursi roda">
                        </div>
                    </div>
                </div>

                <!-- 4. ALASAN PENGAJUAN -->
                <div class="space-y-2 pt-2 border-t">
                    <label class="label text-xs font-bold uppercase text-gray-600">Alasan Permohonan Perubahan Data <span class="text-brandRed">*</span></label>
                    <textarea name="alasan_perubahan" rows="2" required placeholder="Jelaskan secara singkat alasan perubahan data ini..." class="textarea textarea-bordered w-full bg-gray-50 text-xs font-medium">{{ old('alasan_perubahan') }}</textarea>
                    <p class="text-[10px] text-gray-400"><i class="fas fa-info-circle mr-1"></i> Data usulan ini akan diverifikasi terlebih dahulu oleh Ketua RT / Admin sebelum diperbarui di sistem.</p>
                </div>

                <button type="submit" class="btn bg-brandRed hover:bg-brandDarkRed text-white border-none rounded-xl font-bold text-xs gap-2 shadow-sm w-full sm:w-auto justify-center">
                    <i class="fas fa-paper-plane"></i> Kirim Permohonan Perubahan Data
                </button>
            </form>
        </div>

        <!-- TABEL RIWAYAT PENGAJUAN STATUS (1 KOLOM) -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-4 sm:p-6 space-y-4 h-fit">
            <div class="border-b border-gray-100 pb-3">
                <h3 class="text-base font-black text-brandDark uppercase tracking-wide flex items-center gap-2">
                    <i class="fas fa-history text-brandRed"></i> Riwayat Pengajuan Saya
                </h3>
            </div>

            <div class="space-y-3 max-h-[600px] overflow-y-auto">
                @if(isset($riwayatPengajuan) && count($riwayatPengajuan) > 0)
                    @foreach($riwayatPengajuan as $rp)
                        <div class="border border-gray-200 rounded-xl p-3 bg-gray-50/80 text-xs space-y-2">
                            <div class="flex justify-between items-center border-b pb-2 flex-wrap gap-1">
                                <span class="font-bold text-brandDark">{{ $rp->kategori_perubahan }}</span>
                                @if($rp->status == 'pending')
                                    <span class="badge bg-amber-100 text-amber-800 border-amber-200 font-bold text-[10px]">Menunggu Verifikasi</span>
                                @elseif($rp->status == 'disetujui')
                                    <span class="badge bg-emerald-100 text-emerald-800 border-emerald-200 font-bold text-[10px]">Disetujui</span>
                                @else
                                    <span class="badge bg-red-100 text-brandRed border-red-200 font-bold text-[10px]">Ditolak</span>
                                @endif
                            </div>
                            <p class="text-gray-600"><b>Alasan:</b> {{ $rp->alasan_perubahan }}</p>
                            @if($rp->catatan_admin)
                                <div class="bg-white p-2 rounded-lg border text-gray-600 text-[11px]">
                                    <b>Catatan RT/Admin:</b> {{ $rp->catatan_admin }}
                                </div>
                            @endif
                            <div class="text-[10px] text-gray-400 text-right">
                                {{ \Carbon\Carbon::parse($rp->created_at)->format('d M Y, H:i') }} WIB
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="text-center py-8 text-gray-400 text-xs italic">
                        <i class="fas fa-inbox text-3xl mb-2 block text-gray-300"></i>
                        Belum ada riwayat permohonan.
                    </div>
                @endif
            </div>
        </div>

    </div>

</div>
@endsection

@push('scripts')
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

    $(document).ready(function() {
        handleJenisKelaminChange();
    });
</script>
@endpush