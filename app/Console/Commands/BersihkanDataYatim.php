<?php

namespace App\Console\Commands;

use App\Services\DataYatim;
use Illuminate\Console\Command;

class BersihkanDataYatim extends Command
{
    protected $signature = 'sigap:bersihkan-data';

    protected $description = 'Hapus Kartu Keluarga, rumah, dan pengajuan yang sudah tidak punya warga';

    public function handle()
    {
        $hasil = DataYatim::bersihkanSemua();
        $this->info("Dihapus: {$hasil['kk']} Kartu Keluarga, {$hasil['rumah']} rumah, {$hasil['pengajuan']} pengajuan tanpa warga.");
        return 0;
    }
}
