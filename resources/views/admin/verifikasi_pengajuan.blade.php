@extends('layouts.admin')

@section('title', 'Verifikasi Pengajuan Data - SIGAPDESA')

@section('content')
@php
    $listPengajuan = $pengajuan ?? collect();
    $jmlPending   = $listPengajuan->where('status', 'pending')->count();
    $jmlDisetujui = $listPengajuan->where('status', 'disetujui')->count();
    $jmlDitolak   = $listPengajuan->where('status', 'ditolak')->count();
@endphp

<div x-data="{ filter: 'pending' }" class="max-w-7xl mx-auto space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-sm flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="space-y-2">
            <span class="inline-flex items-center gap-1.5 px-3.5 py-1 rounded-full bg-rose-50 text-rose-700 border border-rose-200 font-extrabold text-[11px] uppercase tracking-wider"><i class="fas fa-clipboard-check"></i> Verifikasi Data</span>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight uppercase">Pengajuan Perubahan Data Warga</h1>
            <p class="text-xs text-slate-500 font-medium">Periksa usulan perubahan data yang dikirim warga, lalu setujui atau tolak.</p>
        </div>
        <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 px-4 py-2.5 text-xs font-bold transition"><i class="fas fa-arrow-left text-xs"></i> Kembali ke Dashboard</a>
    </div>

    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-2xl text-sm font-semibold flex items-center gap-2"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 rounded-2xl text-sm font-semibold flex items-center gap-2"><i class="fas fa-exclamation-triangle"></i> {{ session('error') }}</div>
    @endif

    <!-- Ringkasan -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-2"><span class="text-[11px] font-extrabold uppercase text-slate-400 tracking-wider">Menunggu Verifikasi</span><h3 class="text-3xl font-black text-amber-500">{{ $jmlPending }}</h3></div>
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-2"><span class="text-[11px] font-extrabold uppercase text-slate-400 tracking-wider">Disetujui</span><h3 class="text-3xl font-black text-emerald-600">{{ $jmlDisetujui }}</h3></div>
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-2"><span class="text-[11px] font-extrabold uppercase text-slate-400 tracking-wider">Ditolak</span><h3 class="text-3xl font-black text-rose-600">{{ $jmlDitolak }}</h3></div>
    </div>

    <!-- Tab Filter Status -->
    <div class="flex items-center gap-2 border-b border-slate-200 pb-3 overflow-x-auto">
        @foreach(['pending' => ['Menunggu', 'fa-hourglass-half', $jmlPending], 'disetujui' => ['Disetujui', 'fa-check', $jmlDisetujui], 'ditolak' => ['Ditolak', 'fa-times', $jmlDitolak], 'semua' => ['Semua', 'fa-list', $listPengajuan->count()]] as $key => [$label, $icon, $jml])
            <button type="button" @click="filter = '{{ $key }}'" :class="filter === '{{ $key }}' ? 'bg-rose-600 text-white shadow-md font-extrabold' : 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-200 font-bold'" class="px-4 py-2.5 rounded-2xl text-xs transition-all flex items-center gap-2 shrink-0"><i class="fas {{ $icon }}"></i> {{ $label }} ({{ $jml }})</button>
        @endforeach
    </div>

    <!-- Daftar Pengajuan -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-slate-600">
                <thead class="text-xs text-slate-500 uppercase bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3">Waktu</th>
                        <th class="px-4 py-3">Warga (NIK)</th>
                        <th class="px-4 py-3">RT/RW</th>
                        <th class="px-4 py-3">Perubahan (Lama → Baru)</th>
                        <th class="px-4 py-3">Alasan Warga</th>
                        <th class="px-4 py-3 text-center">Status / Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($listPengajuan as $p)
                        @php
                            $dLama = json_decode($p->data_lama, true) ?? [];
                            $dBaru = json_decode($p->data_baru, true) ?? [];
                        @endphp
                        <tr x-show="filter === 'semua' || filter === '{{ $p->status }}'" class="hover:bg-slate-50 align-top">
                            <td class="px-4 py-3 text-xs text-slate-500 whitespace-nowrap">{{ \Carbon\Carbon::parse($p->created_at)->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3 font-semibold text-slate-800">{{ $p->nama_asal }}<br><span class="text-xs font-normal text-slate-500">{{ $p->nik }}</span></td>
                            <td class="px-4 py-3 text-xs whitespace-nowrap">RT {{ $p->nomor_rt ?? '-' }} / RW {{ $p->nomor_rw ?? '-' }}</td>
                            <td class="px-4 py-3 text-xs min-w-[240px]">
                                <div class="space-y-1.5">
                                    @foreach($dBaru as $key => $valBaru)
                                        @php $valLama = $dLama[$key] ?? '-'; @endphp
                                        @if($valLama != $valBaru)
                                            <div class="p-2 bg-amber-50 border border-amber-200 rounded-xl">
                                                <b class="capitalize text-slate-700">{{ str_replace('_', ' ', $key) }}</b><br>
                                                <span class="line-through text-rose-600">{{ $valLama ?: '(Kosong)' }}</span>
                                                <i class="fas fa-arrow-right text-slate-400 text-[10px] mx-1"></i>
                                                <span class="font-bold text-emerald-600">{{ $valBaru ?: '(Kosong)' }}</span>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-600 min-w-[160px]">{{ $p->alasan_perubahan }}</td>
                            <td class="px-4 py-3 text-center">
                                @if($p->status == 'pending')
                                    <div class="flex flex-col gap-2 min-w-[110px]">
                                        <form action="{{ route('admin.pengajuan.setujui', $p->id_pengajuan) }}" method="POST">
                                            @csrf
                                            <button type="submit" onclick="return confirm('Apakah data ini sudah diverifikasi secara faktual?')" class="w-full bg-emerald-500 hover:bg-emerald-600 text-white px-3 py-1.5 rounded-lg text-xs font-bold flex items-center justify-center gap-1"><i class="fas fa-check"></i> Setujui</button>
                                        </form>
                                        <form action="{{ route('admin.pengajuan.tolak', $p->id_pengajuan) }}" method="POST">
                                            @csrf
                                            <button type="submit" onclick="return confirm('Tolak permohonan ini?')" class="w-full bg-rose-500 hover:bg-rose-600 text-white px-3 py-1.5 rounded-lg text-xs font-bold flex items-center justify-center gap-1"><i class="fas fa-times"></i> Tolak</button>
                                        </form>
                                    </div>
                                @elseif($p->status == 'disetujui')
                                    <span class="px-3 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 font-bold rounded-full text-xs">Disetujui</span>
                                @else
                                    <span class="px-3 py-1 bg-rose-50 text-rose-700 border border-rose-200 font-bold rounded-full text-xs">Ditolak</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center py-10 text-slate-400 italic">Belum ada pengajuan perubahan data dari warga.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
