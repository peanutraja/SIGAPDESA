<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'SIGAPDESA - Sistem Informasi Geografis')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- DaisyUI v4 (kompatibel dengan Tailwind v3 dari Play CDN) -->
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.10/dist/full.min.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="{{ asset('vendor/fontawesome-free/css/all.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />

    @stack('styles')
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; color: #0f172a; }</style>
</head>
<body class="bg-slate-100 antialiased font-sans" data-theme="light">

@php
    $adminSession = session('admin');
    $userRole = strtolower($adminSession['role'] ?? 'gmls');
    $userName = $adminSession['nama_pengguna'] ?? $adminSession['nama_lengkap'] ?? $adminSession['username'] ?? 'Pengguna';
    $canCreateAkun = (int)($adminSession['can_create_akun'] ?? 0);

    $roleLabels = [
        'gmls' => 'Super Admin (GMLS)', 'bupati' => 'Bupati', 'camat' => 'Camat',
        'pemerintah_desa' => 'Pemerintah Desa', 'kepala_desa' => 'Kepala Desa', 'kades' => 'Kepala Desa', 
        'sekdes' => 'Sekretaris Desa', 'ketua_rw' => 'Ketua RW', 'rw' => 'Ketua RW', 'ketua_rt' => 'Ketua RT', 'rt' => 'Ketua RT',
    ];
    $displayRole = $roleLabels[$userRole] ?? strtoupper(str_replace('_', ' ', $userRole));
    $isExecutive = in_array($userRole, ['bupati', 'camat', 'pemerintah_desa', 'kades', 'kepala_desa', 'sekdes', 'rw', 'ketua_rw']);
    $canInputData = in_array($userRole, ['ketua_rt', 'rt', 'gmls', 'admin']);
    
    // Akses Kelola Akun: Hanya untuk GMLS/Admin OR Pejabat/Pemerintah Desa yang punya izin can_create_akun == 1
    $canManageUsers = in_array($userRole, ['gmls', 'admin']) || ($canCreateAkun === 1);

    // Jumlah pengajuan perubahan data warga yang menunggu verifikasi (badge notifikasi sidebar)
    $canVerifyPengajuan = in_array($userRole, ['ketua_rt', 'rt', 'gmls', 'admin']);
    $pengajuanPendingCount = 0;
    if ($canVerifyPengajuan) {
        $idRtSes   = $adminSession['id_rt'] ?? $adminSession['rt'] ?? null;
        $idRwSes   = $adminSession['id_rw'] ?? $adminSession['rw'] ?? null;
        $idDesaSes = $adminSession['id_desa'] ?? null;
        $isRtRole  = in_array($userRole, ['ketua_rt', 'rt']);
        $pengajuanPendingCount = \Illuminate\Support\Facades\DB::table('pengajuan_update as p')
            ->join('warga as w', 'p.id_warga', '=', 'w.id_warga')
            ->leftJoin('rumah as r', 'w.id_rumah', '=', 'r.id_rumah')
            ->where('p.status', 'pending')
            ->when($isRtRole && !empty($idRtSes), fn($q) => $q->where('r.id_rt', $idRtSes))
            ->when($isRtRole && !empty($idRwSes), fn($q) => $q->where('r.id_rw', $idRwSes))
            ->when($isRtRole && !empty($idDesaSes), fn($q) => $q->where('w.id_desa', $idDesaSes))
            ->count();
    }
@endphp

@if($isExecutive)
    <!-- TAMPILAN EKSEKUTIF PEJABAT / PEMERINTAH DESA (TOPBAR) -->
    <div class="min-h-screen flex flex-col">
        <header class="bg-white text-slate-800 shadow-sm sticky top-0 z-50 border-b border-slate-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3 flex items-center justify-between gap-3">
                <div class="flex items-center gap-3.5">
                    <img src="{{ asset('img/sigapdesa_logo.png') }}" alt="Logo SIGAPDESA" class="h-11 sm:h-20 w-auto object-contain">
                    <span class="hidden sm:inline-block h-6 w-px bg-slate-200"></span>
                    <span class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-rose-50 text-rose-700 border border-rose-200 text-xs font-black uppercase tracking-wider">
                        <i class="fas fa-user-shield text-rose-600"></i> Portal {{ $displayRole }}
                    </span>
                </div>

                <div class="flex items-center gap-2.5">
                    <!-- TOMBOL KELOLA AKUN: HANYA TAMPIL JIKA CAN_CREATE_AKUN == 1 ATAU GMLS -->
                    @if($canManageUsers)
                        <a href="{{ route('admin.user.index') }}" 
                           class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold transition shadow-sm border border-indigo-500">
                            <i class="fas fa-users-cog"></i>
                            <span class="hidden md:inline">Kelola Akun {{ $userRole === 'pemerintah_desa' ? 'Desa' : 'Pejabat' }}</span>
                        </a>
                    @endif

                    <div class="flex items-center gap-2.5 bg-slate-50 px-3.5 py-1.5 rounded-xl border border-slate-200 shadow-sm">
                        <div class="w-8 h-8 rounded-xl bg-rose-600 text-white flex items-center justify-center text-xs font-black uppercase shrink-0">
                            {{ substr($userRole, 0, 2) }}
                        </div>
                        <div class="text-left hidden sm:block">
                            <p class="text-xs font-extrabold text-slate-900 leading-tight truncate max-w-[120px]">{{ $userName }}</p>
                            <p class="text-[10px] font-bold text-rose-600 uppercase tracking-wide leading-tight">{{ $displayRole }}</p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" onclick="return confirm('Keluar dari portal?')"
                            class="p-2.5 rounded-xl bg-rose-50 hover:bg-rose-600 text-rose-600 hover:text-white transition text-xs font-bold border border-rose-200 shadow-sm" title="Keluar">
                            <i class="fas fa-power-off"></i>
                        </button>
                    </form>
                </div>
            </div>
        </header>
        <main class="flex-1 py-6 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto w-full">
            @yield('content')
        </main>
    </div>

