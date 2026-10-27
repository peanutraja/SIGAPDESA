<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\WargaController;
use App\Http\Controllers\PendudukController;
use App\Http\Controllers\EvakuasiController;
use App\Http\Controllers\WargaDashboardController;
use App\Http\Controllers\WilayahController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\PengunjungController;
use App\Http\Controllers\PengunjungDashboardController;
use App\Http\Controllers\InaRiskController;


// Autentikasi Publik
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Rute Terproteksi Sesi Admin (Pejabat & Pengurus Desa)
Route::middleware(['cek.session:admin'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Status & hapus laporan pendatang oleh pengurus wilayah
    Route::post('/pengunjung/{id}/status', [PengunjungController::class, 'updateStatus'])->name('pengunjung.updateStatus');
    Route::delete('/pengunjung/{id}', [PengunjungController::class, 'destroy'])->name('pengunjung.destroy');

    // Manajemen Akun Pengelola oleh GMLS
    Route::get('/kelola-user', [AdminUserController::class, 'index'])->name('admin.user.index');
    Route::post('/kelola-user', [AdminUserController::class, 'store'])->name('admin.user.store');
    Route::delete('/kelola-user/{id}', [AdminUserController::class, 'destroy'])->name('admin.user.destroy');

    // Verifikasi Pengajuan Update Data Warga
    Route::get('/verifikasi-pengajuan', [WargaController::class, 'indexPengajuan'])->name('admin.pengajuan.index');
    Route::post('/verifikasi-pengajuan/{id}/setujui', [WargaController::class, 'setujuiPengajuan'])->name('admin.pengajuan.setujui');
    Route::post('/verifikasi-pengajuan/{id}/tolak', [WargaController::class, 'tolakPengajuan'])->name('admin.pengajuan.tolak');

    // Pendataan Penduduk & Fitur CSV (Template / Export / Import)
    Route::get('/input-data', [PendudukController::class, 'create'])->name('penduduk.create');
    Route::post('/input-data', [PendudukController::class, 'store'])->name('penduduk.store');
    Route::get('/input-data/download-template-csv', [PendudukController::class, 'downloadTemplateCsv'])->name('penduduk.template.csv');
    Route::get('/input-data/export-csv', [PendudukController::class, 'exportCsv'])->name('penduduk.export.csv');
    Route::post('/input-data/import-csv', [PendudukController::class, 'importCsv'])->name('penduduk.import.csv');

    // Manajemen Daftar Warga
    Route::get('/daftar-warga', [WargaController::class, 'daftarWarga'])->name('warga.index');
    Route::get('/warga/{id}/edit', [WargaController::class, 'edit'])->name('warga.edit');
    Route::put('/warga/{id}', [WargaController::class, 'update'])->name('warga.update');
    Route::delete('/warga/{id}', [WargaController::class, 'destroy'])->name('warga.destroy');

    // Peta Spasial & Titik Evakuasi
    Route::get('/peta-sebaran', [EvakuasiController::class, 'index'])->name('peta.sebaran');
    Route::get('/get-evakuasi', [EvakuasiController::class, 'getEvakuasi'])->name('evakuasi.get');

    // Layer bahaya InaRISK BNPB (tingkat bahaya di titik & zona otomatis rumah)
    Route::get('/inarisk/hazard-at', [InaRiskController::class, 'hazardAt'])->name('inarisk.hazardAt');
    Route::post('/inarisk/zona/{id}', [InaRiskController::class, 'analisisZona'])->name('inarisk.zona');
    Route::post('/simpan-evakuasi', [EvakuasiController::class, 'simpanEvakuasi'])->name('evakuasi.simpan');
});


// Rute Terproteksi Sesi Warga
Route::middleware(['cek.session:warga'])->group(function () {
    Route::get('/warga-dashboard', [WargaDashboardController::class, 'index'])->name('warga.dashboard');
    Route::post('/warga-dashboard/pengajuan', [WargaDashboardController::class, 'storePengajuan'])->name('warga.dashboard.pengajuan');
});

// Rute Terproteksi Sesi Pengunjung / Pendatang
Route::middleware(['cek.session:pengunjung'])->group(function () {
    Route::get('/pengunjung-dashboard', [PengunjungDashboardController::class, 'index'])->name('pengunjung.dashboard');
    Route::post('/pengunjung-dashboard/pulang', [PengunjungDashboardController::class, 'laporPulang'])->name('pengunjung.pulang');
});


// Modul Pendaftaran Pengunjung / Pendatang (publik)
Route::get('/daftar-pendatang', [PengunjungController::class, 'showRegister'])->name('pengunjung.register');
Route::post('/daftar-pendatang', [PengunjungController::class, 'register'])->name('pengunjung.store');


// Endpoint JSON Dropdown Cascading Wilayah (AJAX)
Route::get('/get-kabupaten', [WilayahController::class, 'getKabupaten'])->name('get.kabupaten');
Route::get('/get-kecamatan', [WilayahController::class, 'getKecamatan'])->name('get.kecamatan');
Route::get('/get-desa', [WilayahController::class, 'getDesa'])->name('get.desa');
Route::get('/get-rw', [WilayahController::class, 'getRw'])->name('get.rw');
Route::get('/get-rt', [WilayahController::class, 'getRt'])->name('get.rt');