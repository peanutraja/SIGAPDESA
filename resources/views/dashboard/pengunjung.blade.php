@extends('layouts.warga')

@section('title', 'Dashboard Pendatang - SIGAPDESA')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    
    <!-- HEADER WELCOME -->
    <div class="bg-gradient-to-r from-sky-900 via-sky-800 to-sky-900 rounded-3xl p-6 sm:p-8 text-white shadow-xl relative overflow-hidden">
        <div class="relative z-10 space-y-3">
            <div class="flex items-center gap-2 flex-wrap">
                <span class="px-3.5 py-1 rounded-full bg-sky-500 text-white text-[11px] font-extrabold uppercase tracking-wider shadow-md">Portal Tamu / Pendatang</span>
                @if($pengunjung->status_laporan === 'Selesai')
                    <span class="px-3.5 py-1 rounded-full bg-emerald-500 text-white text-[11px] font-bold shadow-md">Kunjungan Selesai</span>
                @else
                    <span class="px-3.5 py-1 rounded-full bg-white/20 text-white text-[11px] font-bold backdrop-blur-md">Kunjungan Aktif</span>
                @endif
            </div>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-white uppercase">Selamat Datang, {{ explode(' ', $pengunjung->nama_lengkap ?? 'Tamu')[0] }}!</h1>
            <p class="text-xs sm:text-sm text-sky-100 max-w-xl font-medium leading-relaxed">Ini adalah halaman pemantauan status kunjungan Anda di Desa {{ $pengunjung->nama_desa ?? '-' }}. Pastikan untuk melapor jika Anda sudah selesai berkunjung.</p>
        </div>
        <i class="fas fa-walking text-white/5 text-8xl sm:text-9xl absolute -right-4 -bottom-6 pointer-events-none"></i>
    </div>

    <!-- NOTIFIKASI -->
    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-2xl text-xs font-bold flex items-center gap-3">
            <i class="fas fa-check-circle text-lg"></i> {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 rounded-2xl text-xs font-bold flex items-center gap-3">
            <i class="fas fa-exclamation-triangle text-lg"></i> {{ session('error') }}
        </div>
    @endif

    <!-- KARTU INFORMASI & AKSI -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        <!-- DETAIL DATA DIRI -->
        <div class="md:col-span-2 bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-4">
            <h3 class="text-sm font-black text-slate-800 uppercase border-b border-slate-100 pb-3 flex items-center gap-2">
                <i class="fas fa-id-card text-sky-600"></i> Detail Kunjungan Anda
            </h3>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                <div class="bg-slate-50 p-3 rounded-xl border border-slate-100">
                    <span class="block font-bold text-slate-400 uppercase text-[10px] mb-1">Nama Lengkap</span>
                    <span class="font-bold text-slate-800">{{ $pengunjung->nama_lengkap }}</span>
                </div>
                <div class="bg-slate-50 p-3 rounded-xl border border-slate-100">
                    <span class="block font-bold text-slate-400 uppercase text-[10px] mb-1">NIK</span>
                    <span class="font-mono font-bold text-slate-800">{{ $pengunjung->nik }}</span>
                </div>
                <div class="bg-slate-50 p-3 rounded-xl border border-slate-100">
                    <span class="block font-bold text-slate-400 uppercase text-[10px] mb-1">Asal Daerah</span>
                    <span class="font-semibold text-slate-800">{{ $pengunjung->asal_daerah }}</span>
                </div>
                <div class="bg-slate-50 p-3 rounded-xl border border-slate-100">
                    <span class="block font-bold text-slate-400 uppercase text-[10px] mb-1">Tujuan & Lama Menetap</span>
                    <span class="font-semibold text-slate-800">{{ $pengunjung->tujuan_kunjungan }} ({{ $pengunjung->lama_kunjungan }} Hari)</span>
                </div>
                <div class="sm:col-span-2 bg-sky-50 p-3 rounded-xl border border-sky-100">
                    <span class="block font-bold text-sky-600 uppercase text-[10px] mb-1">Lokasi Singgah Saat Ini</span>
                    <span class="font-bold text-slate-800">RT {{ $pengunjung->nomor_rt ?? '-' }} / RW {{ $pengunjung->nomor_rw ?? '-' }}, Desa {{ $pengunjung->nama_desa ?? '-' }}</span>
                </div>
            </div>
        </div>

        <!-- PANEL AKSI LAPOR PULANG -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm flex flex-col justify-center text-center space-y-4">
            @if($pengunjung->status_laporan === 'Selesai')
                <div class="w-16 h-16 bg-emerald-100 text-emerald-500 rounded-full flex items-center justify-center text-3xl mx-auto">
                    <i class="fas fa-check"></i>
                </div>
                <div>
                    <h4 class="font-black text-slate-800 mb-1">Kunjungan Selesai</h4>
                    <p class="text-[11px] text-slate-500 font-medium">Anda telah melaporkan kepulangan kunjungan ini.</p>
                </div>
            @else
                <div class="w-16 h-16 bg-sky-100 text-sky-600 rounded-full flex items-center justify-center text-3xl mx-auto">
                    <i class="fas fa-suitcase-rolling"></i>
                </div>
                <div>
                    <h4 class="font-black text-slate-800 mb-1">Akan Pulang?</h4>
                    <p class="text-[11px] text-slate-500 font-medium mb-4">Jika Anda akan meninggalkan desa, harap lapor dengan menekan tombol di bawah.</p>
                    
                    <!-- TOMBOL YANG SUDAH DIPERBAIKI WARNANYA -->
                    <button onclick="document.getElementById('modal_pulang').showModal()" class="w-full bg-rose-600 hover:bg-rose-700 text-white font-bold py-2.5 rounded-xl text-xs shadow-md transition-all">
                        <i class="fas fa-sign-out-alt mr-1"></i> Lapor Kepulangan
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- MODAL KONFIRMASI KEPULANGAN -->
<dialog id="modal_pulang" class="modal">
    <div class="modal-box bg-white rounded-2xl max-w-sm p-6 text-center">
        <div class="w-14 h-14 bg-rose-100 text-rose-600 rounded-full flex items-center justify-center text-2xl mx-auto mb-4">
            <i class="fas fa-question"></i>
        </div>
        <h3 class="font-black text-slate-900 text-lg">Konfirmasi Kepulangan</h3>
        <p class="py-3 text-xs text-slate-500 font-medium">
            Apakah Anda yakin ingin melaporkan bahwa kunjungan Anda telah selesai dan akan meninggalkan desa?
        </p>
        <div class="modal-action flex justify-center gap-2 mt-4">
            <form method="dialog" class="w-1/2">
                <button class="w-full bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold py-2.5 rounded-xl text-xs transition-all">Batal</button>
            </form>
            <form action="{{ route('pengunjung.pulang') }}" method="POST" class="w-1/2">
                @csrf
                <button type="submit" class="w-full bg-rose-600 hover:bg-rose-700 text-white font-bold py-2.5 rounded-xl text-xs shadow-md transition-all">Ya, Saya Pulang</button>
            </form>
        </div>
    </div>
</dialog>
@endsection