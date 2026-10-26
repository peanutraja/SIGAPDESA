<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Pendatang - SIGAPDESA</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="{{ asset('vendor/fontawesome-free/css/all.min.css') }}" rel="stylesheet">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; }</style>
</head>
<body class="min-h-screen flex items-center justify-center py-10 px-4">

    <div class="w-full max-w-3xl bg-white rounded-3xl shadow-xl border border-slate-200 p-8 sm:p-10">
        
        <div class="text-center mb-8">
            <h2 class="text-2xl font-black text-slate-900 tracking-tight uppercase">Lapor Pendatang Baru</h2>
            <p class="text-sm font-medium text-slate-500 mt-1">Isi formulir untuk melaporkan kunjungan / domisili sementara Anda</p>
        </div>

        <!-- NOTIFIKASI BERHASIL -->
        @if (session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-600 p-4 rounded-xl mb-4 text-sm font-medium">
            <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
        </div>
        @endif

        <!-- NOTIFIKASI ERROR -->
        @if ($errors->any())
        <div class="bg-rose-50 text-rose-600 p-4 rounded-xl mb-4 text-sm">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form action="{{ route('pengunjung.store') }}" method="POST" class="space-y-6">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- DATA DIRI -->
                <div class="space-y-4">
                    <h3 class="text-sm font-black text-rose-600 border-b pb-2">Informasi Pribadi</h3>
                    
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">NIK KTP <span class="text-rose-600">*</span></label>
                        <input type="text" name="nik" id="nik" required minlength="16" maxlength="16" pattern="[0-9]{16}" oninput="this.value = this.value.replace(/[^0-9]/g, '');" placeholder="16 Digit NIK" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-rose-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Nama Lengkap <span class="text-rose-600">*</span></label>
                        <input type="text" name="nama_lengkap" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-rose-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Nomor Telepon/WA <span class="text-rose-600">*</span></label>
                        <input type="number" name="nomor_telepon" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-rose-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Tanggal Lahir <span class="text-rose-600">*</span></label>
                        <input type="date" name="tanggal_lahir" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-rose-500 outline-none">
                        <p class="text-[10px] text-rose-500 mt-1 font-medium leading-tight">
                            *Catatan: Tanggal lahir akan otomatis menjadi Password login Anda (Format: ddmmyyyy, contoh: 31121998).
                        </p>
                    </div>
                </div>

                <!-- INFO KUNJUNGAN -->
                <div class="space-y-4">
                    <h3 class="text-sm font-black text-rose-600 border-b pb-2">Tujuan Kunjungan Wilayah</h3>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-700 uppercase mb-1">Provinsi <span class="text-rose-600">*</span></label>
                            <select name="id_provinsi" id="id_provinsi" required class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-rose-500">
                                <option value="">- Pilih -</option>
                                @foreach($provinsi as $p)
                                    <option value="{{ $p->id_provinsi }}">{{ $p->nama_provinsi }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-700 uppercase mb-1">Kabupaten <span class="text-rose-600">*</span></label>
                            <select name="id_kabupaten" id="id_kabupaten" required class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-rose-500"><option value="">- Pilih -</option></select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-700 uppercase mb-1">Kecamatan <span class="text-rose-600">*</span></label>
                            <select name="id_kecamatan" id="id_kecamatan" required class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-rose-500"><option value="">- Pilih -</option></select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-700 uppercase mb-1">Desa <span class="text-rose-600">*</span></label>
                            <select name="id_desa" id="id_desa" required class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-rose-500"><option value="">- Pilih -</option></select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-700 uppercase mb-1">Nomor RW <span class="text-rose-600">*</span></label>
                            <select name="id_rw" id="id_rw" required class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-rose-500"><option value="">- Pilih -</option></select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-700 uppercase mb-1">Nomor RT <span class="text-rose-600">*</span></label>
                            <select name="id_rt" id="id_rt" required class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-rose-500"><option value="">- Pilih -</option></select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Alamat Asal Daerah <span class="text-rose-600">*</span></label>
                        <input type="text" name="asal_daerah" required placeholder="Contoh: Jakarta Selatan" class="w-full px-4 py-2 bg-slate-50 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-rose-500 outline-none">
                    </div>
                    <div class="grid grid-cols-3 gap-3">
                        <div class="col-span-2">
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Tujuan / Keperluan <span class="text-rose-600">*</span></label>
                            <input type="text" name="tujuan_kunjungan" required placeholder="Misal: Kunjungan keluarga" class="w-full px-4 py-2 bg-slate-50 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-rose-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Lama (Hari) <span class="text-rose-600">*</span></label>
                            <input type="number" name="lama_kunjungan" required min="1" class="w-full px-4 py-2 bg-slate-50 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-rose-500 outline-none">
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between pt-6 border-t border-slate-100">
                <a href="{{ route('login') }}" class="text-xs font-bold text-slate-500 hover:text-slate-800"><i class="fas fa-arrow-left"></i> Kembali ke Login</a>
                <button type="submit" class="bg-rose-600 hover:bg-rose-700 text-white font-bold py-3 px-6 rounded-xl transition-all shadow-md">
                    Kirim Laporan <i class="fas fa-paper-plane ml-1"></i>
                </button>
            </div>
        </form>
    </div>

    <!-- Script jQuery untuk Dropdown Cascading -->
    <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            $('#id_provinsi').change(function(){
                let id = $(this).val(); $('#id_kabupaten').html('<option value="">Loading...</option>');
                $.get("{{ route('get.kabupaten') }}", { id_provinsi: id }, function(data){
                    let opt = '<option value="">- Pilih -</option>';
                    data.forEach(function(val){ opt += `<option value="${val.id_kabupaten}">${val.nama_kabupaten}</option>`; });
                    $('#id_kabupaten').html(opt); $('#id_kecamatan, #id_desa, #id_rw, #id_rt').html('<option value="">- Pilih -</option>');
                });
            });
            $('#id_kabupaten').change(function(){
                let id = $(this).val(); $('#id_kecamatan').html('<option value="">Loading...</option>');
                $.get("{{ route('get.kecamatan') }}", { id_kabupaten: id }, function(data){
                    let opt = '<option value="">- Pilih -</option>';
                    data.forEach(function(val){ opt += `<option value="${val.id_kecamatan}">${val.nama_kecamatan}</option>`; });
                    $('#id_kecamatan').html(opt); $('#id_desa, #id_rw, #id_rt').html('<option value="">- Pilih -</option>');
                });
            });
            $('#id_kecamatan').change(function(){
                let id = $(this).val(); $('#id_desa').html('<option value="">Loading...</option>');
                $.get("{{ route('get.desa') }}", { id_kecamatan: id }, function(data){
                    let opt = '<option value="">- Pilih -</option>';
                    data.forEach(function(val){ opt += `<option value="${val.id_desa}">${val.nama_desa}</option>`; });
                    $('#id_desa').html(opt); $('#id_rw, #id_rt').html('<option value="">- Pilih -</option>');
                });
            });
            $('#id_desa').change(function(){
                let id = $(this).val(); $('#id_rw').html('<option value="">Loading...</option>');
                $.get("{{ route('get.rw') }}", { id_desa: id }, function(data){
                    let opt = '<option value="">- Pilih -</option>';
                    data.forEach(function(val){ opt += `<option value="${val.id_rw}">${val.nomor_rw}</option>`; });
                    $('#id_rw').html(opt); $('#id_rt').html('<option value="">- Pilih -</option>');
                });
            });
            $('#id_rw').change(function(){
                let id = $(this).val(); $('#id_rt').html('<option value="">Loading...</option>');
                $.get("{{ route('get.rt') }}", { id_rw: id }, function(data){
                    let opt = '<option value="">- Pilih -</option>';
                    data.forEach(function(val){ opt += `<option value="${val.id_rt}">${val.nomor_rt}</option>`; });
                    $('#id_rt').html(opt);
                });
            });
        });
    </script>
</body>
</html>