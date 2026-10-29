@extends('layouts.admin')

@section('title', 'Kelola Akun Pejabat - SIGAPDESA')

@section('content')
@php
    $adminSession = session('admin');
    $currentUserRole = $userRole ?? strtolower($adminSession['role'] ?? '');
    $isGmlsUser = in_array($currentUserRole, ['gmls', 'admin']);
    $idAdminSaya = (int) ($adminSession['id_admin'] ?? 0);
@endphp

<div class="max-w-6xl mx-auto space-y-5" data-theme="light">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-2">
        <div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 uppercase tracking-tight">Kelola Akun Admin Wilayah</h1>
            <p class="text-xs sm:text-sm text-slate-500 font-medium">Penerbitan akun berjenjang dengan pembatasan hak akses wilayah</p>
        </div>
        
        <div class="shrink-0">
            <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold rounded-xl shadow-sm transition-all border border-slate-700">
                <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
            </a>
        </div>
    </div>

    <!-- NOTIFIKASI ALERT -->
    @if(session('success'))
        <div class="flex items-center gap-3 bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-2xl text-xs font-bold shadow-xs">
            <i class="fas fa-check-circle text-emerald-600 text-base"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="flex items-center gap-3 bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-2xl text-xs font-bold shadow-xs">
            <i class="fas fa-exclamation-triangle text-rose-600 text-base"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- Form Tambah Akun -->
    <div class="bg-white border border-slate-200 shadow-sm p-5 sm:p-6 rounded-2xl space-y-4">
        <h3 class="text-base sm:text-lg font-extrabold text-rose-600 mb-4 flex items-center gap-2 border-b border-slate-100 pb-3">
            <i class="fas fa-user-plus"></i> Form Buat Akun Admin
        </h3>

        @unless($isGmlsUser)
            <div class="flex items-start gap-2.5 p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold">
                <i class="fas fa-lock mt-0.5"></i>
                <span>Akun yang Anda buat otomatis berada di wilayah <b>Desa {{ $namaDesaSaya ?? '-' }}</b> dan hanya bisa melihat data desa ini. Anda dapat membuat akun Kepala Desa, Ketua RW, dan Ketua RT.</span>
            </div>
        @endunless

        <form action="{{ route('admin.user.store') }}" method="POST" class="space-y-4">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-700 mb-1">Nama Pengguna</label>
                    <input type="text" name="nama_pengguna" required placeholder="Contoh: Pak RW 01 Situregen" class="w-full bg-white text-slate-900 border border-slate-300 rounded-xl px-3 py-2.5 text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-rose-500" />
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase text-slate-700 mb-1">Username Login</label>
                    <input type="text" name="username" required placeholder="rw01_situregen" class="w-full bg-white text-slate-900 border border-slate-300 rounded-xl px-3 py-2.5 text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-rose-500" />
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase text-slate-700 mb-1">Password</label>
                    <input type="password" name="password" required placeholder="******" class="w-full bg-white text-slate-900 border border-slate-300 rounded-xl px-3 py-2.5 text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-rose-500" />
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase text-slate-700 mb-1">Jabatan / Role</label>
                    <select name="role" id="select_role" onchange="toggleWilayah()" class="w-full bg-white text-rose-600 border border-slate-300 rounded-xl px-3 py-2.5 text-xs font-extrabold focus:outline-none focus:ring-2 focus:ring-rose-500">
                        <option value="">-- Pilih Role / Jabatan --</option>
                        @if($currentUserRole === 'gmls')
                            <option value="gmls">GMLS (Super Admin - Seluruh Wilayah)</option>
                            <option value="bpbd">BPBD (Pemantau Seluruh Indonesia - Hanya Lihat)</option>
                            <option value="bupati">Bupati (Akses 1 Kabupaten)</option>
                            <option value="camat">Camat (Akses 1 Kecamatan)</option>
                            <option value="pemerintah_desa">Pemerintah Desa (Akses Admin Desa)</option>
                        @endif
                        <option value="kepala_desa">Kepala Desa / Sekdes (Akses 1 Desa)</option>
                        <option value="ketua_rw">Ketua RW (Akses Gabungan RT di RW)</option>
                        <option value="ketua_rt">Ketua RT (Akses 1 RT Spesifik)</option>
                    </select>
                </div>
            </div>

            <!-- CHECKBOX HAK AKSES (Hanya muncul untuk GMLS dan jika memilih Pemerintah Desa) -->
            @if($currentUserRole === 'gmls')
            <div class="p-3 bg-blue-50 border border-blue-200 rounded-xl items-start gap-3 hidden" id="box_izin_akun">
                <div class="flex items-center h-5 mt-0.5">
                    <input type="checkbox" name="can_create_akun" id="can_create_akun" value="1" class="w-4 h-4 text-blue-600 bg-white border-blue-300 rounded focus:ring-blue-500 cursor-pointer">
                </div>
                <div class="text-sm">
                    <label for="can_create_akun" class="font-extrabold text-blue-900 cursor-pointer">Berikan Hak Pembuatan Akun</label>
                    <p class="text-xs text-blue-700 font-medium">Jika dicentang, pengguna ini dapat membuat akun bawahan (hanya sebatas wilayah desanya sendiri).</p>
                </div>
            </div>
            @endif

            <!-- CONTAINER DROPDOWN WILAYAH CASCADING -->
            <div id="box_wilayah" class="p-4 bg-rose-50/60 border border-rose-100 rounded-xl space-y-3 hidden">
                <h4 class="text-xs font-extrabold text-rose-600 uppercase tracking-wider">Cakupan Wilayah Kekuasaan</h4>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    <!-- Provinsi -->
                    <div id="f_provinsi" class="hidden">
                        <label class="block text-xs font-bold text-slate-700 mb-1">Provinsi</label>
                        <select name="id_provinsi" id="provinsi" class="w-full bg-white text-slate-900 border border-slate-300 rounded-xl p-2 text-xs font-semibold">
                            <option value="">-- Pilih Provinsi --</option>
                            @foreach($provinsi as $p)
                                <option value="{{ $p->id_provinsi }}">{{ $p->nama_provinsi }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Kabupaten -->
                    <div id="f_kabupaten" class="hidden">
                        <label class="block text-xs font-bold text-slate-700 mb-1">Kabupaten / Kota</label>
                        <select name="id_kabupaten" id="kabupaten" class="w-full bg-white text-slate-900 border border-slate-300 rounded-xl p-2 text-xs font-semibold">
                            <option value="">-- Pilih Kabupaten --</option>
                        </select>
                    </div>

                    <!-- Kecamatan -->
                    <div id="f_kecamatan" class="hidden">
                        <label class="block text-xs font-bold text-slate-700 mb-1">Kecamatan</label>
                        <select name="id_kecamatan" id="kecamatan" class="w-full bg-white text-slate-900 border border-slate-300 rounded-xl p-2 text-xs font-semibold">
                            <option value="">-- Pilih Kecamatan --</option>
                        </select>
                    </div>

                    <!-- Desa -->
                    <div id="f_desa" class="hidden">
                        <label class="block text-xs font-bold text-slate-700 mb-1">Desa / Kelurahan</label>
                        <select name="id_desa" id="desa" class="w-full bg-white text-slate-900 border border-slate-300 rounded-xl p-2 text-xs font-semibold">
                            <option value="">-- Pilih Desa --</option>
                        </select>
                    </div>

                    <!-- RW -->
                    <div id="f_rw" class="hidden">
                        <label class="block text-xs font-bold text-slate-700 mb-1">Wilayah RW</label>
                        <select name="id_rw" id="rw" class="w-full bg-white text-slate-900 border border-slate-300 rounded-xl p-2 text-xs font-semibold">
                            <option value="">-- Pilih RW --</option>
                        </select>
                    </div>

                    <!-- RT -->
                    <div id="f_rt" class="hidden">
                        <label class="block text-xs font-bold text-slate-700 mb-1">Unit RT</label>
                        <select name="id_rt" id="rt" class="w-full bg-white text-slate-900 border border-slate-300 rounded-xl p-2 text-xs font-semibold">
                            <option value="">-- Pilih RT --</option>
                        </select>
                    </div>
                </div>
            </div>

            <button type="submit" class="w-full px-6 py-3 bg-rose-600 hover:bg-rose-700 text-white font-extrabold text-xs rounded-xl shadow-sm transition inline-flex items-center justify-center gap-2 cursor-pointer">
                <i class="fas fa-save"></i> Terbitkan Akun!
            </button>
        </form>
    </div>

    <!-- TABEL DAFTAR AKUN ADMIN TERDAFTAR -->
    <div class="bg-white border border-slate-200 shadow-sm p-5 sm:p-6 rounded-2xl space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-3">
            <h3 class="text-base sm:text-lg font-extrabold text-slate-900 flex items-center gap-2">
                <i class="fas fa-users-cog text-rose-600"></i> Daftar Akun Admin Terdaftar
            </h3>
            <span class="text-xs text-slate-500 font-bold">
                Total: <b class="text-rose-600">{{ count($users ?? []) }}</b> Akun Aktif
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-slate-100 text-slate-700 uppercase font-extrabold text-[10px] tracking-wider border-b border-slate-200">
                        <th class="py-3.5 px-3 text-center">No</th>
                        <th class="py-3.5 px-3">Nama Pengguna</th>
                        <th class="py-3.5 px-3">Username</th>
                        <th class="py-3.5 px-3 text-center">Role / Jabatan</th>
                        <th class="py-3.5 px-3">Cakupan Wilayah</th>
                        <th class="py-3.5 px-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($users ?? [] as $index => $u)
                        @php
                            $idAdmin = data_get($u, 'id_admin') ?? data_get($u, 'id_user') ?? data_get($u, 'id');
                            $roleVal = strtolower(data_get($u, 'role') ?? 'gmls');
                            $uname = data_get($u, 'username') ?? 'admin';
                            $canCreate = data_get($u, 'can_create_akun') ?? 0;
                        @endphp
                        <tr class="hover:bg-slate-50 transition">
                            <td class="py-3.5 px-3 text-center font-bold text-slate-400">{{ $index + 1 }}</td>
                            <td class="py-3.5 px-3 font-extrabold text-slate-900">
                                {{ data_get($u, 'nama_pengguna') ?? data_get($u, 'nama_lengkap') ?? 'Admin' }}
                            </td>
                            <td class="py-3.5 px-3 font-mono font-semibold text-slate-600">
                                {{ $uname }}
                            </td>
                            <td class="py-3.5 px-3 text-center">
                                @if(in_array($roleVal, ['gmls', 'admin']))
                                    <span class="px-2.5 py-1 rounded-md bg-purple-100 text-purple-800 border border-purple-200 text-[10px] font-extrabold uppercase">GMLS Super Admin</span>
                                @elseif($roleVal === 'bpbd')
                                    <span class="px-2.5 py-1 rounded-md bg-orange-100 text-orange-800 border border-orange-200 text-[10px] font-extrabold uppercase">BPBD</span>
                                @elseif($roleVal === 'bupati')
                                    <span class="px-2.5 py-1 rounded-md bg-blue-100 text-blue-800 border border-blue-200 text-[10px] font-bold uppercase">Bupati</span>
                                @elseif($roleVal === 'camat')
                                    <span class="px-2.5 py-1 rounded-md bg-sky-100 text-sky-800 border border-sky-200 text-[10px] font-bold uppercase">Camat</span>
                                @elseif($roleVal === 'pemerintah_desa')
                                    <span class="px-2.5 py-1 rounded-md bg-indigo-100 text-indigo-800 border border-indigo-200 text-[10px] font-bold uppercase block mb-1">Pemerintah Desa</span>
                                    @if($canCreate)
                                        <span class="px-2 py-0.5 rounded bg-blue-100 text-blue-700 text-[9px] font-bold"><i class="fas fa-key"></i> Hak Buat Akun</span>
                                    @endif
                                @elseif(in_array($roleVal, ['kepala_desa', 'kades', 'sekdes']))
                                    <span class="px-2.5 py-1 rounded-md bg-emerald-100 text-emerald-800 border border-emerald-200 text-[10px] font-bold uppercase">Kepala Desa</span>
                                @elseif(in_array($roleVal, ['ketua_rw', 'rw']))
                                    <span class="px-2.5 py-1 rounded-md bg-amber-100 text-amber-800 border border-amber-200 text-[10px] font-bold uppercase">Ketua RW</span>
                                @elseif(in_array($roleVal, ['ketua_rt', 'rt']))
                                    <span class="px-2.5 py-1 rounded-md bg-rose-100 text-rose-800 border border-rose-200 text-[10px] font-bold uppercase">Ketua RT</span>
                                @else
                                    <span class="px-2.5 py-1 rounded-md bg-slate-100 text-slate-800 text-[10px] font-bold uppercase">{{ $roleVal }}</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-3 text-slate-600">
                                @if(in_array($roleVal, ['gmls', 'admin']))
                                    <span class="text-purple-700 font-extrabold">Nasional / Seluruh Wilayah</span>
                                @elseif($roleVal === 'bpbd')
                                    <span class="text-orange-700 font-extrabold">Seluruh Indonesia <span class="font-semibold text-slate-500">(hanya lihat)</span></span>
                                @else
                                    <div class="flex flex-wrap gap-1 text-[11px]">
                                        @if(data_get($u, 'nama_kabupaten'))
                                            <span class="bg-slate-100 px-2 py-0.5 rounded text-slate-700 font-medium">{{ data_get($u, 'nama_kabupaten') }}</span>
                                        @endif
                                        @if(data_get($u, 'nama_kecamatan'))
                                            <span class="bg-slate-100 px-2 py-0.5 rounded text-slate-700 font-medium">Kec. {{ data_get($u, 'nama_kecamatan') }}</span>
                                        @endif
                                        @if(data_get($u, 'nama_desa'))
                                            <span class="bg-slate-100 px-2 py-0.5 rounded text-slate-700 font-medium">Desa {{ data_get($u, 'nama_desa') }}</span>
                                        @endif
                                        @if(data_get($u, 'nomor_rw'))
                                            <span class="bg-amber-50 text-amber-700 font-bold px-2 py-0.5 rounded">RW {{ sprintf('%02d', data_get($u, 'nomor_rw')) }}</span>
                                        @endif
                                        @if(data_get($u, 'nomor_rt'))
                                            <span class="bg-rose-50 text-rose-700 font-bold px-2 py-0.5 rounded">RT {{ sprintf('%02d', data_get($u, 'nomor_rt')) }}</span>
                                        @endif

                                        @if(!data_get($u, 'nama_kabupaten') && !data_get($u, 'nama_kecamatan') && !data_get($u, 'nama_desa') && !data_get($u, 'nomor_rw') && !data_get($u, 'nomor_rt'))
                                            <span class="text-slate-400 italic">Wilayah belum diatur</span>
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td class="py-3.5 px-3 text-center">
                                @php
                                    // Sama dengan aturan di AdminUserController::destroy
                                    $bisaHapus = $idAdmin && (int) $idAdmin !== $idAdminSaya
                                        && ($isGmlsUser || in_array($roleVal, ['kepala_desa', 'ketua_rw', 'ketua_rt']));
                                @endphp
                                @if(!$bisaHapus)
                                    <span class="text-[10px] text-slate-400 font-semibold">{{ (int) $idAdmin === $idAdminSaya ? 'Akun Anda' : '-' }}</span>
                                @else
                                    <form action="{{ route('admin.user.destroy', $idAdmin) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus akun admin ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold transition shadow-xs cursor-pointer" title="Hapus Akun Admin">
                                            <i class="fas fa-trash-alt text-[10px]"></i> Hapus
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-6 text-slate-400 font-medium">
                                Belum ada akun admin wilayah terdaftar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- SCRIPT DROPDOWN CASCADING -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // Event listener untuk select_role
    $('#select_role').on('change', function() {
        toggleWilayah();
        
        // Memunculkan checkbox hak akses khusus jika GMLS memilih Pemerintah Desa
        if($(this).val() === 'pemerintah_desa') {
            $('#box_izin_akun').removeClass('hidden').addClass('flex');
        } else {
            $('#box_izin_akun').removeClass('flex').addClass('hidden');
            $('#can_create_akun').prop('checked', false);
        }
    });

    // Jalankan satu kali saat halaman dimuat
    toggleWilayah();

    $('#provinsi').on('change', function() {
        let id = $(this).val();
        $('#kabupaten').html('<option value="">Loading...</option>');
        $('#kecamatan').html('<option value="">-- Pilih Kecamatan --</option>');
        $('#desa').html('<option value="">-- Pilih Desa --</option>');

        if (id) {
            $.ajax({
                url: '{{ route('get.kabupaten') }}',
                type: 'GET',
                data: { id_provinsi: id, provinsi_id: id },
                dataType: 'json',
                success: function(data) {
                    let options = '<option value="">-- Pilih Kabupaten --</option>';
                    if (data && data.length > 0) {
                        $.each(data, function(key, val) {
                            options += `<option value="${val.id_kabupaten}">${val.nama_kabupaten}</option>`;
                        });
                    } else {
                        options = '<option value="">(Tidak ada data kabupaten)</option>';
                    }
                    $('#kabupaten').html(options);
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error Kabupaten:", error);
                    $('#kabupaten').html('<option value="">Gagal memuat data</option>');
                }
            });
        } else {
            $('#kabupaten').html('<option value="">-- Pilih Kabupaten --</option>');
        }
    });

    $('#kabupaten').on('change', function() {
        let id = $(this).val();
        $('#kecamatan').html('<option value="">Loading...</option>');
        $('#desa').html('<option value="">-- Pilih Desa --</option>');

        if (id) {
            $.ajax({
                url: '{{ route('get.kecamatan') }}',
                type: 'GET',
                data: { id_kabupaten: id, kabupaten_id: id },
                dataType: 'json',
                success: function(data) {
                    let options = '<option value="">-- Pilih Kecamatan --</option>';
                    if (data && data.length > 0) {
                        $.each(data, function(key, val) {
                            options += `<option value="${val.id_kecamatan}">${val.nama_kecamatan}</option>`;
                        });
                    } else {
                        options = '<option value="">(Tidak ada data kecamatan)</option>';
                    }
                    $('#kecamatan').html(options);
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error Kecamatan:", error);
                    $('#kecamatan').html('<option value="">Gagal memuat data</option>');
                }
            });
        } else {
            $('#kecamatan').html('<option value="">-- Pilih Kecamatan --</option>');
        }
    });

    $('#kecamatan').on('change', function() {
        let id = $(this).val();
        $('#desa').html('<option value="">Loading...</option>');

        if (id) {
            $.ajax({
                url: '{{ route('get.desa') }}',
                type: 'GET',
                data: { id_kecamatan: id, kecamatan_id: id },
                dataType: 'json',
                success: function(data) {
                    let options = '<option value="">-- Pilih Desa --</option>';
                    if (data && data.length > 0) {
                        $.each(data, function(key, val) {
                            options += `<option value="${val.id_desa}">${val.nama_desa}</option>`;
                        });
                    } else {
                        options = '<option value="">(Tidak ada data desa)</option>';
                    }
                    $('#desa').html(options);
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error Desa:", error);
                    $('#desa').html('<option value="">Gagal memuat data</option>');
                }
            });
        } else {
            $('#desa').html('<option value="">-- Pilih Desa --</option>');
        }
    });
});

