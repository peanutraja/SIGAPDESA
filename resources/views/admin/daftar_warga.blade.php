@extends('layouts.admin')

@section('title', 'Daftar Data Penduduk - SIGAPDESA')

@push('styles')
<link href="https://cdn.datatables.net/1.13.5/css/jquery.dataTables.min.css" rel="stylesheet">
<style>
    /* CUSTOM DATATABLES STYLING FOR MOBILE & DESKTOP */
    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_filter {
        margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;
    }
    .dataTables_wrapper .dataTables_filter { justify-content: flex-end; }
    .dataTables_wrapper .dataTables_length select { border-radius: 0.75rem !important; border: 1px solid #e2e8f0 !important; padding: 0.4rem 2rem 0.4rem 0.75rem !important; background-color: #f8fafc !important; font-weight: 600; font-size: 0.825rem; }
    .dataTables_wrapper .dataTables_filter input { border-radius: 0.75rem !important; border: 1px solid #cbd5e1 !important; padding: 0.45rem 0.85rem !important; background-color: #ffffff !important; font-size: 0.825rem; outline: none; transition: all 0.2s; }
    .dataTables_wrapper .dataTables_filter input:focus { border-color: #be123c !important; box-shadow: 0 0 0 3px rgba(190, 18, 60, 0.1) !important; }
    .dataTables_wrapper .dataTables_info,
    .dataTables_wrapper .dataTables_paginate { margin-top: 1rem; font-size: 0.8rem; color: #64748b; }
    .dataTables_wrapper .dataTables_paginate .paginate_button.current { background: #be123c !important; color: #ffffff !important; border: none !important; border-radius: 0.5rem !important; font-weight: 700 !important; }
    .dataTables_wrapper .dataTables_paginate .paginate_button:hover { background: #f1f5f9 !important; color: #0f172a !important; border-radius: 0.5rem !important; border: 1px solid transparent !important; }
    .dataTables_scrollBody::-webkit-scrollbar { height: 6px; }
    .dataTables_scrollBody::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    tr.warga-row, tr.pendatang-row { cursor: pointer; user-select: none; }
    tr.warga-row:hover, tr.pendatang-row:hover { background-color: #f1f5f9 !important; }
</style>
@endpush

@section('content')
@php
    $adminSess = session('admin') ?? (auth()->check() ? auth()->user() : null);
    $userRole = 'gmls';
    if (is_array($adminSess)) $userRole = $adminSess['role'] ?? 'gmls';
    else if (is_object($adminSess)) $userRole = $adminSess->role ?? 'gmls';

    $canManageData = in_array($userRole, ['ketua_rt', 'rt', 'gmls', 'admin']);
@endphp

<div x-data="{ activeTab: 'warga' }" id="content" class="max-w-7xl mx-auto space-y-4 sm:space-y-6">

    <!-- HEADER & ACTION BAR -->
    <div class="bg-white rounded-2xl p-4 sm:p-6 border border-slate-200/80 shadow-sm flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1 flex-wrap">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-rose-50 text-brandRed border border-rose-100">
                    <i class="fas fa-users text-[10px]"></i> Data Penduduk Wilayah
                </span>
                <span class="text-xs text-slate-400 font-medium">Total: <b>{{ count($warga) + count($pendatang) }}</b> Data Terdaftar</span>
                <span class="text-[10px] px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 font-extrabold uppercase">Role: {{ str_replace('_', ' ', $userRole) }}</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight uppercase">Daftar Warga & Pendatang</h1>
            <p class="text-xs text-slate-500">
                @if($canManageData) Klik / tekan baris tabel untuk melihat detail, mengedit, atau menghapus data.
                @else Klik baris tabel untuk melihat detail informasi lengkap penduduk (Mode Read-Only). @endif
            </p>
        </div>

        <div class="flex items-center gap-2 sm:gap-3 flex-wrap sm:flex-nowrap w-full sm:w-auto">
            @if($canManageData)
                <a href="{{ route('penduduk.create') }}" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl bg-brandRed hover:bg-brandDarkRed text-white px-4 py-2.5 text-xs font-bold shadow-sm transition-all">
                    <i class="fas fa-plus"></i> Tambah Warga
                </a>
            @endif
            <a href="{{ route('dashboard') }}" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 px-4 py-2.5 text-xs font-bold transition-all">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    @if(session('success')) <div class="flex items-center gap-3 bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-2xl text-xs font-semibold shadow-sm"><i class="fas fa-check-circle text-emerald-600 text-base"></i><span>{{ session('success') }}</span></div> @endif
    @if(session('error')) <div class="flex items-center gap-3 bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-2xl text-xs font-semibold shadow-sm"><i class="fas fa-exclamation-triangle text-rose-600 text-base"></i><span>{{ session('error') }}</span></div> @endif

    <!-- FILTER FORM (ACCORDION) -->
    <details id="filterForm" class="group rounded-2xl border border-slate-200/80 bg-white shadow-sm overflow-hidden transition-all">
        <summary class="flex cursor-pointer items-center justify-between px-4 py-3.5 sm:px-6 font-bold text-slate-800 hover:bg-slate-50/80 select-none">
            <div class="flex items-center gap-2.5 text-xs sm:text-sm"><div class="w-7 h-7 rounded-lg bg-rose-50 text-brandRed flex items-center justify-center text-xs"><i class="fas fa-filter"></i></div><span>Saring & Filter Data Wilayah</span></div>
            <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-brandRed bg-rose-50 px-3 py-1 rounded-full border border-rose-100 group-open:rotate-180 transition-transform"><i class="fas fa-chevron-down"></i></span>
        </summary>
        <div class="p-4 sm:p-6 border-t border-slate-100 bg-slate-50/50 space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                <div>
                    <label class="block text-[11px] font-extrabold uppercase text-slate-500 mb-1">Provinsi</label>
                    <select id="filterProvinsi" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium focus:border-brandRed focus:outline-none">
                        <option value="">-- Semua Provinsi --</option>
                        @foreach($provinsi as$p) <option value="{{ $p->nama_provinsi }}" data-id="{{ $p->id_provinsi }}">{{ $p->nama_provinsi }}</option> @endforeach
                    </select>
                </div>
                <div><label class="block text-[11px] font-extrabold uppercase text-slate-500 mb-1">Kabupaten / Kota</label><select id="filterKabupaten" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium focus:border-brandRed"><option value="">-- Semua Kabupaten --</option></select></div>
                <div><label class="block text-[11px] font-extrabold uppercase text-slate-500 mb-1">Kecamatan</label><select id="filterKecamatan" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium focus:border-brandRed"><option value="">-- Semua Kecamatan --</option></select></div>
                <div><label class="block text-[11px] font-extrabold uppercase text-slate-500 mb-1">Desa / Kelurahan</label><select id="filterDesa" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium focus:border-brandRed"><option value="">-- Semua Desa --</option></select></div>
                <div><label class="block text-[11px] font-extrabold uppercase text-slate-500 mb-1">Nomor RT</label><input type="text" id="filterRT" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium focus:border-brandRed" placeholder="Contoh: 01"></div>
                <div><label class="block text-[11px] font-extrabold uppercase text-slate-500 mb-1">Nomor RW</label><input type="text" id="filterRW" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium focus:border-brandRed" placeholder="Contoh: 02"></div>
            </div>
            <div class="flex items-center gap-2 pt-2">
                <button id="applyFilter" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brandRed hover:bg-brandDarkRed text-white px-5 py-2 text-xs font-bold shadow-sm transition-all"><i class="fas fa-search text-xs"></i> Terapkan Filter</button>
                <button id="resetFilter" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-100 text-slate-700 px-5 py-2 text-xs font-bold transition-all"><i class="fas fa-undo text-xs"></i> Reset</button>
            </div>
        </div>
    </details>

    <div class="bg-slate-100 border border-slate-200 rounded-xl px-4 py-2 flex items-center justify-between text-[11px] text-slate-600 font-medium">
        <span class="flex items-center gap-2"><i class="fas fa-info-circle text-brandRed"></i><span><b>Laptop:</b> Klik baris / klik kanan untuk menu. | <b>HP:</b> Sentuh baris warga.</span></span>
    </div>

    <!-- SISTEM TAB ALPINE.JS -->
    <div class="flex items-center gap-2 overflow-x-auto pb-1">
        <button type="button" @click="activeTab = 'warga'; setTimeout(() => table.columns.adjust(), 150);" :class="activeTab === 'warga' ? 'bg-brandRed text-black shadow-md font-extrabold' : 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-200 font-bold'" class="px-5 py-2.5 rounded-2xl text-xs transition-all flex items-center gap-2 shrink-0">
            <i class="fas fa-home"></i> Warga Tetap
        </button>
        <button type="button" @click="activeTab = 'pendatang'; setTimeout(() => tablePendatang.columns.adjust(), 150);" :class="activeTab === 'pendatang' ? 'bg-sky-600 text-black shadow-md font-extrabold' : 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-200 font-bold'" class="px-5 py-2.5 rounded-2xl text-xs transition-all flex items-center gap-2 shrink-0">
            <i class="fas fa-walking"></i> Pendatang / Tamu
        </button>
    </div>

    <div class="rounded-2xl border border-slate-200/80 bg-white p-3 sm:p-5 shadow-sm overflow-hidden">
        <!-- TAB 1: WARGA TETAP -->
        <div x-show="activeTab === 'warga'" class="overflow-x-auto">
            <table class="w-full text-left text-xs divide-y divide-slate-200" id="wargaTable" style="width:100%">
                <thead>
                    <tr class="bg-slate-900 text-white font-bold uppercase tracking-wider text-[11px]">
                        <th class="px-3 py-3.5 text-center">#</th><th class="px-3 py-3.5">NIK</th><th class="px-3 py-3.5">Nama Lengkap</th>
                        <th class="px-3 py-3.5">Tempat / Umur</th><th class="px-3 py-3.5 text-center">JK</th><th class="px-3 py-3.5">Alamat Lengkap</th>
                        <th class="px-3 py-3.5 text-center">RT</th><th class="px-3 py-3.5 text-center">RW</th><th class="px-3 py-3.5">Desa</th>
                        <th class="px-3 py-3.5">Kecamatan</th><th class="px-3 py-3.5">Kabupaten</th><th class="px-3 py-3.5">Provinsi</th>
                        <th class="px-3 py-3.5 text-center">Gol. Darah</th><th class="px-3 py-3.5">Agama</th><th class="px-3 py-3.5">Status</th>
                        <th class="px-3 py-3.5">Pekerjaan</th><th class="px-2 py-3.5 text-center bg-slate-900 text-white w-8"><i class="fas fa-ellipsis-v"></i></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white text-slate-700">
                    @foreach($warga as $index =>$row)
                    <tr class="warga-row transition-colors" data-warga='@json($row)'>
                        <td class="px-3 py-3 text-center font-bold text-slate-400">{{ $index + 1 }}</td>
                        <td class="px-3 py-3 font-mono font-semibold text-slate-900 whitespace-nowrap"><span class="px-2 py-0.5 rounded-md bg-slate-100 border border-slate-200 text-[11px]">{{ $row->nik }}</span></td>
                        <td class="px-3 py-3 font-bold text-slate-900 whitespace-nowrap">{{ $row->nama_lengkap }}</td>
                        <td class="px-3 py-3 whitespace-nowrap"><span class="font-medium text-slate-800">{{ $row->tempat_lahir }}</span><span class="inline-block ml-1 px-1.5 py-0.5 rounded bg-rose-50 text-brandRed font-bold text-[10px]">{{ !empty($row->tanggal_lahir) ? \Carbon\Carbon::parse($row->tanggal_lahir)->age . ' Thn' : '-' }}</span></td>
                        <td class="px-3 py-3 text-center whitespace-nowrap">@if(strtoupper($row->jenis_kelamin ?? '') == 'L' || strtolower($row->jenis_kelamin ?? '') == 'laki-laki') <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-600 border border-blue-100">L</span> @else <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-pink-50 text-pink-600 border border-pink-100">P</span> @endif</td>
                        <td class="px-3 py-3 max-w-xs truncate" title="{{ $row->alamat_lengkap }}">{{ $row->alamat_lengkap }}</td>
                        <td class="px-3 py-3 text-center font-bold text-slate-700">{{ $row->nomor_rt ?? '-' }}</td><td class="px-3 py-3 text-center font-bold text-slate-700">{{ $row->nomor_rw ?? '-' }}</td>
                        <td class="px-3 py-3 whitespace-nowrap">{{ $row->nama_desa ?? '-' }}</td><td class="px-3 py-3 whitespace-nowrap">{{ $row->nama_kecamatan ?? '-' }}</td>
                        <td class="px-3 py-3 whitespace-nowrap">{{ $row->nama_kabupaten ?? '-' }}</td><td class="px-3 py-3 whitespace-nowrap">{{ $row->nama_provinsi ?? '-' }}</td>
                        <td class="px-3 py-3 text-center font-bold whitespace-nowrap"><span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 text-[10px]">{{ $row->golongan_darah ?? '-' }}</span></td>
                        <td class="px-3 py-3 whitespace-nowrap">{{ $row->agama ?? '-' }}</td><td class="px-3 py-3 whitespace-nowrap">{{ $row->status_perkawinan ?? '-' }}</td>
                        <td class="px-3 py-3 whitespace-nowrap font-medium text-slate-600">{{ $row->pekerjaan ?? '-' }}</td>
                        <td class="px-2 py-3 text-center bg-white/95 text-slate-400"><i class="fas fa-chevron-right text-[10px]"></i></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- TAB 2: PENDATANG -->
        <div x-show="activeTab === 'pendatang'" class="overflow-x-auto" style="display: none;">
            <table class="w-full text-left text-xs divide-y divide-slate-200" id="pendatangTable" style="width:100%">
                <thead>
                    <tr class="bg-sky-900 text-white font-bold uppercase tracking-wider text-[11px]">
                        <th class="px-3 py-3.5 text-center">#</th><th class="px-3 py-3.5">NIK</th><th class="px-3 py-3.5">Nama Lengkap</th>
                        <th class="px-3 py-3.5">Asal Daerah</th><th class="px-3 py-3.5">Tujuan & Lama</th><th class="px-3 py-3.5">No. Telp</th>
                        <th class="px-3 py-3.5 text-center">RT (Tinggal)</th><th class="px-3 py-3.5 text-center">RW (Tinggal)</th><th class="px-3 py-3.5">Desa</th>
                        <th class="px-3 py-3.5">Kecamatan</th><th class="px-3 py-3.5">Kabupaten</th><th class="px-3 py-3.5">Provinsi</th>
                        <th class="px-2 py-3.5 text-center bg-sky-900 text-white w-8"><i class="fas fa-ellipsis-v"></i></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white text-slate-700">
                    @foreach($pendatang as $index =>$p)
                    <tr class="pendatang-row transition-colors" data-pendatang='@json($p)'>
                        <td class="px-3 py-3 text-center font-bold text-slate-400">{{ $index + 1 }}</td>
                        <td class="px-3 py-3 font-mono font-semibold text-slate-900 whitespace-nowrap"><span class="px-2 py-0.5 rounded-md bg-slate-100 border border-slate-200 text-[11px]">{{ $p->nik }}</span></td>
                        <td class="px-3 py-3 font-bold text-slate-900 whitespace-nowrap">{{ $p->nama_lengkap }}</td>
                        <td class="px-3 py-3 max-w-xs truncate" title="{{ $p->asal_daerah }}">{{ $p->asal_daerah }}</td>
                        <td class="px-3 py-3 whitespace-nowrap text-sky-700 font-semibold">{{ $p->tujuan_kunjungan }} ({{ $p->lama_kunjungan }} Hari)</td>
                        <td class="px-3 py-3 whitespace-nowrap">{{ $p->nomor_telepon }}</td>
                        <td class="px-3 py-3 text-center font-bold text-slate-700">{{ $p->nomor_rt ?? '-' }}</td><td class="px-3 py-3 text-center font-bold text-slate-700">{{ $p->nomor_rw ?? '-' }}</td>
                        <td class="px-3 py-3 whitespace-nowrap">{{ $p->nama_desa ?? '-' }}</td><td class="px-3 py-3 whitespace-nowrap">{{ $p->nama_kecamatan ?? '-' }}</td>
                        <td class="px-3 py-3 whitespace-nowrap">{{ $p->nama_kabupaten ?? '-' }}</td><td class="px-3 py-3 whitespace-nowrap">{{ $p->nama_provinsi ?? '-' }}</td>
                        <td class="px-2 py-3 text-center bg-white/95 text-slate-400"><i class="fas fa-chevron-right text-[10px]"></i></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- CONTEXT MENU MELAYANG (KLIK KANAN DESKTOP) -->
<div id="contextMenu" class="fixed z-50 hidden bg-white rounded-2xl border border-slate-200 shadow-2xl w-52 py-2 text-xs font-semibold divide-y divide-slate-100">
    <div class="px-3.5 py-1.5 text-[10px] font-extrabold text-slate-400 uppercase tracking-wider" id="cm_title">Pilihan Aksi</div>
    <div class="py-1">
        <button type="button" id="cm_btn_detail" class="w-full text-left px-3.5 py-2 hover:bg-slate-100 text-slate-800 flex items-center gap-2.5 transition">
            <i class="fas fa-eye text-sky-600 text-xs w-4"></i> Detail Informasi
        </button>
        @if($canManageData)
            <a id="cm_btn_edit" href="#" class="w-full text-left px-3.5 py-2 hover:bg-slate-100 text-slate-800 flex items-center gap-2.5 transition">
                <i class="fas fa-pen text-amber-500 text-xs w-4"></i> Edit Data Warga
            </a>
        @endif
    </div>
    @if($canManageData)
        <div class="py-1">
            <button type="button" id="cm_btn_delete" class="w-full text-left px-3.5 py-2 hover:bg-rose-50 text-rose-600 flex items-center gap-2.5 transition font-bold">
                <i class="fas fa-trash-alt text-rose-600 text-xs w-4"></i> Hapus Data
            </button>
        </div>
    @endif
</div>

<!-- MODAL POP-UP DETAIL WARGA & PENDATANG -->
<div id="actionDetailModal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-lg w-full p-5 sm:p-6 shadow-2xl space-y-4 border border-slate-100 relative max-h-[90vh] overflow-y-auto">
        <button onclick="closeActionModal()" class="absolute right-4 top-4 text-slate-400 hover:text-slate-700 text-lg w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center">✕</button>
        
        <div class="flex items-center gap-3 border-b border-slate-100 pb-3">
            <div id="m_icon_bg" class="w-10 h-10 rounded-xl bg-slate-900 text-white flex items-center justify-center font-bold text-lg shadow-sm">
                <i id="m_icon" class="fas fa-user-check"></i>
            </div>
            <div>
                <h3 class="font-bold text-base text-slate-900 uppercase tracking-tight" id="m_nama_lengkap">-</h3>
                <p class="text-xs font-mono text-slate-500" id="m_nik">NIK: -</p>
            </div>
        </div>

        @if($canManageData)
            <div class="grid grid-cols-2 gap-2 pt-1" id="m_action_buttons">
                <a id="m_btn_edit" href="#" class="inline-flex items-center justify-center gap-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-white px-4 py-2.5 text-xs font-bold transition-all shadow-sm">
                    <i class="fas fa-pen"></i> Edit Data
                </a>
                <button id="m_btn_delete" type="button" class="inline-flex items-center justify-center gap-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white px-4 py-2.5 text-xs font-bold transition-all shadow-sm">
                    <i class="fas fa-trash-alt"></i> Hapus Warga
                </button>
            </div>
        @endif

        <div class="space-y-2 text-xs pt-2">
            <div class="font-bold text-slate-400 uppercase text-[10px] tracking-wider">Ringkasan Profil</div>
            
            <!-- BLOK WARGA TETAP -->
            <div id="blok_warga" class="space-y-2 hidden">
                <div class="grid grid-cols-2 gap-2 bg-slate-50 p-3 rounded-xl border border-slate-100">
                    <div><span class="text-slate-400 block font-bold uppercase text-[10px]">Tempat, Tgl Lahir:</span> <span id="m_ttl" class="font-semibold text-slate-800">-</span></div>
                    <div><span class="text-slate-400 block font-bold uppercase text-[10px]">Jenis Kelamin:</span> <span id="m_jk" class="font-semibold text-slate-800">-</span></div>
                </div>
                <div class="grid grid-cols-2 gap-2 bg-slate-50 p-3 rounded-xl border border-slate-100">
                    <div><span class="text-slate-400 block font-bold uppercase text-[10px]">Golongan Darah:</span> <span id="m_gol_darah" class="font-semibold text-slate-800">-</span></div>
                    <div><span class="text-slate-400 block font-bold uppercase text-[10px]">Agama:</span> <span id="m_agama" class="font-semibold text-slate-800">-</span></div>
                </div>
                <div class="grid grid-cols-2 gap-2 bg-slate-50 p-3 rounded-xl border border-slate-100">
                    <div><span class="text-slate-400 block font-bold uppercase text-[10px]">Status Perkawinan:</span> <span id="m_status_perkawinan" class="font-semibold text-slate-800">-</span></div>
                    <div><span class="text-slate-400 block font-bold uppercase text-[10px]">Pekerjaan:</span> <span id="m_pekerjaan" class="font-semibold text-slate-800">-</span></div>
                </div>
            </div>

            <!-- BLOK PENDATANG -->
            <div id="blok_pendatang" class="space-y-2 hidden">
                <div class="grid grid-cols-1 gap-2 bg-sky-50 p-3 rounded-xl border border-sky-100">
                    <div><span class="text-sky-600 block font-bold uppercase text-[10px]">Asal Daerah Lengkap:</span> <span id="m_asal_daerah" class="font-semibold text-slate-800">-</span></div>
                </div>
                <div class="grid grid-cols-2 gap-2 bg-sky-50 p-3 rounded-xl border border-sky-100">
                    <div><span class="text-sky-600 block font-bold uppercase text-[10px]">Tujuan Kunjungan:</span> <span id="m_tujuan" class="font-semibold text-slate-800">-</span></div>
                    <div><span class="text-sky-600 block font-bold uppercase text-[10px]">Lama Menetap:</span> <span id="m_lama" class="font-semibold text-slate-800">-</span></div>
                </div>
                <div class="grid grid-cols-1 gap-2 bg-sky-50 p-3 rounded-xl border border-sky-100">
                    <div><span class="text-sky-600 block font-bold uppercase text-[10px]">Nomor Telp / WA:</span> <span id="m_telp" class="font-semibold text-slate-800">-</span></div>
                </div>
            </div>

            <div class="bg-slate-50 p-3 rounded-xl border border-slate-100 space-y-1">
                <span class="text-slate-400 block font-bold uppercase text-[10px]">Alamat & Wilayah (Tempat Tinggal):</span>
                <p id="m_alamat" class="font-medium text-slate-800">-</p>
                <div class="text-[11px] text-slate-500 pt-1 font-semibold" id="m_wilayah_lengkap">-</div>
            </div>
        </div>
    </div>
</div>

@if($canManageData)
<form id="deleteWargaForm" method="POST" class="hidden">
    @csrf @method('DELETE')
</form>
<div id="deleteConfirmModal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-sm w-full p-6 text-center space-y-4 shadow-2xl border border-slate-100">
        <div class="w-12 h-12 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center mx-auto text-xl font-bold"><i class="fas fa-exclamation-triangle"></i></div>
        <div>
            <h3 class="font-bold text-base text-slate-900">Hapus Data?</h3>
            <p class="text-xs text-slate-500 mt-1">Apakah Anda yakin ingin menghapus <span id="deleteTargetNama" class="font-bold text-slate-800"></span>? Tindakan ini tidak dapat dibatalkan.</p>
        </div>
        <div class="flex items-center gap-2 pt-2">
            <button onclick="closeDeleteModal()" class="w-1/2 rounded-xl border border-slate-200 bg-white hover:bg-slate-100 text-slate-700 py-2.5 text-xs font-bold transition">Batal</button>
            <button onclick="submitDeleteWarga()" class="w-1/2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white py-2.5 text-xs font-bold transition shadow">Ya, Hapus</button>
        </div>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
<script>
let table, tablePendatang;
const canManageData = {{ $canManageData ? 'true' : 'false' }};

$(document).ready(function () {
    const dtConfig = {
        scrollX: true, pageLength: 10,
        language: { search: "", searchPlaceholder: "Cari NIK / Nama...", lengthMenu: "Tampilkan _MENU_ data", info: "Menampilkan _START_ - _END_ dari _TOTAL_ data", infoEmpty: "Tidak ada data", infoFiltered: "(disaring dari _MAX_ data)", paginate: { first: "Awal", last: "Akhir", next: "›", previous: "‹" } }
    };

    table = $('#wargaTable').DataTable(dtConfig);
    tablePendatang = $('#pendatangTable').DataTable(dtConfig);

    $('#wargaTable tbody, #pendatangTable tbody').on('click', 'tr.warga-row, tr.pendatang-row', function (e) {
        let isPendatang = $(this).hasClass('pendatang-row');
        let rowData = isPendatang ? $(this).data('pendatang') : $(this).data('warga');
        if (rowData) openActionModal(rowData, isPendatang ? 'pendatang' : 'warga');
    });

    $('#wargaTable tbody, #pendatangTable tbody').on('contextmenu', 'tr.warga-row, tr.pendatang-row', function (e) {
        e.preventDefault();
        let isPendatang = $(this).hasClass('pendatang-row');
        let rowData = isPendatang ? $(this).data('pendatang') : $(this).data('warga');
        if (rowData) showContextMenu(e, rowData, isPendatang ? 'pendatang' : 'warga');
    });

    let touchTimer;
    $('#wargaTable tbody, #pendatangTable tbody').on('touchstart', 'tr.warga-row, tr.pendatang-row', function (e) {
        let isPendatang = $(this).hasClass('pendatang-row');
        let rowData = isPendatang ? $(this).data('pendatang') : $(this).data('warga');
        touchTimer = setTimeout(function() { if (rowData) openActionModal(rowData, isPendatang ? 'pendatang' : 'warga'); }, 500);
    }).on('touchend touchmove', 'tr.warga-row, tr.pendatang-row', function () { clearTimeout(touchTimer); });

    $(document).on('click', function () { $('#contextMenu').addClass('hidden'); });

    $('#filterProvinsi').change(function () {
        let provId = $(this).find(":selected").data("id");
        $('#filterKabupaten, #filterKecamatan, #filterDesa').html('<option value="">-- Semua --</option>');
        if (provId) $.get("{{ route('get.kabupaten') }}?provinsi_id=" + provId, function (data) { data.forEach(k => $('#filterKabupaten').append(`<option value="${k.nama_kabupaten}" data-id="${k.id_kabupaten}">${k.nama_kabupaten}</option>`)); });
    });
    $('#filterKabupaten').change(function () {
        let id = $(this).find(":selected").data("id");
        $('#filterKecamatan, #filterDesa').html('<option value="">-- Semua --</option>');
        if (id) $.get("{{ route('get.kecamatan') }}?kabupaten_id=" + id, function (data) { data.forEach(k => $('#filterKecamatan').append(`<option value="${k.nama_kecamatan}" data-id="${k.id_kecamatan}">${k.nama_kecamatan}</option>`)); });
    });
    $('#filterKecamatan').change(function () {
        let id = $(this).find(":selected").data("id");
        $('#filterDesa').html('<option value="">-- Semua --</option>');
        if (id) $.get("{{ route('get.desa') }}?kecamatan_id=" + id, function (data) { data.forEach(d => $('#filterDesa').append(`<option value="${d.nama_desa}">${d.nama_desa}</option>`)); });
    });

    $('#applyFilter').click(function () {
        // Filter Warga Table (Indeks Kolom Warga: RT=6, RW=7, Desa=8, Kec=9, Kab=10, Prov=11)
        table.column(11).search($('#filterProvinsi').val()); table.column(10).search($('#filterKabupaten').val());
        table.column(9).search($('#filterKecamatan').val()); table.column(8).search($('#filterDesa').val());
        table.column(6).search($('#filterRT').val()); table.column(7).search($('#filterRW').val()); table.draw();
        // Filter Pendatang Table (Indeks Kolom Pendatang disesuaikan: RT=6, RW=7, Desa=8, Kec=9, Kab=10, Prov=11)
        tablePendatang.column(11).search($('#filterProvinsi').val()); tablePendatang.column(10).search($('#filterKabupaten').val());
        tablePendatang.column(9).search($('#filterKecamatan').val()); tablePendatang.column(8).search($('#filterDesa').val());
        tablePendatang.column(6).search($('#filterRT').val()); tablePendatang.column(7).search($('#filterRW').val()); tablePendatang.draw();
    });

    $('#resetFilter').click(function () {
        $('#filterForm select, #filterForm input').val('');
        table.search('').columns().search('').draw(); tablePendatang.search('').columns().search('').draw();
    });
});

function showContextMenu(e, row, tipe) {
    let idWarga = row.id_warga || row.id_pengunjung || row.id;
    let editUrl = tipe === 'warga' ? "{{ url('/warga') }}/" + idWarga + "/edit" : "#"; // Fitur edit pendatang bisa ditambahkan nanti
    let destroyUrl = tipe === 'warga' ? "{{ url('/warga') }}/" + idWarga : "{{ url('/pengunjung') }}/" + idWarga; 

    $('#cm_title').text(row.nama_lengkap || 'Pilihan Aksi');
    
    if (canManageData) {
        if (tipe === 'warga') { $('#cm_btn_edit').show().attr('href', editUrl); } else { $('#cm_btn_edit').hide(); }
        $('#cm_btn_delete').off('click').on('click', function() { $('#contextMenu').addClass('hidden'); confirmDeleteWarga(destroyUrl, row.nama_lengkap); });
    }

    $('#cm_btn_detail').off('click').on('click', function() { $('#contextMenu').addClass('hidden'); openActionModal(row, tipe); });
    $('#contextMenu').css({ top: e.pageY + 'px', left: e.pageX + 'px' }).removeClass('hidden');
}

function openActionModal(row, tipe) {
    $('#contextMenu').addClass('hidden');
    let idWarga = row.id_warga || row.id_pengunjung || row.id;

    $('#m_nama_lengkap').text(row.nama_lengkap || '-');
    $('#m_nik').text('NIK: ' + (row.nik || '-'));
    
    let wilayah = [];
    if (row.nomor_rt) wilayah.push('RT ' + row.nomor_rt); if (row.nomor_rw) wilayah.push('RW ' + row.nomor_rw);
    if (row.nama_desa) wilayah.push('Desa ' + row.nama_desa); if (row.nama_kecamatan) wilayah.push('Kec. ' + row.nama_kecamatan);
    if (row.nama_kabupaten) wilayah.push(row.nama_kabupaten); if (row.nama_provinsi) wilayah.push(row.nama_provinsi);
    $('#m_wilayah_lengkap').text(wilayah.join(', '));

    if (tipe === 'warga') {
        $('#m_icon').removeClass('fa-walking').addClass('fa-user-check');
        $('#m_icon_bg').removeClass('bg-sky-600').addClass('bg-slate-900');
        $('#blok_pendatang').addClass('hidden'); $('#blok_warga').removeClass('hidden');
        $('#m_ttl').text((row.tempat_lahir || '-') + ', ' + (row.tanggal_lahir || '-'));
        $('#m_jk').text(row.jenis_kelamin === 'L' ? 'Laki-laki' : (row.jenis_kelamin === 'P' ? 'Perempuan' : '-'));
        $('#m_gol_darah').text(row.golongan_darah || '-'); $('#m_agama').text(row.agama || '-');
        $('#m_status_perkawinan').text(row.status_perkawinan || '-'); $('#m_pekerjaan').text(row.pekerjaan || '-');
        $('#m_alamat').text(row.alamat_lengkap || '-');
        if (canManageData) { $('#m_btn_edit').show().attr('href', "{{ url('/warga') }}/" + idWarga + "/edit"); $('#m_btn_delete').off('click').on('click', function() { closeActionModal(); confirmDeleteWarga("{{ url('/warga') }}/" + idWarga, row.nama_lengkap); }); }
    } else {
        $('#m_icon').removeClass('fa-user-check').addClass('fa-walking');
        $('#m_icon_bg').removeClass('bg-slate-900').addClass('bg-sky-600');
        $('#blok_warga').addClass('hidden'); $('#blok_pendatang').removeClass('hidden');
        $('#m_asal_daerah').text(row.asal_daerah || '-'); $('#m_tujuan').text(row.tujuan_kunjungan || '-');
        $('#m_lama').text((row.lama_kunjungan || '0') + ' Hari'); $('#m_telp').text(row.nomor_telepon || '-');
        $('#m_alamat').text('Tinggal / Menetap sementara di wilayah RT ' + (row.nomor_rt || '-') + ' / RW ' + (row.nomor_rw || '-'));
        if (canManageData) { $('#m_btn_edit').hide(); $('#m_btn_delete').off('click').on('click', function() { closeActionModal(); confirmDeleteWarga("{{ url('/pengunjung') }}/" + idWarga, row.nama_lengkap); }); }
    }

    $('#actionDetailModal').removeClass('hidden');
}

function closeActionModal() { $('#actionDetailModal').addClass('hidden'); }
let targetDeleteUrl = '';
function confirmDeleteWarga(url, nama) { if (!canManageData) return; targetDeleteUrl = url; $('#deleteTargetNama').text(nama); $('#deleteConfirmModal').removeClass('hidden'); }
function closeDeleteModal() { $('#deleteConfirmModal').addClass('hidden'); }
function submitDeleteWarga() { if (targetDeleteUrl && canManageData) { let form = $('#deleteWargaForm'); form.attr('action', targetDeleteUrl); form.submit(); } }
</script>
@endpush