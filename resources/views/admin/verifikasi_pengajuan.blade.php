<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Pengajuan Data - SIGAP</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Mozilla+Text:wght@200..700&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="{{ asset('vendor/fontawesome-free/css/all.min.css') }}" rel="stylesheet">
</head>
<body class="bg-gray-100">

<div class="flex flex-col md:flex-row min-h-screen">

    <!-- Sidebar Admin -->
    <aside class="w-full md:w-64 bg-gradient-to-b from-rose-600 to-rose-400 text-white flex flex-col">
        <div class="flex items-center justify-center py-4">
            <img src="{{ asset('img/gmls_logo.png') }}" alt="Logo SIGAP DESA" class="w-40 h-auto">
        </div>

        <nav class="flex-1 px-4 py-6 space-y-2">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2 rounded hover:bg-rose-800 transition">
                <i class="fas fa-home"></i>
                <span>Home</span>
            </a>

            <a href="{{ route('admin.pengajuan.index') }}" class="flex items-center justify-between px-3 py-2 rounded bg-rose-800">
                <div class="flex items-center gap-3">
                    <i class="fas fa-clipboard-check"></i>
                    <span>Verifikasi Data</span>
                </div>
            </a>

            <a href="{{ route('penduduk.create') }}" class="flex items-center gap-3 px-3 py-2 rounded hover:bg-rose-800 transition">
                <i class="fas fa-user-edit"></i>
                <span>Input Data Penduduk</span>
            </a>

            <a href="{{ route('warga.index') }}" class="flex items-center gap-3 px-3 py-2 rounded hover:bg-rose-800 transition">
                <i class="fas fa-list"></i>
                <span>Daftar Warga</span>
            </a>

            <a href="{{ route('peta.sebaran') }}" class="flex items-center gap-3 px-3 py-2 rounded hover:bg-rose-800 transition">
                <i class="fas fa-map-marker-alt"></i>
                <span>Peta Sebaran Evakuasi</span>
            </a>

            <form method="POST" action="{{ route('logout') }}" id="formLogout">
                @csrf
                <button type="button" onclick="if(confirm('Yakin ingin logout?')) document.getElementById('formLogout').submit();"
                    class="w-full flex items-center gap-3 px-3 py-2 rounded hover:bg-rose-800 transition text-left">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </button>
            </form>
        </nav>
    </aside>

    <!-- Content Utama -->
    <main class="flex-1 p-4 md:p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Verifikasi Pengajuan Perubahan Data Warga</h1>
        </div>

        @if(session('success'))
            <div class="bg-emerald-100 border border-emerald-400 text-emerald-700 px-4 py-3 rounded-lg mb-4 flex items-center gap-2">
                <i class="fas fa-check-circle"></i> {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-rose-100 border border-rose-400 text-rose-700 px-4 py-3 rounded-lg mb-4 flex items-center gap-2">
                <i class="fas fa-exclamation-triangle"></i> {{ session('error') }}
            </div>
        @endif

        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-rose-600 text-white">
                        <tr>
                            <th class="px-4 py-3 text-left">Waktu</th>
                            <th class="px-4 py-3 text-left">Warga (NIK)</th>
                            <th class="px-4 py-3 text-left">RT/RW</th>
                            <th class="px-4 py-3 text-left">Komparasi Data (Lama vs Usulan Baru)</th>
                            <th class="px-4 py-3 text-left">Alasan Warga</th>
                            <th class="px-4 py-3 text-center">Status / Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @if(isset($pengajuan) && count($pengajuan) > 0)
                            @foreach($pengajuan as $p)
                                @php
                                    $dLama = json_decode($p->data_lama, true);
                                    $dBaru = json_decode($p->data_baru, true);
                                @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-xs text-gray-500">
                                        {{ \Carbon\Carbon::parse($p->created_at)->format('d/m/Y H:i') }}
                                    </td>
                                    <td class="px-4 py-3 font-medium">
                                        {{ $p->nama_asal }}<br>
                                        <span class="text-xs text-gray-500 font-normal">NIK: {{ $p->nik }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-xs">
                                        RT {{ $p->nomor_rt ?? '-' }} / RW {{ $p->nomor_rw ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-xs">
                                        <div class="space-y-1">
                                            @foreach($dBaru as $key => $valBaru)
                                                @php $valLama = $dLama[$key] ?? '-'; @endphp
                                                @if($valLama != $valBaru)
                                                    <div class="p-1.5 bg-amber-50 border border-amber-200 rounded">
                                                        <b class="capitalize text-gray-700">{{ str_replace('_', ' ', $key) }}:</b><br>
                                                        <span class="line-through text-rose-600 mr-2">{{ $valLama ?: '(Kosong)' }}</span>
                                                        <i class="fas fa-arrow-right text-gray-400 text-[10px]"></i>
                                                        <span class="font-bold text-emerald-600 ml-2">{{ $valBaru ?: '(Kosong)' }}</span>
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-600">
                                        {{ $p->alasan_perubahan }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if($p->status == 'pending')
                                            <div class="flex flex-col gap-2">
                                                <!-- Form Setujui -->
                                                <form action="{{ route('admin.pengajuan.setujui', $p->id_pengajuan) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" onclick="return confirm('Apakah data ini sudah diverifikasi secara faktual?')"
                                                            class="w-full px-3 py-1.5 bg-emerald-600 text-white font-bold rounded hover:bg-emerald-700 text-xs flex items-center justify-center gap-1">
                                                        <i class="fas fa-check"></i> Setujui
                                                    </button>
                                                </form>
                                                <!-- Form Tolak -->
                                                <form action="{{ route('admin.pengajuan.tolak', $p->id_pengajuan) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" onclick="return confirm('Tolak permohonan ini?')"
                                                            class="w-full px-3 py-1.5 bg-rose-600 text-white font-bold rounded hover:bg-rose-700 text-xs flex items-center justify-center gap-1">
                                                        <i class="fas fa-times"></i> Tolak
                                                    </button>
                                                </form>
                                            </div>
                                        @elseif($p->status == 'disetujui')
                                            <span class="px-2 py-1 bg-emerald-100 text-emerald-800 font-bold rounded-full text-xs">Disetujui</span>
                                        @else
                                            <span class="px-2 py-1 bg-rose-100 text-rose-800 font-bold rounded-full text-xs">Ditolak</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="6" class="text-center py-8 text-gray-400">
                                    Belum ada pengajuan perubahan data dari warga.
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

</body>
</html>