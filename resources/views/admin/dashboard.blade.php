@extends('layouts.admin')

@section('title', 'Dashboard Wilayah - SIGAPDESA')

@section('content')
<div class="max-w-7xl mx-auto space-y-5" data-theme="light">

    <!-- TOP BAR HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 sm:p-6 rounded-2xl border border-slate-200 shadow-sm">
        <div>
            <div class="flex items-center gap-2 mb-1 flex-wrap">
                <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Dashboard Wilayah</h1>
                <span class="px-3 py-1 bg-rose-600 rounded-full text-white font-extrabold text-[11px] uppercase tracking-wider flex items-center gap-1.5 shadow-sm">
                    <i class="fas fa-user-shield"></i> {{ str_replace('_', ' ', $role ?? 'gmls') }}
                </span>
            </div>
            <p class="text-xs text-slate-500 font-medium">Sistem Informasi Geografis Penanggulangan Bencana Desa!</p>
        </div>

        <button onclick="document.getElementById('contributors_modal').showModal()" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl font-bold text-xs shadow-md shadow-rose-200 w-full sm:w-auto transition-all cursor-pointer">
            <i class="fas fa-users"></i> Tim Kontributor
        </button>
    </div>

    <!-- QUICK LINK PETA GLOBAL -->
    <a href="{{ route('peta.sebaran') }}" class="flex items-center justify-between bg-slate-900 hover:bg-black text-white p-4 sm:p-5 rounded-2xl shadow-md transition-all group border border-slate-800">
        <div class="flex items-center gap-3.5">
            <div class="w-10 h-10 rounded-xl bg-amber-400/20 text-amber-400 flex items-center justify-center shrink-0">
                <i class="fas fa-map-marked-alt text-lg"></i>
            </div>
            <div>
                <h3 class="font-extrabold text-sm text-white group-hover:text-amber-300 transition-colors">Buka Peta Sebaran Evakuasi Global</h3>
                <p class="text-[11px] text-slate-400 font-medium">Akses penuh pantauan titik koordinat rumah & evakuasi seluruh wilayah.</p>
            </div>
        </div>
        <i class="fas fa-arrow-right text-slate-500 group-hover:text-white group-hover:translate-x-1 transition-all"></i>
    </a>

    <!-- STATS CARDS (CLEAN LIGHT THEME) -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Card 1: Total Warga -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Warga Terdaftar</span>
                <div class="text-3xl font-black text-slate-900">{{ number_format($total_warga ?? 0) }}</div>
                <span class="text-[11px] font-bold text-rose-600 block">Terdata di Wilayah Anda</span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center text-xl shrink-0">
                <i class="fas fa-users"></i>
            </div>
        </div>
        
        <!-- Card 2: Ibu Hamil -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Kelompok Rentan (Ibu Hamil)</span>
                <div class="text-3xl font-black text-slate-900">{{ number_format($jumlah_hamil ?? 0) }}</div>
                <span class="text-[11px] font-semibold text-slate-500 block">Prioritas Evakuasi Darurat</span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-700 flex items-center justify-center text-xl shrink-0">
                <i class="fas fa-female"></i>
            </div>
        </div>
        
        <!-- Card 3: Kartu Keluarga -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Kartu Keluarga (KK)</span>
                <div class="text-3xl font-black text-slate-900">{{ number_format($total_kk ?? 0) }}</div>
                <span class="text-[11px] font-semibold text-slate-500 block">Bangunan Rumah Terhubung</span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-700 flex items-center justify-center text-xl shrink-0">
                <i class="fas fa-home"></i>
            </div>
        </div>
    </div>

    <!-- FILTER WILAYAH CARD -->
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5 space-y-4">
        <div class="font-extrabold text-slate-800 text-sm flex items-center gap-2 border-b border-slate-100 pb-3">
            <i class="fas fa-filter text-rose-600"></i> Filter Spesifik Wilayah
        </div>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Provinsi</label>
                <select id="filterProvinsi" class="w-full bg-white text-slate-900 border border-slate-300 rounded-xl px-3 py-2.5 text-xs font-bold focus:outline-none focus:ring-2 focus:ring-rose-500">
                    <option value="" class="bg-white text-slate-900">Semua Provinsi</option>
                    @foreach($provinsi as $p)
                        <option value="{{ $p->id_provinsi }}" class="bg-white text-slate-900" {{ request('provinsi') == $p->id_provinsi ? 'selected' : '' }}>
                            {{ $p->nama_provinsi }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Kabupaten</label>
                <select id="filterKabupaten" class="w-full bg-white text-slate-900 border border-slate-300 rounded-xl px-3 py-2.5 text-xs font-bold focus:outline-none focus:ring-2 focus:ring-rose-500 disabled:bg-slate-100 disabled:text-slate-400" {{ request('provinsi') ? '' : 'disabled' }}>
                    <option value="" class="bg-white text-slate-900">Semua Kabupaten</option>
                    @foreach($kabupatenList ?? [] as $kb)
                        <option value="{{ $kb->id_kabupaten }}" class="bg-white text-slate-900" {{ request('kabupaten') == $kb->id_kabupaten ? 'selected' : '' }}>{{ $kb->nama_kabupaten }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Kecamatan</label>
                <select id="filterKecamatan" class="w-full bg-white text-slate-900 border border-slate-300 rounded-xl px-3 py-2.5 text-xs font-bold focus:outline-none focus:ring-2 focus:ring-rose-500 disabled:bg-slate-100 disabled:text-slate-400" {{ request('kabupaten') ? '' : 'disabled' }}>
                    <option value="" class="bg-white text-slate-900">Semua Kecamatan</option>
                    @foreach($kecamatanList ?? [] as $kc)
                        <option value="{{ $kc->id_kecamatan }}" class="bg-white text-slate-900" {{ request('kecamatan') == $kc->id_kecamatan ? 'selected' : '' }}>{{ $kc->nama_kecamatan }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="flex flex-wrap justify-end gap-2 pt-2 border-t border-slate-100">
            <button id="btnResetFilter" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition cursor-pointer">Reset</button>
            <button id="btnApplyFilter" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold transition shadow-sm cursor-pointer">Terapkan Filter</button>
        </div>
    </div>

    <!-- VISUALISASI GRAFIS -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm space-y-4">
            <h3 class="font-extrabold text-slate-800 text-sm flex items-center gap-2 border-b border-slate-100 pb-3">
                <i class="fas fa-venus-mars text-rose-600"></i> Komposisi Jenis Kelamin
            </h3>
            <div class="relative h-60 w-full flex items-center justify-center">
                <canvas id="chartGender"></canvas>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm space-y-4">
            <h3 class="font-extrabold text-slate-800 text-sm flex items-center gap-2 border-b border-slate-100 pb-3">
                <i class="fas fa-users-line text-rose-600"></i> Distribusi Kelompok Usia
            </h3>
            <div class="relative h-60 w-full">
                <canvas id="chartUsia"></canvas>
            </div>
        </div>
    </div>

</div>

<!-- MODAL TIM KONTRIBUTOR -->
<dialog id="contributors_modal" class="modal">
    <div class="modal-box bg-white text-slate-800 rounded-2xl w-11/12 max-w-md p-6 border border-slate-200 shadow-2xl">
        <form method="dialog">
            <button class="btn btn-sm btn-circle btn-ghost absolute right-3 top-3 text-slate-500">✕</button>
        </form>
        <h3 class="font-bold text-lg text-rose-600 flex items-center gap-2 mb-4 border-b pb-2">
            <i class="fas fa-users"></i> Tim Kontributor SIGAP DESA
        </h3>
        <ul class="divide-y divide-slate-100 text-xs text-slate-700">
            <li class="py-2.5"><span class="font-bold text-slate-900 block">Anis Faisal Reza</span> Direktur GMLS</li>
            <li class="py-2.5"><span class="font-bold text-slate-900 block">Fernando Agustino Hutahaean</span> Mahasiswa FTI Batch 4</li>
            <li class="py-2.5"><span class="font-bold text-slate-900 block">Alfreando Moza Siagian</span> Mahasiswa FTI Batch 4</li>
            <li class="py-2.5"><span class="font-bold text-slate-900 block">Abigail Tesalonika</span> Mahasiswa FTI Batch 4</li>
            <li class="py-2.5"><span class="font-bold text-slate-900 block">F Adiwidya Wirawan J</span> Mahasiswa FTI Batch 5</li>
            <li class="py-2.5"><span class="font-bold text-slate-900 block">Rocky</span> Mahasiswa FTI Batch 5</li>
            <li class="py-2.5"><span class="font-bold text-slate-900 block">Yoel Beny Christian</span> Mahasiswa FTI Batch 5</li>
        </ul>
    </div>
    <form method="dialog" class="modal-backdrop">
        <button>close</button>
    </form>
</dialog>
@endsection

@push('scripts')
{{-- Data bridge: Blade data ditulis di script type=application/json agar TIDAK di-parse JS language service --}}
<script type="application/json" id="dashboardServerData">
{
    "genderLabels": {!! json_encode($gender_labels ?? ['Laki-Laki', 'Perempuan']) !!},
    "genderData": {!! json_encode($gender_data ?? [0, 0]) !!},
    "usiaAnak": {!! json_encode($usia_data['anak'] ?? 0) !!},
    "usiaDewasa": {!! json_encode($usia_data['dewasa'] ?? 0) !!},
    "usiaLansia": {!! json_encode($usia_data['lansia'] ?? 0) !!},
    "routeGetKabupaten": "{{ route('get.kabupaten') }}",
    "routeGetKecamatan": "{{ route('get.kecamatan') }}",
    "routeDashboard": "{{ route('dashboard') }}"
}
</script>
<script>
$(document).ready(function() {
    // 1. Parse data dari JSON bridge (menghindari Blade directive di dalam script JS)
    var serverData = JSON.parse(document.getElementById('dashboardServerData').textContent);

    var genderLabels = serverData.genderLabels;
    var genderData = serverData.genderData;

    var usiaAnak = Number(serverData.usiaAnak);
    var usiaDewasa = Number(serverData.usiaDewasa);
    var usiaLansia = Number(serverData.usiaLansia);

    // 2. Render Chart Jenis Kelamin
    var ctxGender = document.getElementById('chartGender');
    if (ctxGender) {
        new Chart(ctxGender, {
            type: 'doughnut',
            data: {
                labels: genderLabels,
                datasets: [{
                    data: genderData,
                    backgroundColor: ['#1e293b', '#e11d48'],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { font: { weight: 'bold', size: 11 } } }
                }
            }
        });
    }

    // 3. Render Chart Kelompok Usia (HANYA BILANGAN BULAT PADA SUMBU Y)
    var ctxUsia = document.getElementById('chartUsia');
    if (ctxUsia) {
        new Chart(ctxUsia, {
            type: 'bar',
            data: {
                labels: ['Anak-anak (<18 thn)', 'Dewasa (18-59 thn)', 'Lansia (\u226560 thn)'],
                datasets: [{
                    label: 'Jumlah Warga',
                    data: [usiaAnak, usiaDewasa, usiaLansia],
                    backgroundColor: ['#e11d48', '#1e293b', '#f59e0b'],
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0,
                            stepSize: 1,
                            font: { weight: 'bold', size: 11 }
                        },
                        grid: { color: '#f1f5f9' }
                    },
                    x: {
                        ticks: { font: { weight: 'bold', size: 11 } },
                        grid: { display: false }
                    }
                }
            }
        });
    }

    // 4. Logika Cascading Filter Wilayah (AJAX)
    $('#filterProvinsi').on('change', function() {
        var id = $(this).val();
        $('#filterKabupaten').html('<option value="" class="bg-white text-slate-900">Semua Kabupaten</option>').prop('disabled', true);
        $('#filterKecamatan').html('<option value="" class="bg-white text-slate-900">Semua Kecamatan</option>').prop('disabled', true);

        if (id) {
            $.get(serverData.routeGetKabupaten, { id_provinsi: id, provinsi_id: id }, function(data) {
                $('#filterKabupaten').prop('disabled', false);
                data.forEach(function(item) {
                    $('#filterKabupaten').append('<option value="' + item.id_kabupaten + '" class="bg-white text-slate-900">' + item.nama_kabupaten + '</option>');
                });
            });
        }
    });

    $('#filterKabupaten').on('change', function() {
        var id = $(this).val();
        $('#filterKecamatan').html('<option value="" class="bg-white text-slate-900">Semua Kecamatan</option>').prop('disabled', true);

        if (id) {
            $.get(serverData.routeGetKecamatan, { id_kabupaten: id, kabupaten_id: id }, function(data) {
                $('#filterKecamatan').prop('disabled', false);
                data.forEach(function(item) {
                    $('#filterKecamatan').append('<option value="' + item.id_kecamatan + '" class="bg-white text-slate-900">' + item.nama_kecamatan + '</option>');
                });
            });
        }
    });

    $('#btnApplyFilter').on('click', function() {
        var prov = $('#filterProvinsi').val();
        var kab = $('#filterKabupaten').val();
        var kec = $('#filterKecamatan').val();

        var url = new URL(window.location.href);
        if (prov) { url.searchParams.set('provinsi', prov); } else { url.searchParams.delete('provinsi'); }
        if (kab) { url.searchParams.set('kabupaten', kab); } else { url.searchParams.delete('kabupaten'); }
        if (kec) { url.searchParams.set('kecamatan', kec); } else { url.searchParams.delete('kecamatan'); }

        window.location.href = url.toString();
    });

    $('#btnResetFilter').on('click', function() {
        window.location.href = serverData.routeDashboard;
    });
});
</script>
@endpush