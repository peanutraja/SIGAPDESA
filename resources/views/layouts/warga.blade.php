<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Portal SIGAPDESA')</title>

    <!-- Google Fonts & Tailwind CSS -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- DaisyUI v4 (kompatibel dengan Tailwind v3 dari Play CDN) -->
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.10/dist/full.min.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="{{ asset('vendor/fontawesome-free/css/all.min.css') }}" rel="stylesheet">

    <style>
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background-color: #f8fafc; 
            color: #0f172a;
        }
    </style>
</head>
<body class="bg-slate-50 antialiased font-sans flex flex-col min-h-screen">

@php
    $isPengunjung = session()->has('pengunjung');
    $user = $isPengunjung ? session('pengunjung') : session('warga');
    $namaLengkap = $user['nama_lengkap'] ?? 'Pengguna';
    
    $tipeAkun = $isPengunjung ? 'Akun Pendatang' : 'Akun Warga';
    $badgeText = $isPengunjung ? 'Portal Tamu / Pendatang' : 'Portal Warga Mandiri';
    $badgeClass = $isPengunjung ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-blue-50 text-blue-700 border-blue-200';
    $iconClass = $isPengunjung ? 'fas fa-suitcase-rolling text-emerald-600' : 'fas fa-users text-blue-600';
@endphp

    <!-- TOPBAR -->
    <header class="bg-white text-slate-800 shadow-sm sticky top-0 z-50 border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3 flex items-center justify-between">
            
            <div class="flex items-center gap-3.5">
                <img src="{{ asset('img/sigapdesa_logo.png') }}" alt="Logo SIGAPDESA" class="h-11 sm:h-20 w-auto object-contain drop-shadow-sm">
                <span class="hidden sm:inline-block h-6 w-px bg-slate-200"></span>
                <span class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1 rounded-full {{ $badgeClass }} text-xs font-black uppercase tracking-wider">
                    <i class="{{ $iconClass }}"></i> {{ $badgeText }}
                </span>
            </div>

            <div class="flex items-center gap-3">
                <div class="flex items-center gap-2.5 bg-slate-50 px-3.5 py-1.5 rounded-xl border border-slate-200 shadow-sm">
                    <!-- Menggunakan warna bawaan Tailwind agar tidak hilang -->
                    <div class="w-8 h-8 rounded-xl bg-rose-600 text-white flex items-center justify-center text-xs font-black uppercase">
                        {{ substr($namaLengkap, 0, 1) }}
                    </div>
                    <div class="text-left hidden sm:block">
                        <p class="text-xs font-extrabold text-slate-900 leading-tight">{{ $namaLengkap }}</p>
                        <p class="text-[10px] font-bold text-rose-600 uppercase tracking-wide leading-tight">{{ $tipeAkun }}</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit" onclick="return confirm('Keluar dari portal?')"
                        class="p-2.5 rounded-xl bg-rose-50 hover:bg-rose-600 text-rose-600 hover:text-white transition text-xs font-bold border border-rose-200 cursor-pointer shadow-sm" title="Keluar">
                        <i class="fas fa-power-off"></i>
                    </button>
                </form>
            </div>
        </div>
    </header>

    <main class="flex-1 py-6 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto w-full">
        @yield('content')
    </main>

    <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
    @stack('scripts')
</body>
</html>