function toggleWilayah() {
    const role = $('#select_role').val();
    const userRole = '{{ $currentUserRole }}';
    
    $('#box_wilayah').addClass('hidden');
    $('#f_provinsi, #f_kabupaten, #f_kecamatan, #f_desa, #f_rw, #f_rt').addClass('hidden');

    if (!role || role === 'gmls' || role === 'bpbd') return; // cakupan nasional, tanpa pilihan wilayah

    $('#box_wilayah').removeClass('hidden');

    // Akun desa (Pemdes dengan hak buat akun): desa terkunci, hanya RW/RT yang dipilih
    if (!['gmls', 'admin'].includes(userRole)) {
        if (role === 'kepala_desa') {
            $('#box_wilayah').addClass('hidden');
        } else if (role === 'ketua_rw') {
            $('#f_rw').removeClass('hidden');
            loadRwOptions();
        } else if (role === 'ketua_rt') {
            $('#f_rw, #f_rt').removeClass('hidden');
            loadRwOptions();
            loadRtOptions();
        }
        return; // Hentikan agar field provinsi, dll tidak muncul
    }

    // Default kondisi jika yang login GMLS
    if (role === 'bupati') {
        $('#f_provinsi, #f_kabupaten').removeClass('hidden');
    } else if (role === 'camat') {
        $('#f_provinsi, #f_kabupaten, #f_kecamatan').removeClass('hidden');
    } else if (role === 'pemerintah_desa' || role === 'kepala_desa') {
        $('#f_provinsi, #f_kabupaten, #f_kecamatan, #f_desa').removeClass('hidden');
    } else if (role === 'ketua_rw') {
        $('#f_provinsi, #f_kabupaten, #f_kecamatan, #f_desa, #f_rw').removeClass('hidden');
        loadRwOptions();
    } else if (role === 'ketua_rt') {
        $('#f_provinsi, #f_kabupaten, #f_kecamatan, #f_desa, #f_rw, #f_rt').removeClass('hidden');
        loadRwOptions();
        loadRtOptions();
    }
}

function loadRwOptions() {
    if ($('#rw option').length <= 1) {
        $.get('{{ route('get.rw') }}', function(data) {
            let options = '<option value="">-- Pilih RW --</option>';
            $.each(data, function(key, val) {
                options += `<option value="${val.id_rw}">RW ${val.nomor_rw}</option>`;
            });
            $('#rw').html(options);
        });
    }
}

function loadRtOptions() {
    if ($('#rt option').length <= 1) {
        $.get('{{ route('get.rt') }}', function(data) {
            let options = '<option value="">-- Pilih RT --</option>';
            $.each(data, function(key, val) {
                options += `<option value="${val.id_rt}">RT ${val.nomor_rt}</option>`;
            });
            $('#rt').html(options);
        });
    }
}
</script>
@endsection