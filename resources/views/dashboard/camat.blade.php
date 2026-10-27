@extends('layouts.admin')

@section('title', 'Dashboard Camat - SIGAPDESA')

@push('styles')
<style>
    /* FIX POPUP MAP AGAR TEKS TERLIHAT JELAS */
    .leaflet-popup-content-wrapper { background-color: #ffffff !important; border-radius: 0.75rem !important; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1) !important; }
    .leaflet-popup-tip { background-color: #ffffff !important; }
    .leaflet-popup-content { margin: 10px !important; }
    .leaflet-popup-content * { color: #0f172a !important; }

    #map-container { position: relative; width: 100%; border-radius: 1rem; overflow: hidden; background: #f8fafc; }
    #mapCamat { width: 100%; height: 500px; }
    #search-bar { display: flex; align-items: center; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(8px); border: 1px solid #e2e8f0; border-radius: 1rem; padding: 0 16px; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1); }
    #search-input { flex: 1; border: none; outline: none; font-size: 12px; color: #111827 !important; padding: 12px 0; background: transparent; font-weight: 600; }
    @media (min-width: 768px) {
        #mapCamat { height: 600px; }
        #search-wrap { position: absolute; top: 20px; left: 20px; z-index: 1000; width: 320px; max-width: calc(100% - 40px); }
        #filter-panel { position: absolute; top: 20px; right: 20px; z-index: 900; width: 240px; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(8px); border: 1px solid #e2e8f0; border-radius: 1.25rem; padding: 16px; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1); max-height: calc(100% - 80px); overflow-y: auto; color: #0f172a; }
        #stats-bar { position: absolute; bottom: 20px; left: 20px; z-index: 900; display: flex; gap: 6px; flex-wrap: wrap; max-width: calc(100% - 280px); }
        #bottom-bar { position: absolute; bottom: 20px; right: 20px; left: auto; transform: none; z-index: 950; display: flex; gap: 4px; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(8px); border: 1px solid #e2e8f0; border-radius: 1rem; padding: 4px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
    }
    @media (max-width: 767px) {
        #search-wrap { position: relative; width: 100%; padding: 10px; background: #fff; border-bottom: 1px solid #e2e8f0; }
        #filter-panel { position: relative; width: 100%; background: #fff; border-bottom: 1px solid #e2e8f0; padding: 12px; color: #0f172a; }
        #stats-bar { position: relative; display: flex; gap: 6px; flex-wrap: wrap; padding: 10px; background: #f8fafc; border-top: 1px solid #e2e8f0; }
        #bottom-bar { position: relative; display: flex; justify-content: center; gap: 4px; padding: 8px; background: #fff; border-top: 1px solid #e2e8f0; }    
    }
    .pulse-marker { border: 2px solid #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white !important; font-weight: bold; font-size: 11px; }
    .marker-umum { background: #3b82f6 !important; box-shadow: 0 0 10px rgba(59, 130, 246, 0.6); }
    .marker-lansia { background: #eab308 !important; box-shadow: 0 0 10px rgba(234, 179, 8, 0.6); }
    .marker-balita { background: #f43f5e !important; box-shadow: 0 0 10px rgba(244, 63, 94, 0.6); }
    .marker-ibu-hamil { background: #a855f7 !important; box-shadow: 0 0 10px rgba(168, 85, 247, 0.6); }
    .marker-disabilitas { background: #10b981 !important; box-shadow: 0 0 10px rgba(16, 185, 129, 0.6); }
    .evac-marker { background: #111827; border: 2px solid #fff; border-radius: 50%; box-shadow: 0 0 12px rgba(17, 24, 39, 0.8); display: flex; align-items: center; justify-content: center; color: white !important; font-size: 12px; }
</style>
@endpush

@section('content')
<div x-data="{ activeTab: 'dashboard' }" class="space-y-6">
    <div id="camatChartData" data-rekap='@json($rekapWilayah ?? [])' data-rumah='@json($rumahData ?? [])' data-hamil="{{ $total_hamil ?? 0 }}" data-disabilitas="{{ $total_disabilitas ?? 0 }}" data-warga="{{ $total_warga ?? 0 }}" class="hidden"></div>
    <div class="bg-gradient-to-r from-slate-900 via-brandDarkRed to-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-xl relative overflow-hidden border border-slate-800">
        <div class="relative z-10 space-y-3">
            <div class="flex items-center gap-2 flex-wrap">
                <span class="px-3.5 py-1 rounded-full bg-brandRed text-white text-[11px] font-extrabold uppercase tracking-wider shadow-md">Dashboard Camat</span>
                <span class="px-3.5 py-1 rounded-full bg-white/10 text-white text-[11px] font-bold backdrop-blur-md border border-white/10">KECAMATAN {{ strtoupper($infoWilayah->nama_kecamatan ?? '-') }}</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-white uppercase">MONITORING DEMOGRAFI & PETA KECAMATAN</h1>
            <p class="text-xs sm:text-sm text-rose-100 max-w-2xl font-medium leading-relaxed">Pengawasan terintegrasi populasi warga dan lokasi titik rumah di wilayah Kecamatan {{ $infoWilayah->nama_kecamatan ?? '-' }}.</p>
        </div>
        <i class="fas fa-building text-white/5 text-8xl sm:text-9xl absolute -right-4 -bottom-6 pointer-events-none"></i>
    </div>

    <!-- NAVIGASI -->
    <div class="flex items-center gap-2 border-b border-slate-200 pb-3 overflow-x-auto">
        <button type="button" @click="activeTab = 'dashboard'; $nextTick(() => { if(mapCamat) mapCamat.invalidateSize(); });" :class="activeTab === 'dashboard' ? 'bg-brandRed text-white shadow-md font-extrabold' : 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-200 font-bold'" class="px-4 py-2.5 rounded-2xl text-xs transition-all flex items-center gap-2 shrink-0"><i class="fas fa-chart-line"></i> Dashboard Utama & Peta</button>
        <button type="button" @click="activeTab = 'rekap'" :class="activeTab === 'rekap' ? 'bg-brandRed text-white shadow-md font-extrabold' : 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-200 font-bold'" class="px-4 py-2.5 rounded-2xl text-xs transition-all flex items-center gap-2 shrink-0"><i class="fas fa-table"></i> Tabel Agregat Desa</button>
        <a href="{{ route('warga.index') }}" class="px-4 py-2.5 rounded-2xl text-xs font-bold bg-slate-900 hover:bg-black text-white transition-all flex items-center gap-2 shrink-0 ml-auto"><i class="fas fa-address-book text-rose-400"></i> Lihat Daftar Warga Kecamatan</a>
    </div>

    <!-- PANEL NOTIFIKASI PENDATANG -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-6">
        <h3 class="text-lg font-bold text-slate-800 mb-4 border-b pb-2">Notifikasi Pendatang Baru</h3>
        @if(session('success')) <div class="mb-4 bg-emerald-50 text-emerald-600 px-4 py-3 rounded-lg text-sm">{{ session('success') }}</div> @endif
        @if(isset($laporan_pendatang) && count($laporan_pendatang) > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-slate-600">
                    <thead class="text-xs text-slate-500 uppercase bg-slate-50">
                        <tr><th class="px-4 py-3">Nama Lengkap</th><th class="px-4 py-3">Asal Daerah</th><th class="px-4 py-3">Tujuan</th><th class="px-4 py-3">Lama (Hari)</th><th class="px-4 py-3">Aksi</th></tr>
                    </thead>
                    <tbody>
                        @foreach($laporan_pendatang as $p)
                        <tr class="border-b">
                            <td class="px-4 py-3 font-semibold text-slate-800">{{ $p->nama_lengkap }} <br><span class="text-xs font-normal text-slate-500">{{ $p->nik }}</span></td>
                            <td class="px-4 py-3">{{ $p->asal_daerah }}</td><td class="px-4 py-3">{{ $p->tujuan_kunjungan }}</td><td class="px-4 py-3">{{ $p->lama_kunjungan }} Hari</td>
                            <td class="px-4 py-3 flex gap-2">
                                <button type="button" onclick="document.getElementById('modal_pendatang_{{ $p->id_pengunjung ?? $p->id }}').showModal()" class="bg-sky-500 hover:bg-sky-600 text-white px-3 py-1.5 rounded-lg text-xs font-bold">Detail</button>
                                <form action="{{ route('pengunjung.updateStatus', $p->id_pengunjung ?? $p->id) }}" method="POST">@csrf <input type="hidden" name="status" value="Diketahui"><button type="submit" class="bg-emerald-500 hover:bg-emerald-600 text-white px-3 py-1.5 rounded-lg text-xs font-bold" onclick="return confirm('Konfirmasi laporan ini?')">Terima</button></form>
                                <form action="{{ route('pengunjung.updateStatus', $p->id_pengunjung ?? $p->id) }}" method="POST">@csrf <input type="hidden" name="status" value="Ditolak"><button type="submit" class="bg-rose-500 hover:bg-rose-600 text-white px-3 py-1.5 rounded-lg text-xs font-bold" onclick="return confirm('Tolak laporan ini?')">Tolak</button></form>
                            </td>
                        </tr>
                        <!-- Modal Tampilan DaisyUI / Tailwind untuk Detail Pendatang -->
                        <dialog id="modal_pendatang_{{ $p->id_pengunjung ?? $p->id }}" class="modal">
                            <div class="modal-box bg-white text-slate-800 rounded-2xl w-11/12 max-w-lg p-6">
                                <h3 class="font-black text-lg text-slate-900 border-b pb-3 mb-4 uppercase">Informasi Lengkap Pendatang</h3>
                                <div class="space-y-3 text-sm">
                                    <div class="grid grid-cols-3 gap-2 border-b border-slate-100 pb-2"><span class="text-slate-500 font-semibold">Nama Lengkap</span><span class="col-span-2 font-bold text-slate-900">: {{ $p->nama_lengkap }}</span></div>
                                    <div class="grid grid-cols-3 gap-2 border-b border-slate-100 pb-2"><span class="text-slate-500 font-semibold">NIK</span><span class="col-span-2 font-bold text-slate-900">: {{ $p->nik }}</span></div>
                                    <div class="grid grid-cols-3 gap-2 border-b border-slate-100 pb-2"><span class="text-slate-500 font-semibold">No. Telp / WA</span><span class="col-span-2 font-bold text-slate-900">: {{ $p->nomor_telepon }}</span></div>
                                    <div class="grid grid-cols-3 gap-2 border-b border-slate-100 pb-2"><span class="text-slate-500 font-semibold">Alamat Asal</span><span class="col-span-2 font-bold text-slate-900">: {{ $p->asal_daerah }}</span></div>
                                    <div class="grid grid-cols-3 gap-2 border-b border-slate-100 pb-2"><span class="text-slate-500 font-semibold">Tujuan Kunjungan</span><span class="col-span-2 font-bold text-slate-900">: {{ $p->tujuan_kunjungan }}</span></div>
                                    <div class="grid grid-cols-3 gap-2 border-b border-slate-100 pb-2"><span class="text-slate-500 font-semibold">Lama Kunjungan</span><span class="col-span-2 font-bold text-slate-900">: {{ $p->lama_kunjungan }} Hari</span></div>
                                </div>
                                <div class="modal-action mt-6"><form method="dialog"><button class="btn btn-sm bg-slate-200 hover:bg-slate-300 text-slate-700 border-none rounded-xl px-6">Tutup</button></form></div>
                            </div>
                        </dialog>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-slate-500 text-sm italic">Belum ada laporan pendatang baru saat ini.</p>
        @endif
    </div>

    <!-- TAB 1: PETA & CHART -->
    <div x-show="activeTab === 'dashboard'" x-transition class="space-y-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-2"><span class="text-[11px] font-extrabold uppercase text-slate-400 tracking-wider">Total Desa</span><h3 class="text-3xl font-black text-slate-900">{{ number_format($total_desa ?? 0) }}</h3><p class="text-xs text-slate-500 font-semibold">Kelurahan / Desa</p></div>
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-2"><span class="text-[11px] font-extrabold uppercase text-slate-400 tracking-wider">Total Jiwa</span><h3 class="text-3xl font-black text-brandRed">{{ number_format($total_warga ?? 0) }}</h3><p class="text-xs text-slate-500 font-semibold">Penduduk Terdaftar</p></div>
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-2"><span class="text-[11px] font-extrabold uppercase text-slate-400 tracking-wider">Ibu Hamil</span><h3 class="text-3xl font-black text-purple-600">{{ number_format($total_hamil ?? 0) }}</h3><p class="text-xs text-slate-500 font-semibold">Ibu Hamil Terdata</p></div>
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-2"><span class="text-[11px] font-extrabold uppercase text-slate-400 tracking-wider">Disabilitas</span><h3 class="text-3xl font-black text-emerald-600">{{ number_format($total_disabilitas ?? 0) }}</h3><p class="text-xs text-slate-500 font-semibold">Prioritas Evakuasi</p></div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-4"><div class="flex items-center justify-between border-b border-slate-100 pb-3"><h3 class="text-sm font-black text-slate-900 uppercase tracking-wide">Perbandingan Populasi Tiap Desa</h3></div><div class="relative h-64 w-full"><canvas id="chartDesaCamat"></canvas></div></div>

        <!-- PETA SPASIAL -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm space-y-4 mt-6">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3"><h3 class="text-sm font-black text-slate-900 uppercase tracking-wide"><i class="fas fa-map-marked-alt text-brandRed mr-2"></i> Peta Spasial & Titik Rawan</h3></div>
            <div id="map-container" class="border border-gray-200 shadow-sm relative">
                <div id="search-wrap"><div id="search-bar"><i class="fas fa-search text-gray-400 mr-2 text-xs"></i><input type="text" id="search-input" placeholder="Cari nama keluarga, NIK, atau RT/RW..."/><button id="search-clear" onclick="clearSearch()" class="hidden text-gray-400 hover:text-brandRed text-xs p-1"><i class="fas fa-times"></i></button></div><div id="search-results" class="bg-white rounded-2xl border border-gray-200 shadow-xl mt-2 overflow-hidden hidden max-h-60 overflow-y-auto"></div></div>
                <div id="mapCamat"></div>
                <div id="filter-panel" class="space-y-3 text-xs">
                    <div class="font-extrabold text-brandRed uppercase tracking-wider border-b pb-2 flex items-center gap-2"><i class="fas fa-filter"></i> Saring Data</div>
                    <div class="space-y-1 bg-slate-50 p-2 rounded-xl border border-slate-100"><span class="font-extrabold text-gray-700 uppercase text-[10px] block">Zona Risiko Bencana:</span><div class="grid grid-cols-1 gap-1 font-bold text-gray-700"><label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" id="chk-zona-merah" checked onchange="applyFilters()" class="checkbox checkbox-xs checkbox-error"/><span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-rose-600"></span> Zona Merah</span></label><label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" id="chk-zona-kuning" checked onchange="applyFilters()" class="checkbox checkbox-xs checkbox-warning"/><span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span> Zona Kuning</span></label><label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" id="chk-zona-hijau" checked onchange="applyFilters()" class="checkbox checkbox-xs checkbox-success"/><span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-emerald-600"></span> Zona Hijau</span></label></div></div>
                    <div class="space-y-1"><span class="font-extrabold text-gray-700 uppercase text-[10px] block">Kelompok Rentan:</span><div class="grid grid-cols-1 gap-1.5 font-bold text-gray-700"><label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" id="chk-umum" checked onchange="applyFilters()" class="checkbox checkbox-xs checkbox-info"/><span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span> Warga Umum</span></label><label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" id="chk-lansia" checked onchange="applyFilters()" class="checkbox checkbox-xs checkbox-warning"/><span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-yellow-500"></span> Lansia (&ge;60 Thn)</span></label><label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" id="chk-balita" checked onchange="applyFilters()" class="checkbox checkbox-xs checkbox-error"/><span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span> Balita (&le;5 Thn)</span></label><label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" id="chk-ibu_hamil" checked onchange="applyFilters()" class="checkbox checkbox-xs"/><span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-purple-500"></span> Ibu Hamil</span></label><label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" id="chk-disabilitas" checked onchange="applyFilters()" class="checkbox checkbox-xs checkbox-success"/><span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> Disabilitas</span></label><label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" id="chk-evakuasi" checked onchange="applyFilters()" class="checkbox checkbox-xs"/><span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-gray-900"></span> Titik Evakuasi</span></label></div></div>
                    <div class="border-t pt-2"><span class="font-extrabold text-gray-500 uppercase text-[10px]">Filter Berdasarkan RW:</span><div class="flex flex-wrap gap-1 mt-1" id="rw-filter-container"></div></div>
                </div>
                <div id="stats-bar" class="text-xs">
                    <div class="bg-white/95 backdrop-blur border border-gray-200 px-3 py-1.5 rounded-xl font-bold flex items-center gap-1.5 shadow-sm"><span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span> Total: <span id="stat-total-warga" class="text-brandDark font-black">0</span></div><div class="bg-white/95 backdrop-blur border border-gray-200 px-3 py-1.5 rounded-xl font-bold flex items-center gap-1.5 shadow-sm"><span class="w-2.5 h-2.5 rounded-full bg-yellow-500"></span> Lansia: <span id="stat-lansia" class="text-brandDark font-black">0</span></div><div class="bg-white/95 backdrop-blur border border-gray-200 px-3 py-1.5 rounded-xl font-bold flex items-center gap-1.5 shadow-sm"><span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span> Balita: <span id="stat-balita" class="text-brandDark font-black">0</span></div><div class="bg-white/95 backdrop-blur border border-gray-200 px-3 py-1.5 rounded-xl font-bold flex items-center gap-1.5 shadow-sm"><span class="w-2.5 h-2.5 rounded-full bg-purple-500"></span> Ibu Hamil: <span id="stat-ibu_hamil" class="text-purple-600 font-black">0</span></div><div class="bg-white/95 backdrop-blur border border-gray-200 px-3 py-1.5 rounded-xl font-bold flex items-center gap-1.5 shadow-sm"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> Disabilitas: <span id="stat-disabilitas" class="text-emerald-600 font-black">0</span></div>
                </div>
                <div id="bottom-bar"><button class="btn btn-xs bg-brandRed text-white border-none rounded-lg" id="btn-tile-street" onclick="switchTile('street')">Jalanan</button><button class="btn btn-xs bg-gray-100 text-brandDark border-none rounded-lg" id="btn-tile-dark" onclick="switchTile('dark')">Gelap</button><button class="btn btn-xs bg-gray-100 text-brandDark border-none rounded-lg" id="btn-tile-satellite" onclick="switchTile('satellite')">Satelit</button></div>
            </div>
        </div>
    </div>

    <!-- TAB 2: TABEL REKAP DESA -->
    <div x-show="activeTab === 'rekap'" x-transition class="space-y-6">
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 sm:p-6 space-y-4">
            <div class="border-b border-slate-100 pb-3"><h3 class="text-base font-black text-slate-900 uppercase tracking-wide">Rekapitulasi Desa Per Wilayah</h3></div>
            <div class="overflow-x-auto w-full">
                <table class="w-full text-left text-xs divide-y divide-slate-100 min-w-[650px]">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 font-extrabold uppercase text-[10px] tracking-wider"><th class="py-3.5 px-4">#</th><th class="py-3.5 px-4">Nama Desa</th><th class="py-3.5 px-4 text-center">Jumlah Warga</th><th class="py-3.5 px-4 text-center">Jumlah KK</th><th class="py-3.5 px-4 text-center">Ibu Hamil</th><th class="py-3.5 px-4 text-center">Disabilitas</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                        @forelse($rekapWilayah ?? [] as $index => $row)
                            <tr class="hover:bg-slate-50 transition"><td class="py-3.5 px-4 font-bold text-slate-400">{{ $index + 1 }}</td><td class="py-3.5 px-4 font-bold text-slate-900">Desa {{ $row->nama_desa ?? '-' }}</td><td class="py-3.5 px-4 text-center font-black text-slate-900">{{ number_format($row->total_warga ?? 0) }} Jiwa</td><td class="py-3.5 px-4 text-center font-semibold text-slate-600">{{ number_format($row->total_kk ?? 0) }} KK</td><td class="py-3.5 px-4 text-center font-bold text-purple-600">{{ number_format($row->total_hamil ?? 0) }}</td><td class="py-3.5 px-4 text-center font-bold text-emerald-600">{{ number_format($row->total_disabilitas ?? 0) }}</td></tr>
                        @empty
                            <tr><td colspan="6" class="text-center py-6 text-slate-400 italic">Belum ada data desa terdaftar.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<dialog id="modal_detail" class="modal">
  <div class="modal-box bg-white rounded-2xl w-11/12 max-w-md p-4 sm:p-6 space-y-4">
    <div class="flex justify-between items-start border-b pb-3"><div><h3 id="modal-title" class="font-black text-lg text-brandDark uppercase">Detail Rumah</h3><p id="modal-sub" class="text-xs text-gray-500"></p></div><form method="dialog"><button class="btn btn-sm btn-circle btn-ghost">✕</button></form></div>
    <div id="modal-body" class="space-y-2 text-xs"></div>
    <div class="p-3 bg-red-50 border border-red-100 rounded-xl text-xs text-brandRed font-semibold flex items-center gap-2"><i class="fas fa-exclamation-triangle"></i><div><b>Kontak Darurat:</b> Call Center 112 | <b>BPBD:</b> 0252-201234</div></div>
  </div>
</dialog>
@endsection

@push('scripts')
<script>
    let rawRumahData = []; let mapCamat, markerCluster, evacLayerGroup, zonaCircleGroup; let activeTile = 'street'; let markersList = []; let selectedRw = 'Semua';
    const tileLayers = {
        street: L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '© OpenStreetMap' }),
        dark: L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Dark_Gray_Base/MapServer/tile/{z}/{y}/{x}', { maxZoom: 16, attribution: 'Esri' }),
        satellite: L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { attribution: 'Esri' })
    };
    document.addEventListener("DOMContentLoaded", function() {
        const holder = document.getElementById('camatChartData'); if (holder) { try { rawRumahData = JSON.parse(holder.getAttribute('data-rumah') || '[]'); } catch(e) { rawRumahData = []; } }
        initMap(); initRwFilters(); renderMapMarkers(); initSearch(); loadEvacuationPoints(); initChartJS();
    });
    function initMap() {
        let defaultCenter = [-6.5886, 106.2415]; if (rawRumahData.length > 0 && rawRumahData[0].lat && rawRumahData[0].lng) { defaultCenter = [rawRumahData[0].lat, rawRumahData[0].lng]; }
        mapCamat = L.map('mapCamat', { zoomControl: true, layers: [tileLayers.street] }).setView(defaultCenter, 13);
        evacLayerGroup = L.layerGroup().addTo(mapCamat); zonaCircleGroup = L.layerGroup().addTo(mapCamat); SigapInaRisk.attach(mapCamat, { panel: '#filter-panel', rumah: rawRumahData, onZonaUpdated: () => renderMapMarkers() }); setTimeout(() => { mapCamat.invalidateSize(); }, 500);
    }
    function getDisabilitasCount(r) { return parseInt(r.total_disabilitas || (r.detail_warga ? r.detail_warga.disabilitas : 0) || 0); } function getIbuHamilCount(r) { return parseInt(r.total_ibu_hamil || (r.detail_warga ? r.detail_warga.ibu_hamil : 0) || 0); } function getLansiaCount(r) { return parseInt(r.total_lansia || (r.detail_warga ? r.detail_warga.lansia : 0) || 0); } function getBalitaCount(r) { return parseInt(r.total_balita || (r.detail_warga ? r.detail_warga.balita : 0) || 0); }
    function renderMapMarkers() {
        if (markerCluster) mapCamat.removeLayer(markerCluster); markerCluster = L.markerClusterGroup(); zonaCircleGroup.clearLayers(); markersList = [];
        let statTotalWarga = 0, statLansia = 0, statBalita = 0, statIbuHamil = 0, statDisabilitas = 0;
        const showUmum = $('#chk-umum').is(':checked'), showLansia = $('#chk-lansia').is(':checked'), showBalita = $('#chk-balita').is(':checked'), showIbuHamil = $('#chk-ibu_hamil').is(':checked'), showDisabilitas = $('#chk-disabilitas').is(':checked');
        const showZonaMerah = $('#chk-zona-merah').is(':checked'), showZonaKuning = $('#chk-zona-kuning').is(':checked'), showZonaHijau = $('#chk-zona-hijau').is(':checked');
        rawRumahData.forEach(r => {
            let lat = parseFloat(r.lat || r.latitude), lng = parseFloat(r.lng || r.longitude); let rwVal = r.rw || r.nomor_rw, zona = r.zona_bencana || r.zona || 'Hijau';
            if (selectedRw !== 'Semua' && String(rwVal) !== String(selectedRw)) return;
            if (zona === 'Merah' && !showZonaMerah) return; if (zona === 'Kuning' && !showZonaKuning) return; if (zona === 'Hijau' && !showZonaHijau) return;
            const tLan = getLansiaCount(r), tBal = getBalitaCount(r), tHam = getIbuHamilCount(r), tDis = getDisabilitasCount(r); const isUmum = !tLan && !tBal && !tHam && !tDis;
            let shouldShow = false; if (showUmum && isUmum) shouldShow = true; if (showLansia && tLan > 0) shouldShow = true; if (showBalita && tBal > 0) shouldShow = true; if (showIbuHamil && tHam > 0) shouldShow = true; if (showDisabilitas && tDis > 0) shouldShow = true;
            if (shouldShow && !isNaN(lat) && !isNaN(lng)) {
                statTotalWarga += parseInt(r.total_warga || r.anggota || 1); statLansia += tLan; statBalita += tBal; statIbuHamil += tHam; statDisabilitas += tDis;
                let mClass = 'pulse-marker '; if (showDisabilitas && tDis > 0) mClass += 'marker-disabilitas'; else if (showIbuHamil && tHam > 0) mClass += 'marker-ibu-hamil'; else if (showLansia && tLan > 0) mClass += 'marker-lansia'; else if (showBalita && tBal > 0) mClass += 'marker-balita'; else mClass += 'marker-umum';
                if (zona === 'Merah') L.circle([lat, lng], { radius: 40, color: '#f43f5e', fillColor: '#f43f5e', fillOpacity: 0.25, weight: 1.5 }).addTo(zonaCircleGroup); else if (zona === 'Kuning') L.circle([lat, lng], { radius: 35, color: '#f59e0b', fillColor: '#f59e0b', fillOpacity: 0.2, weight: 1.5 }).addTo(zonaCircleGroup);
                let bZona = (zona === 'Merah') ? `<span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-600 text-white">⚠️ ZONA MERAH</span>` : (zona === 'Kuning') ? `<span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500 text-white">⚠️ ZONA KUNING</span>` : (zona === 'Belum' ? `<span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-400 text-white">⏳ BELUM DIANALISIS</span>` : `<span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-600 text-white">✅ ZONA HIJAU</span>`);
                const marker = L.marker([lat, lng], { icon: L.divIcon({ className: mClass, html: `<span>${r.total_warga || 1}</span>`, iconSize: [28, 28] }) });
                const popupContent = `<div class="p-2 space-y-1.5 font-sans bg-white text-slate-800 rounded"><div class="flex items-center justify-between border-b pb-1 gap-2"><h4 class="font-bold text-xs text-slate-900">${r.nama || 'Rumah Penduduk'}</h4> ${bZona}</div><p class="text-[10px] text-slate-600">Alamat: ${r.alamat || '-'}</p><p class="text-[10px] text-slate-600">RT/RW: ${r.rt || '-'} / ${r.rw || '-'}</p><p class="text-[10px] font-bold text-slate-900">Total Warga: ${r.total_warga || 1} Jiwa</p><div class="text-[10px] text-slate-800 bg-slate-100 p-1.5 rounded-lg border border-slate-200"><div>Disabilitas: <b class="text-emerald-600">${tDis}</b> | Ibu Hamil: <b class="text-purple-600">${tHam}</b></div><div>Lansia: <b class="text-amber-600">${tLan}</b> | Balita: <b class="text-rose-600">${tBal}</b></div></div><button class="btn btn-xs bg-brandRed text-white border-none w-full font-bold mt-1" onclick="showDetailModal('${r.id}')">Detail Rumah</button></div>`;
                marker.bindPopup(popupContent); markerCluster.addLayer(marker); markersList.push({ data: r, marker: marker });
            }
        });
        mapCamat.addLayer(markerCluster); $('#stat-total-warga').text(statTotalWarga); $('#stat-lansia').text(statLansia); $('#stat-balita').text(statBalita); $('#stat-ibu_hamil').text(statIbuHamil); $('#stat-disabilitas').text(statDisabilitas);
    }
    function applyFilters() { renderMapMarkers(); loadEvacuationPoints(); }
    function initRwFilters() {
        const uniqueRw = ['Semua']; rawRumahData.forEach(r => { const rw = r.rw || r.nomor_rw; if (rw && !uniqueRw.includes(rw)) uniqueRw.push(rw); }); uniqueRw.sort((a, b) => a === 'Semua' ? -1 : b === 'Semua' ? 1 : String(a).localeCompare(String(b), undefined, { numeric: true }));
        let html = ''; uniqueRw.forEach(rw => { const isSel = (rw === selectedRw) ? 'bg-brandRed text-white' : 'bg-gray-100 text-brandDark'; html += `<span class="badge ${isSel} text-[10px] font-bold cursor-pointer hover:bg-brandRed hover:text-white" onclick="selectRwFilter('${rw}')">${rw}</span>`; });
        $('#rw-filter-container').html(html);
    }
    function selectRwFilter(rw) { selectedRw = rw; initRwFilters(); applyFilters(); }
    function switchTile(type) {
        if (!tileLayers[type] || activeTile === type) return; mapCamat.removeLayer(tileLayers[activeTile]); tileLayers[type].addTo(mapCamat); activeTile = type;
        $('#btn-tile-street, #btn-tile-dark, #btn-tile-satellite').removeClass('bg-brandRed text-white').addClass('bg-gray-100 text-brandDark'); $('#btn-tile-' + type).removeClass('bg-gray-100 text-brandDark').addClass('bg-brandRed text-white');
    }
    function showDetailModal(id) {
        const r = rawRumahData.find(item => item.id == id); if (!r) return;
        let zona = r.zona_bencana || r.zona || 'Hijau', badgeZona = (zona === 'Merah') ? `<span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-600 text-white">⚠️ ZONA MERAH</span>` : (zona === 'Kuning') ? `<span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500 text-white">⚠️ ZONA KUNING</span>` : (zona === 'Belum' ? `<span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-400 text-white">⏳ BELUM DIANALISIS</span>` : `<span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-600 text-white">✅ ZONA HIJAU</span>`);
        $('#modal-title').text(r.nama || 'Detail Rumah'); $('#modal-sub').text((r.alamat || '') + ' (' + (r.rt || '') + ' / ' + (r.rw || '') + ')');
        $('#modal-body').html(`<div class="flex justify-between items-center py-1 border-b"><span>Status Bencana:</span> ${badgeZona}</div><div class="flex justify-between py-1 border-b"><span>Desa:</span> <span class="font-bold">${r.desa || '-'}</span></div><div class="flex justify-between py-1 border-b"><span>Total Anggota Keluarga:</span> <span class="font-bold">${r.total_warga || 1} Orang</span></div><div class="flex justify-between py-1 border-b"><span>Disabilitas:</span> <span class="font-bold text-emerald-600">${getDisabilitasCount(r)} Orang</span></div><div class="flex justify-between py-1 border-b"><span>Ibu Hamil:</span> <span class="font-bold text-purple-600">${getIbuHamilCount(r)} Orang</span></div><div class="flex justify-between py-1 border-b"><span>Lansia (&ge;60 Thn):</span> <span class="font-bold text-amber-600">${getLansiaCount(r)} Orang</span></div><div class="flex justify-between py-1 border-b"><span>Balita (&le;5 Thn):</span> <span class="font-bold text-rose-600">${getBalitaCount(r)} Orang</span></div>`);
        document.getElementById('modal_detail').showModal();
    }
    function initSearch() {
        $('#search-input').on('keyup input', function() {
            const query = $(this).val().toLowerCase().trim();
            if (query.length > 0) {
                $('#search-clear').removeClass('hidden');
                let res = rawRumahData.filter(r => (r.nama && r.nama.toLowerCase().includes(query)) || (r.alamat && r.alamat.toLowerCase().includes(query)) || (r.rt && r.rt.toLowerCase().includes(query)) || (r.rw && r.rw.toLowerCase().includes(query)));
                let html = '';
                if (res.length > 0) { res.slice(0, 10).forEach(r => { html += `<div class="p-2 border-b hover:bg-slate-100 cursor-pointer flex items-center gap-2 bg-white text-slate-800" onclick="flyToRumah('${r.id}')"><i class="fas fa-home text-brandRed text-xs"></i><div><div class="font-bold text-xs text-slate-900">${r.nama || 'Rumah ' + r.id}</div><div class="text-[10px] text-slate-600">${r.alamat || ''} (${r.rt || ''} ${r.rw || ''})</div></div></div>`; }); } else { html = '<div class="p-3 text-xs text-slate-500 text-center bg-white">Tidak ada lokasi ditemukan.</div>'; }
                $('#search-results').html(html).removeClass('hidden');
            } else { clearSearch(); }
        });
    }
    function clearSearch() { $('#search-input').val(''); $('#search-clear').addClass('hidden'); $('#search-results').addClass('hidden'); }
    function flyToRumah(id) { const item = markersList.find(m => m.data.id == id); if (item) { let lat = parseFloat(item.data.lat || item.data.latitude), lng = parseFloat(item.data.lng || item.data.longitude); mapCamat.setView([lat, lng], 18); item.marker.openPopup(); $('#search-results').addClass('hidden'); } }
    function loadEvacuationPoints() {
        evacLayerGroup.clearLayers(); if (!$('#chk-evakuasi').is(':checked')) return;
        $.get("{{ route('evakuasi.get') }}", function(res) { if (res.success && res.data) { res.data.forEach(ev => { L.marker([ev.lat, ev.lng], { icon: L.divIcon({ className: 'evac-marker', html: '<i class="fas fa-shield-alt text-white"></i>', iconSize: [30, 30] }) }).bindPopup(`<div class="text-slate-900"><b>${ev.nama}</b><br>Kapasitas: ${ev.kapasitas} Orang<br>${ev.keterangan || ''}</div>`).addTo(evacLayerGroup); }); } });
    }
    function initChartJS() {
        const holder = document.getElementById('camatChartData'); if (!holder) return; let rekapData = []; try { rekapData = JSON.parse(holder.getAttribute('data-rekap') || '[]'); } catch(e) {}
        const ctxDesa = document.getElementById('chartDesaCamat');
        if (ctxDesa) { new Chart(ctxDesa, { type: 'bar', data: { labels: rekapData.map(i => 'Desa ' + (i.nama_desa || '-')), datasets: [{ label: 'Jumlah Penduduk', data: rekapData.map(i => i.total_warga || 0), backgroundColor: '#be123c', borderRadius: 8 }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { precision: 0, stepSize: 1 } }, x: { grid: { display: false } } } } }); }
    }
</script>
@endpush