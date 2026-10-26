<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SIGAPDESA System</title>

    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brandRed: '#dc2626',
                        brandDarkRed: '#b91c1c',
                        brandDark: '#0f172a',
                    },
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                    }
                }
            }
        }
    </script>

    <!-- FontAwesome -->
    <link href="{{ asset('vendor/fontawesome-free/css/all.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f8fafc;
            /* Pola titik-titik halus untuk latar belakang */
            background-image: radial-gradient(#cbd5e1 1px, transparent 1px);
            background-size: 20px 20px;
        }
        .animate-fade-in-up {
            animation: fadeInUp 0.6s ease-out forwards;
        }
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-[420px] bg-white rounded-3xl shadow-xl border border-slate-200 p-8 sm:p-10 animate-fade-in-up">
        
        <!-- Header Logo & Title -->
        <div class="text-center mb-8 space-y-4">
            <div class="flex justify-center">
                <img src="{{ asset('img/sigapdesa_logo.png') }}" alt="Logo SIGAPDESA" class="h-40 w-auto object-contain mx-auto drop-shadow-sm" onerror="this.onerror=null;this.src=this.dataset.fallback;" data-fallback="{{ asset('img/gmls_logo_red.png') }}">
            </div>
            <div>
                <h2 class="text-2xl font-black text-slate-900 tracking-tight">Selamat Datang</h2>
                <p class="text-sm font-medium text-slate-500 mt-1">Sistem Informasi Geografis & Kebencanaan</p>
                <p class="text-sm font-medium text-slate-500 mt-1">Gugus Mitigasi Lebak Selatan</p>
            </div>
        </div>

        <!-- Alert Error -->
        @if(session('error'))
            <div class="mb-6 flex items-center gap-3 bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 rounded-xl text-xs font-bold shadow-sm">
                <i class="fas fa-exclamation-circle text-rose-500 text-lg shrink-0"></i>
                <span class="leading-relaxed">{{ session('error') }}</span>
            </div>
        @endif

        <!-- Form Login -->
        <form action="{{ route('login.post') }}" method="POST" class="space-y-5">
            @csrf
            
            <!-- Tipe Akun -->
            <div class="space-y-1.5">
                <label for="tipe" class="block text-xs font-bold text-slate-700 uppercase tracking-wide">
                    Login Sebagai <span class="text-rose-600">*</span>
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                        <i class="fas fa-user-shield text-slate-400"></i>
                    </div>
                    <select name="tipe" id="tipe" required
                        class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-300 text-slate-900 text-sm font-semibold rounded-xl focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-rose-500 transition-shadow appearance-none cursor-pointer">
                        <option value="" disabled selected class="text-slate-400">-- Pilih Tipe Akun --</option>
                        <option value="admin" {{ old('tipe') == 'admin' ? 'selected' : '' }}>Admin</option>
                        <option value="warga" {{ old('tipe') == 'warga' ? 'selected' : '' }}>Warga</option>
                    </select>
                    <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none">
                        <i class="fas fa-chevron-down text-slate-400 text-xs"></i>
                    </div>
                </div>
            </div>

            <!-- Username / NIK -->
            <div class="space-y-1.5">
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide">
                    Username / NIK <span class="text-rose-600">*</span>
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                        <i class="fas fa-id-card text-slate-400"></i>
                    </div>
                    <input type="text" name="username" value="{{ old('username') }}" required
                        placeholder="Masukkan username atau NIK"
                        class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-300 text-slate-900 text-sm font-semibold rounded-xl focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-rose-500 transition-shadow placeholder:font-medium placeholder:text-slate-400">
                </div>
            </div>

            <!-- Password -->
            <div class="space-y-1.5">
                <div class="flex justify-between items-center">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide">
                        Kata Sandi <span class="text-rose-600">*</span>
                    </label>
                </div>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                        <i class="fas fa-lock text-slate-400"></i>
                    </div>
                    <input type="password" name="password" required
                        placeholder="Password atau Tgl Lahir (DDMMYYYY)"
                        class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-300 text-slate-900 text-sm font-semibold rounded-xl focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-rose-500 transition-shadow placeholder:font-medium placeholder:text-slate-400">
                </div>
            </div>

            <!-- Tombol Submit -->
            <button type="submit"
                class="w-full flex items-center justify-center gap-2 bg-rose-600 hover:bg-rose-700 active:bg-rose-800 text-white font-bold py-3.5 px-4 rounded-xl transition-all shadow-md shadow-rose-200 mt-2 cursor-pointer">
                Masuk ke Sistem <i class="fas fa-arrow-right text-sm"></i>
            </button>
        </form>

        <!-- TOMBOL PENTDATANG -->
        <div class="mt-6 text-center border-t border-slate-100 pt-5">
            <p class="text-xs font-semibold text-slate-500 mb-2">
                Anda pendatang / pengunjung dari luar desa?
            </p>
            <a href="{{ route('pengunjung.register') }}" class="inline-flex items-center gap-2 text-rose-600 hover:text-rose-700 font-black transition text-sm">
                <i class="fas fa-user-plus"></i> Lapor & Buat Akun Pendatang
            </a>
        </div>

        <!-- Footer -->
        <div class="mt-8 text-center border-t border-slate-100 pt-6">
            <p class="text-xs font-semibold text-slate-400">
                &copy; {{ date('Y') }} SIGAPDESA System<br>
                <span class="font-medium text-[10px]">Dikembangkan oleh GMLS</span>
            </p>
        </div>
    </div>

</body>
</html>