@else
    <!-- TAMPILAN OPERASIONAL GMLS/RT (SIDEBAR) -->
    <div class="min-h-screen flex flex-col md:flex-row">
        <aside class="w-full md:w-64 bg-slate-900 text-white flex flex-col shrink-0 border-r border-slate-800 shadow-xl">
            <div class="py-6 px-4 border-b border-slate-800 bg-slate-950/90 flex justify-center">
                <img src="{{ asset('img/sigapdesa_logo.png') }}" alt="SIGAPDESA" class="w-40 sm:w-44 h-auto mx-auto drop-shadow-lg">
            </div>
            <div class="px-5 py-3.5 border-b border-slate-800/80 bg-slate-900/90 flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-rose-600 text-white flex items-center justify-center font-black text-xs uppercase shrink-0 shadow-sm">
                    {{ substr($userRole, 0, 2) }}
                </div>
                <div class="overflow-hidden">
                    <p class="text-xs font-bold text-white truncate">{{ $userName }}</p>
                    <p class="text-[10px] text-rose-400 font-extrabold uppercase tracking-wider">{{ $displayRole }}</p>
                </div>
            </div>

            <nav class="flex-1 px-3 py-4 space-y-1.5 text-xs font-bold overflow-y-auto">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all {{ request()->routeIs('dashboard') ? 'bg-rose-600 text-white shadow-md' : 'text-slate-300 hover:bg-slate-800' }}">
                    <i class="fas fa-home w-4 text-center"></i> <span>Home / Dashboard</span>
                </a>
                @if($canInputData)
                <a href="{{ route('penduduk.create') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all {{ request()->routeIs('penduduk.create') ? 'bg-rose-600 text-white shadow-md' : 'text-slate-300 hover:bg-slate-800' }}">
                    <i class="fas fa-user-plus w-4 text-center"></i> <span>Input Data Penduduk</span>
                </a>
                @endif
                <a href="{{ route('warga.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all {{ request()->routeIs('warga.*') ? 'bg-rose-600 text-white shadow-md' : 'text-slate-300 hover:bg-slate-800' }}">
                    <i class="fas fa-address-book w-4 text-center"></i> <span>Daftar Warga</span>
                </a>
                @if($canVerifyPengajuan)
                <a href="{{ route('admin.pengajuan.index') }}" class="flex items-center justify-between gap-3 px-3.5 py-2.5 rounded-xl transition-all {{ request()->routeIs('admin.pengajuan.*') ? 'bg-rose-600 text-white shadow-md' : 'text-slate-300 hover:bg-slate-800' }}">
                    <span class="flex items-center gap-3"><i class="fas fa-clipboard-check w-4 text-center text-emerald-400"></i> <span>Verifikasi Data</span></span>
                    @if($pengajuanPendingCount > 0)
                        <span class="min-w-[20px] h-5 px-1.5 rounded-full bg-amber-400 text-slate-900 text-[10px] font-black flex items-center justify-center">{{ $pengajuanPendingCount }}</span>
                    @endif
                </a>
                @endif
                <a href="{{ route('peta.sebaran') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all {{ request()->routeIs('peta.sebaran') ? 'bg-rose-600 text-white shadow-md' : 'text-slate-300 hover:bg-slate-800' }}">
                    <i class="fas fa-map-marked-alt w-4 text-center text-amber-400"></i> <span>Peta Evakuasi</span>
                </a>

                @if($canManageUsers)
                <div class="pt-4 pb-1 px-3"><span class="text-[10px] uppercase font-extrabold text-slate-500">Administrator</span></div>
                <a href="{{ route('admin.user.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all {{ request()->routeIs('admin.user.*') ? 'bg-rose-600 text-white shadow-md' : 'text-slate-300 hover:bg-slate-800' }}">
                    <i class="fas fa-users-cog w-4 text-center text-cyan-400"></i> <span>Kelola Akun Pejabat</span>
                </a>
                @endif
            </nav>

            <div class="p-3 border-t border-slate-800 bg-slate-950/60">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" onclick="return confirm('Keluar dari sistem?')" class="w-full flex justify-between px-3.5 py-2.5 rounded-xl bg-slate-800 hover:bg-rose-900/80 text-slate-200 text-xs font-bold transition">
                        <span><i class="fas fa-sign-out-alt text-rose-400 mr-2"></i> Keluar</span>
                    </button>
                </form>
            </div>
        </aside>

        <main class="flex-1 flex flex-col min-w-0 overflow-hidden">
            <header class="bg-white border-b border-slate-200 px-6 py-3.5 flex items-center justify-between sticky top-0 z-30 shadow-xs">
                <div class="flex items-center gap-3">
                    <span class="text-xs font-black text-slate-800 uppercase">SIGAPDESA <span class="text-rose-600">SYSTEM</span></span>
                </div>
                <div class="flex items-center gap-3">
                    <div class="text-right hidden sm:block">
                        <p class="text-xs font-black text-slate-900 uppercase">{{ $userName }}</p>
                    </div>
                    <span class="px-3 py-1.5 rounded-full bg-rose-50 text-rose-700 text-[11px] font-extrabold uppercase border border-rose-200">{{ $displayRole }}</span>
                </div>
            </header>
            <div class="flex-1 p-4 sm:p-6 overflow-y-auto">
                @yield('content')
            </div>
        </main>
    </div>
@endif

<script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
@stack('scripts')
</body>
</html>