<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddInariskZonaToRumahTable extends Migration
{
    /**
     * Zona rumah kini dihitung otomatis dari indeks bahaya InaRISK BNPB
     * (bukan dicentang manual). Kolom status_zona_tsunami tetap dipakai
     * untuk hasil zonanya (Merah/Kuning/Hijau).
     */
    public function up()
    {
        Schema::table('rumah', function (Blueprint $table) {
            $table->decimal('indeks_bahaya', 6, 4)->nullable()->after('status_zona_tsunami');
            $table->timestamp('zona_dianalisis_at')->nullable()->after('indeks_bahaya');
        });
    }

    public function down()
    {
        Schema::table('rumah', function (Blueprint $table) {
            $table->dropColumn(['indeks_bahaya', 'zona_dianalisis_at']);
        });
    }
}
