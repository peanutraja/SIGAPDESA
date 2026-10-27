@extends('layouts.admin')

@section('title', 'Peta Sebaran Evakuasi - SIGAPDESA')

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.markercluster/1.5.3/MarkerCluster.css"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.markercluster/1.5.3/MarkerCluster.Default.css"/>
<style>
    #map-container {
        position: relative;
        width: 100%;
        border-radius: 1.25rem;
        overflow: hidden;
        background: #f8fafc;
    }
    #map { 
        width: 100%; 
        height: 420px; 
    }

    /* SEARCH BAR PREMIUM */
    #search-bar {
        display: flex;
        align-items: center;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(8px);
        border: 1px solid #e2e8f0;
        border-radius: 1rem;
        padding: 0 16px;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
    }
    #search-input {
        flex: 1;
        border: none;
        outline: none;
        font-size: 12px;
        color: #111827;
        padding: 12px 0;
        background: transparent;
        font-weight: 600;
    }

    /* DESKTOP FLOATING POSITIONS (MENGHINDARI PENUMPUKAN TULISAN DISABILITAS) */
    @media (min-width: 768px) {
        #map { height: 720px; }
        
        #search-wrap {
            position: absolute;
            top: 20px;
            left: 20px;
            z-index: 1000;
            width: 320px;
            max-width: calc(100% - 40px);
        }
        #filter-panel {
            position: absolute;
            top: 20px;
            right: 20px;
            z-index: 900;
            width: 240px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(8px);
            border: 1px solid #e2e8f0;
            border-radius: 1.25rem;
            padding: 16px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            max-height: calc(100% - 80px);
            overflow-y: auto;
        }

        /* BARIS STATISTIK DI KIRI BAWAH */
        #stats-bar {
            position: absolute;
            bottom: 20px;
            left: 20px;
            z-index: 900;
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            max-width: calc(100% - 280px); /* MEMBERI RUANG UNTUK TOMBOL PILIHAN PETA DI KANAN BAWAH */
        }

        /* TOMBOL PILIHAN PETA DI KANAN BAWAH (SEHINGGA TULISAN DISABILITAS TIDAK TERTUTUP) */
        #bottom-bar {
            position: absolute;
            bottom: 20px;
            right: 20px;
            left: auto;
            transform: none;
            z-index: 950;
            display: flex;
            gap: 4px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(8px);
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            padding: 4px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }
    }

    /* MOBILE STACKED LAYOUT */
    @media (max-width: 767px) {
        #search-wrap {
            position: relative;
            top: 0; left: 0;
            z-index: 1000; width: 100%; padding: 10px;
            background: #ffffff; border-bottom: 1px solid #e2e8f0;
        }
        #filter-panel {
            position: relative;
            top: 0; right: 0;
            z-index: 900; width: 100%;
            background: #ffffff; border-bottom: 1px solid #e2e8f0; padding: 12px;
        }
        #stats-bar {
            position: relative;
            bottom: 0; left: 0; z-index: 900;
            display: flex; gap: 6px; flex-wrap: wrap; padding: 10px;
            background: #f8fafc; border-top: 1px solid #e2e8f0;
        }
        #bottom-bar {
            position: relative;
            bottom: 0; left: 0; transform: none; z-index: 900;
            display: flex; justify-content: center; gap: 4px; padding: 8px;
            background: #ffffff; border-top: 1px solid #e2e8f0;
        }
    }

    /* MARKER KERENTANAN WARNA SPESIFIK */
    .pulse-marker {
        border: 2px solid #fff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
        font-size: 11px;
    }
    .marker-umum { background: #3b82f6 !important; box-shadow: 0 0 10px rgba(59, 130, 246, 0.6); }
    .marker-lansia { background: #eab308 !important; box-shadow: 0 0 10px rgba(234, 179, 8, 0.6); }
    .marker-balita { background: #f43f5e !important; box-shadow: 0 0 10px rgba(244, 63, 94, 0.6); }
    .marker-ibu-hamil { background: #a855f7 !important; box-shadow: 0 0 10px rgba(168, 85, 247, 0.6); }
    .marker-disabilitas { background: #10b981 !important; box-shadow: 0 0 10px rgba(16, 185, 129, 0.6); }

    .evac-marker {
        background: #111827;
        border: 2px solid #fff;
        border-radius: 50%;
        box-shadow: 0 0 12px rgba(17, 24, 39, 0.8);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 12px;
    }
</style>
@endpush

@section('content')
<div class="max-w-7xl mx-auto space-y-4">

    <!-- HEADER SEBARAN -->
    <div class="bg-white rounded-2xl p-4 sm:p-6 border border-gray-200 shadow-sm flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1 flex-wrap">
                <span class="badge bg-brandRed text-black font-bold text-xs uppercase px-3 py-1">Peta Spasial GIS</span>
                <span class="text-xs text-gray-500 font-medium">Sistem Geografis Bencana & Kependudukan</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-brandDark tracking-tight uppercase">Peta Sebaran Evakuasi Wilayah</h1>
            <p class="text-xs text-gray-500">Visualisasi geografis sebaran penduduk, kelompok rentan, zona bencana, dan titik evakuasi.</p>
        </div>
    </div>

    <!-- PETA WRAPPER CONTAINER -->
    <div id="map-container" class="border border-gray-200 shadow-sm">
        
        <!-- SEARCH BAR -->
        <div id="search-wrap">
            <div id="search-bar">
                <i class="fas fa-search text-gray-400 mr-2 text-xs"></i>
                <input type="text" id="search-input" placeholder="Cari nama keluarga, NIK, atau RT/RW..."/>
                <button id="search-clear" onclick="clearSearch()" class="hidden text-gray-400 hover:text-brandRed text-xs p-1"><i class="fas fa-times"></i></button>
            </div>
            <div id="search-results" class="bg-white rounded-2xl border border-gray-200 shadow-xl mt-2 overflow-hidden hidden max-h-60 overflow-y-auto"></div>
        </div>

        <!-- AREA MAP -->
        <div id="map"></div>

        <!-- FILTERS PANEL -->
        <div id="filter-panel" class="space-y-3 text-xs">
            <div class="font-extrabold text-brandRed uppercase tracking-wider border-b pb-2 flex items-center gap-2">
                <i class="fas fa-filter"></i> Saring Data
            </div>

            <!-- FILTER ZONA BENCANA -->
            <div class="space-y-1 bg-slate-50 p-2 rounded-xl border border-slate-100">
                <span class="font-extrabold text-gray-700 uppercase text-[10px] block">Zona Risiko Bencana:</span>
                <div class="grid grid-cols-1 gap-1 font-bold text-gray-700">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" id="chk-zona-merah" checked onchange="applyFilters()" class="checkbox checkbox-xs checkbox-error"/>
                        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-rose-600"></span> Zona Merah (Rawan)</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" id="chk-zona-kuning" checked onchange="applyFilters()" class="checkbox checkbox-xs checkbox-warning"/>
                        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span> Zona Kuning (Waspada)</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" id="chk-zona-hijau" checked onchange="applyFilters()" class="checkbox checkbox-xs checkbox-success"/>
                        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-emerald-600"></span> Zona Hijau (Aman)</span>
                    </label>
                </div>
            </div>
            
            <!-- FILTER KELOMPOK RENTAN -->
            <div class="space-y-1">
                <span class="font-extrabold text-gray-700 uppercase text-[10px] block">Kelompok Rentan:</span>
                <div class="grid grid-cols-1 gap-1.5 font-bold text-gray-700">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" id="chk-umum" checked onchange="applyFilters()" class="checkbox checkbox-xs checkbox-info"/>
                        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span> Warga Umum</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" id="chk-lansia" checked onchange="applyFilters()" class="checkbox checkbox-xs checkbox-warning"/>
                        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-yellow-500"></span> Lansia (&ge;60 Thn)</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" id="chk-balita" checked onchange="applyFilters()" class="checkbox checkbox-xs checkbox-error"/>
                        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span> Balita (&le;5 Thn)</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" id="chk-ibu_hamil" checked onchange="applyFilters()" class="checkbox checkbox-xs"/>
                        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-purple-500"></span> Ibu Hamil</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" id="chk-disabilitas" checked onchange="applyFilters()" class="checkbox checkbox-xs checkbox-success"/>
                        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> Disabilitas</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" id="chk-evakuasi" checked onchange="applyFilters()" class="checkbox checkbox-xs"/>
                        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-gray-900"></span> Titik Evakuasi</span>
                    </label>
                </div>
            </div>

            <div class="border-t pt-2">
                <span class="font-extrabold text-gray-500 uppercase text-[10px]">Filter Berdasarkan RW:</span>
                <div class="flex flex-wrap gap-1 mt-1" id="rw-filter-container"></div>
            </div>
        </div>

        <!-- DYNAMIC STATS BAR (BERADA DI KIRI BAWAH) -->
        <div id="stats-bar" class="text-xs">
            <div class="bg-white/95 backdrop-blur border border-gray-200 px-3 py-1.5 rounded-xl font-bold flex items-center gap-1.5 shadow-sm">
                <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span> Total: <span id="stat-total-warga" class="text-brandDark font-black">0</span>
            </div>
            <div class="bg-white/95 backdrop-blur border border-gray-200 px-3 py-1.5 rounded-xl font-bold flex items-center gap-1.5 shadow-sm">
                <span class="w-2.5 h-2.5 rounded-full bg-yellow-500"></span> Lansia: <span id="stat-lansia" class="text-brandDark font-black">0</span>
            </div>
            <div class="bg-white/95 backdrop-blur border border-gray-200 px-3 py-1.5 rounded-xl font-bold flex items-center gap-1.5 shadow-sm">
                <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span> Balita: <span id="stat-balita" class="text-brandDark font-black">0</span>
            </div>
            <div class="bg-white/95 backdrop-blur border border-gray-200 px-3 py-1.5 rounded-xl font-bold flex items-center gap-1.5 shadow-sm">
                <span class="w-2.5 h-2.5 rounded-full bg-purple-500"></span> Ibu Hamil: <span id="stat-ibu_hamil" class="text-purple-600 font-black">0</span>
            </div>
            <div class="bg-white/95 backdrop-blur border border-gray-200 px-3 py-1.5 rounded-xl font-bold flex items-center gap-1.5 shadow-sm">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> Disabilitas: <span id="stat-disabilitas" class="text-emerald-600 font-black">0</span>
            </div>
        </div>

        <!-- TILE MAP SWITCHER (BERADA DI KANAN BAWAH SECARA TERPISAH) -->
        <div id="bottom-bar">
            <button class="btn btn-xs bg-brandRed text-black border-none rounded-lg" id="btn-tile-street" onclick="switchTile('street')">Jalanan</button>
            <button class="btn btn-xs bg-gray-100 text-brandDark border-none rounded-lg" id="btn-tile-dark" onclick="switchTile('dark')">Gelap</button>
            <button class="btn btn-xs bg-gray-100 text-brandDark border-none rounded-lg" id="btn-tile-satellite" onclick="switchTile('satellite')">Satelit</button>
        </div>
    </div>
</div>

<!-- MODAL DETAIL RUMAH & KONTAK DARURAT -->
<dialog id="modal_detail" class="modal">
  <div class="modal-box bg-white rounded-2xl w-11/12 max-w-md p-4 sm:p-6 space-y-4">
    <div class="flex justify-between items-start border-b pb-3">
        <div>
            <h3 id="modal-title" class="font-black text-lg text-brandDark uppercase">Detail Rumah</h3>
            <p id="modal-sub" class="text-xs text-gray-500"></p>
        </div>
        <form method="dialog">
            <button class="btn btn-sm btn-circle btn-ghost">✕</button>
        </form>
    </div>

    <div id="modal-body" class="space-y-2 text-xs"></div>

    <div class="p-3 bg-red-50 border border-red-100 rounded-xl text-xs text-brandRed font-semibold flex items-center gap-2">
        <i class="fas fa-exclamation-triangle"></i>
        <div><b>Kontak Darurat:</b> Call Center 112 | <b>BPBD:</b> 0252-201234</div>
    </div>
  </div>
</dialog>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.markercluster/1.5.3/leaflet.markercluster.js"></script>

<script>
    const rawRumahData = @json($rumahData ?? []);
    
    let map, markerCluster, evacLayerGroup, zonaCircleGroup;
    let activeTile = 'street';
    let markersList = [];
    let selectedRw = 'Semua';

    const tileLayers = {
        street: L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '© OpenStreetMap' }),
        dark: L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Dark_Gray_Base/MapServer/tile/{z}/{y}/{x}', { maxZoom: 16, attribution: 'Esri' }),
        satellite: L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { attribution: 'Esri' })
    };

    $(document).ready(function() {
        initMap();
        initRwFilters();
        renderMapMarkers();
        initSearch();
        loadEvacuationPoints();
    });

    function initMap() {
        let defaultCenter = [-6.967, 106.333];
        if (rawRumahData.length > 0) {
            let totalLat = 0, totalLng = 0;
            rawRumahData.forEach(r => { totalLat += parseFloat(r.lat || r.latitude || 0); totalLng += parseFloat(r.lng || r.longitude || 0); });
            defaultCenter = [totalLat / rawRumahData.length, totalLng / rawRumahData.length];
        }

        map = L.map('map', {
            zoomControl: true,
            layers: [tileLayers.street]
        }).setView(defaultCenter, 14);

        evacLayerGroup = L.layerGroup().addTo(map);
        zonaCircleGroup = L.layerGroup().addTo(map);
    }

    function getDisabilitasCount(r) {
        return parseInt(r.total_disabilitas || r.disabilitas || (r.detail_warga ? r.detail_warga.disabilitas : 0) || 0);
    }

    function getIbuHamilCount(r) {
        return parseInt(r.total_ibu_hamil || r.ibu_hamil || (r.detail_warga ? r.detail_warga.ibu_hamil : 0) || 0);
    }

    function getLansiaCount(r) {
        return parseInt(r.total_lansia || r.lansia || (r.detail_warga ? r.detail_warga.lansia : 0) || 0);
    }

    function getBalitaCount(r) {
        return parseInt(r.total_balita || r.balita || (r.detail_warga ? r.detail_warga.balita : 0) || 0);
    }

    function renderMapMarkers() {
        if (markerCluster) map.removeLayer(markerCluster);
        markerCluster = L.markerClusterGroup();
        zonaCircleGroup.clearLayers();
        markersList = [];

        let statTotalWarga = 0, statLansia = 0, statBalita = 0, statIbuHamil = 0, statDisabilitas = 0;

        const showUmum = $('#chk-umum').is(':checked');
        const showLansia = $('#chk-lansia').is(':checked');
        const showBalita = $('#chk-balita').is(':checked');
        const showIbuHamil = $('#chk-ibu_hamil').is(':checked');
        const showDisabilitas = $('#chk-disabilitas').is(':checked');

        const showZonaMerah = $('#chk-zona-merah').is(':checked');
        const showZonaKuning = $('#chk-zona-kuning').is(':checked');
        const showZonaHijau = $('#chk-zona-hijau').is(':checked');

        rawRumahData.forEach(r => {
            let lat = parseFloat(r.lat || r.latitude);
            let lng = parseFloat(r.lng || r.longitude);
            let rwVal = r.rw || r.nomor_rw;
            let zona = r.zona_bencana || 'Hijau';

            // 1. Filter RW
            if (selectedRw !== 'Semua' && String(rwVal) !== String(selectedRw)) return;

            // 2. Filter Zona
            if (zona === 'Merah' && !showZonaMerah) return;
            if (zona === 'Kuning' && !showZonaKuning) return;
            if (zona === 'Hijau' && !showZonaHijau) return;

            const totalLansia = getLansiaCount(r);
            const totalBalita = getBalitaCount(r);
            const totalIbuHamil = getIbuHamilCount(r);
            const totalDisabilitas = getDisabilitasCount(r);

            const hasLansia = totalLansia > 0;
            const hasBalita = totalBalita > 0;
            const hasIbuHamil = totalIbuHamil > 0;
            const hasDisabilitas = totalDisabilitas > 0;
            const isUmumOnly = !hasLansia && !hasBalita && !hasIbuHamil && !hasDisabilitas;

            // 3. Filter Kelompok Rentan
            let shouldShow = false;
            if (showUmum && isUmumOnly) shouldShow = true;
            if (showLansia && hasLansia) shouldShow = true;
            if (showBalita && hasBalita) shouldShow = true;
            if (showIbuHamil && hasIbuHamil) shouldShow = true;
            if (showDisabilitas && hasDisabilitas) shouldShow = true;

            if (shouldShow && !isNaN(lat) && !isNaN(lng)) {
                statTotalWarga += parseInt(r.total_warga || r.anggota || 1);
                statLansia += totalLansia;
                statBalita += totalBalita;
                statIbuHamil += totalIbuHamil;
                statDisabilitas += totalDisabilitas;

                // Urutan Warna Marker: Disabilitas (Hijau) > Ibu Hamil (Ungu) > Lansia (Kuning) > Balita (Merah/Pink) > Umum (Biru)
                let markerClass = 'pulse-marker ';
                if (showDisabilitas && hasDisabilitas) {
                    markerClass += 'marker-disabilitas';
                } else if (showIbuHamil && hasIbuHamil) {
                    markerClass += 'marker-ibu-hamil';
                } else if (showLansia && hasLansia) {
                    markerClass += 'marker-lansia';
                } else if (showBalita && hasBalita) {
                    markerClass += 'marker-balita';
                } else {
                    markerClass += 'marker-umum';
                }

                // Render Lingkaran Radius Bencana
                if (zona === 'Merah') {
                    L.circle([lat, lng], {
                        radius: 40,
                        color: '#f43f5e',
                        fillColor: '#f43f5e',
                        fillOpacity: 0.25,
                        weight: 1.5
                    }).addTo(zonaCircleGroup);
                } else if (zona === 'Kuning') {
                    L.circle([lat, lng], {
                        radius: 35,
                        color: '#f59e0b',
                        fillColor: '#f59e0b',
                        fillOpacity: 0.2,
                        weight: 1.5
                    }).addTo(zonaCircleGroup);
                }

                let badgeZona = '';
                if (zona === 'Merah') {
                    badgeZona = `<span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-600 text-white">⚠️ ZONA MERAH (RAWAN)</span>`;
                } else if (zona === 'Kuning') {
                    badgeZona = `<span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500 text-white">⚠️ ZONA KUNING (WASPADA)</span>`;
                } else {
                    badgeZona = `<span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-600 text-white">✅ ZONA HIJAU (AMAN)</span>`;
                }

                const customIcon = L.divIcon({
                    className: markerClass,
                    html: `<span>${r.total_warga || r.anggota || 1}</span>`,
                    iconSize: [28, 28]
                });

                const marker = L.marker([lat, lng], { icon: customIcon });

                const popupContent = `
                    <div class="p-2 space-y-1.5 font-sans">
                        <div class="flex items-center justify-between border-b pb-1 gap-2">
                            <h4 class="font-bold text-xs text-brandDark">${r.nama || r.nama_lengkap || r.alamat || 'Rumah Penduduk'}</h4>
                            ${badgeZona}
                        </div>
                        <p class="text-[10px] text-gray-500">Alamat: ${r.alamat || r.alamat_lengkap || '-'}</p>
                        <p class="text-[10px] text-gray-500">RT/RW: ${r.rt || '-'} / ${r.rw || '-'}</p>
                        <p class="text-[10px] font-bold text-brandDark">Total Warga: ${r.total_warga || r.anggota || 1} Jiwa</p>
                        <div class="text-[10px] text-gray-600 bg-slate-50 p-1.5 rounded-lg border">
                            <div>Disabilitas: <b class="text-emerald-600">${totalDisabilitas}</b> | Ibu Hamil: <b class="text-purple-600">${totalIbuHamil}</b></div>
                            <div>Lansia: <b class="text-amber-600">${totalLansia}</b> | Balita: <b class="text-rose-600">${totalBalita}</b></div>
                        </div>
                        <button class="btn btn-xs bg-brandRed text-white border-none w-full font-bold" onclick="showDetailModal('${r.id}')">Detail Rumah</button>
                    </div>
                `;
                marker.bindPopup(popupContent);
                markerCluster.addLayer(marker);

                markersList.push({ data: r, marker: marker });
            }
        });

        map.addLayer(markerCluster);
        $('#stat-total-warga').text(statTotalWarga);
        $('#stat-lansia').text(statLansia);
        $('#stat-balita').text(statBalita);
        $('#stat-ibu_hamil').text(statIbuHamil);
        $('#stat-disabilitas').text(statDisabilitas);
    }

    function applyFilters() {
        renderMapMarkers();
        loadEvacuationPoints();
    }

    function initRwFilters() {
        const uniqueRw = ['Semua'];
        rawRumahData.forEach(r => {
            const rwVal = r.rw || r.nomor_rw;
            if (rwVal && !uniqueRw.includes(rwVal)) uniqueRw.push(rwVal);
        });
        uniqueRw.sort((a, b) => a === 'Semua' ? -1 : b === 'Semua' ? 1 : String(a).localeCompare(String(b), undefined, { numeric: true }));

        let html = '';
        uniqueRw.forEach(rw => {
            const isSel = (rw === selectedRw) ? 'bg-brandRed text-white' : 'bg-gray-100 text-brandDark';
            html += `<span class="badge ${isSel} text-[10px] font-bold cursor-pointer hover:bg-brandRed hover:text-white" onclick="selectRwFilter('${rw}')">${rw}</span>`;
        });
        $('#rw-filter-container').html(html);
    }

    function selectRwFilter(rw) {
        selectedRw = rw;
        initRwFilters();
        applyFilters();
    }

    function switchTile(type) {
        if (!tileLayers[type] || activeTile === type) return;
        map.removeLayer(tileLayers[activeTile]);
        tileLayers[type].addTo(map);
        activeTile = type;

        $('#btn-tile-street, #btn-tile-dark, #btn-tile-satellite').removeClass('bg-brandRed text-white').addClass('bg-gray-100 text-brandDark');
        $('#btn-tile-' + type).removeClass('bg-gray-100 text-brandDark').addClass('bg-brandRed text-white');
    }

    function showDetailModal(id) {
        const r = rawRumahData.find(item => item.id == id);
        if (!r) return;

        let zona = r.zona_bencana || 'Hijau';
        let badgeZona = '';
        if (zona === 'Merah') {
            badgeZona = `<span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-600 text-white">⚠️ ZONA MERAH (RAWAN)</span>`;
        } else if (zona === 'Kuning') {
            badgeZona = `<span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500 text-white">⚠️ ZONA KUNING (WASPADA)</span>`;
        } else {
            badgeZona = `<span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-600 text-white">✅ ZONA HIJAU (AMAN)</span>`;
        }

        $('#modal-title').text(r.nama || r.nama_lengkap || 'Detail Rumah');
        $('#modal-sub').text((r.alamat || r.alamat_lengkap || '') + ' (RT ' + (r.rt || '') + ' / RW ' + (r.rw || '') + ')');

        const totalLansia = getLansiaCount(r);
        const totalBalita = getBalitaCount(r);
        const totalIbuHamil = getIbuHamilCount(r);
        const totalDisabilitas = getDisabilitasCount(r);

        let html = `
            <div class="flex justify-between items-center py-1 border-b"><span>Status Bencana:</span> ${badgeZona}</div>
            <div class="flex justify-between py-1 border-b"><span>Desa:</span> <span class="font-bold">${r.desa || '-'}</span></div>
            <div class="flex justify-between py-1 border-b"><span>Total Anggota Keluarga:</span> <span class="font-bold">${r.total_warga || r.anggota || 1} Orang</span></div>
            <div class="flex justify-between py-1 border-b"><span>Disabilitas:</span> <span class="font-bold text-emerald-600">${totalDisabilitas} Orang</span></div>
            <div class="flex justify-between py-1 border-b"><span>Ibu Hamil:</span> <span class="font-bold text-purple-600">${totalIbuHamil} Orang</span></div>
            <div class="flex justify-between py-1 border-b"><span>Lansia (&ge;60 Thn):</span> <span class="font-bold text-amber-600">${totalLansia} Orang</span></div>
            <div class="flex justify-between py-1 border-b"><span>Balita (&le;5 Thn):</span> <span class="font-bold text-rose-600">${totalBalita} Orang</span></div>
        `;

        $('#modal-body').html(html);
        document.getElementById('modal_detail').showModal();
    }

    function initSearch() {
        $('#search-input').on('keyup input', function() {
            const query = $(this).val().toLowerCase().trim();
            if (query.length > 0) {
                $('#search-clear').removeClass('hidden');
                let results = rawRumahData.filter(r => 
                    (r.nama && r.nama.toLowerCase().includes(query)) ||
                    (r.nama_lengkap && r.nama_lengkap.toLowerCase().includes(query)) ||
                    (r.alamat && r.alamat.toLowerCase().includes(query)) ||
                    (r.rt && r.rt.toLowerCase().includes(query)) ||
                    (r.rw && r.rw.toLowerCase().includes(query))
                );

                let html = '';
                if (results.length > 0) {
                    results.slice(0, 10).forEach(r => {
                        html += `
                            <div class="p-2 border-b hover:bg-gray-50 cursor-pointer flex items-center gap-2" onclick="flyToRumah('${r.id}')">
                                <i class="fas fa-home text-brandRed text-xs"></i>
                                <div>
                                    <div class="font-bold text-xs text-brandDark">${r.nama || r.nama_lengkap || 'Rumah ' + r.id}</div>
                                    <div class="text-[10px] text-gray-500">${r.alamat || r.alamat_lengkap || ''} (${r.rt || ''} ${r.rw || ''})</div>
                                </div>
                            </div>
                        `;
                    });
                } else {
                    html = '<div class="p-3 text-xs text-gray-500 text-center">Tidak ada lokasi ditemukan.</div>';
                }
                $('#search-results').html(html).removeClass('hidden');
            } else {
                clearSearch();
            }
        });
    }

    function clearSearch() {
        $('#search-input').val('');
        $('#search-clear').addClass('hidden');
        $('#search-results').addClass('hidden');
    }

    function flyToRumah(id) {
        const item = markersList.find(m => m.data.id == id);
        if (item) {
            let lat = parseFloat(item.data.lat || item.data.latitude);
            let lng = parseFloat(item.data.lng || item.data.longitude);
            map.setView([lat, lng], 18);
            item.marker.openPopup();
            $('#search-results').addClass('hidden');
        }
    }

    function loadEvacuationPoints() {
        evacLayerGroup.clearLayers();
        if (!$('#chk-evakuasi').is(':checked')) return;
        
        $.get("{{ route('evakuasi.get') }}", function(res) {
            if (res.success && res.data) {
                res.data.forEach(ev => {
                    const evacIcon = L.divIcon({
                        className: 'evac-marker',
                        html: '<i class="fas fa-shield-alt text-white"></i>',
                        iconSize: [30, 30]
                    });
                    L.marker([ev.lat, ev.lng], { icon: evacIcon })
                     .bindPopup(`<b>${ev.nama}</b><br>Kapasitas: ${ev.kapasitas} Orang<br>${ev.keterangan || ''}`)
                     .addTo(evacLayerGroup);
                });
            }
        });
    }
</script>
@endpush