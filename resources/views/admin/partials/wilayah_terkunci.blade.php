{{-- Wilayah akun RT: tidak bisa diubah, data penduduk otomatis masuk ke RT ini --}}
<div class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 sm:p-4 space-y-2">
    <p class="text-xs font-extrabold text-emerald-800 flex items-center gap-2">
        <i class="fas fa-lock"></i> Wilayah terkunci sesuai akun RT Anda
    </p>
    <div class="flex flex-wrap gap-1.5 text-[11px] font-bold">
        <span class="px-2 py-1 rounded-lg bg-white text-slate-700 border border-emerald-100">{{ $wilayahRt['nama_provinsi'] ?? '-' }}</span>
        <span class="px-2 py-1 rounded-lg bg-white text-slate-700 border border-emerald-100">{{ $wilayahRt['nama_kabupaten'] ?? '-' }}</span>
        <span class="px-2 py-1 rounded-lg bg-white text-slate-700 border border-emerald-100">Kec. {{ $wilayahRt['nama_kecamatan'] ?? '-' }}</span>
        <span class="px-2 py-1 rounded-lg bg-white text-slate-700 border border-emerald-100">Desa {{ $wilayahRt['nama_desa'] ?? '-' }}</span>
        <span class="px-2 py-1 rounded-lg bg-amber-100 text-amber-800">RW {{ $wilayahRt['nomor_rw'] ?? '-' }}</span>
        <span class="px-2 py-1 rounded-lg bg-rose-100 text-rose-800">RT {{ $wilayahRt['nomor_rt'] ?? '-' }}</span>
    </div>
</div>
