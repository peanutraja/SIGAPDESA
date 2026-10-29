<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Menambah jabatan BPBD: pemantau data penduduk seluruh Indonesia (hanya melihat).
 */
class AddBpbdRoleToAdminTable extends Migration
{
    public function up()
    {
        DB::statement("ALTER TABLE admin MODIFY role ENUM('gmls','bupati','camat','pemerintah_desa','kepala_desa','ketua_rw','ketua_rt','bpbd') NOT NULL DEFAULT 'gmls'");
    }

    public function down()
    {
        DB::table('admin')->where('role', 'bpbd')->delete();
        DB::statement("ALTER TABLE admin MODIFY role ENUM('gmls','bupati','camat','pemerintah_desa','kepala_desa','ketua_rw','ketua_rt') NOT NULL DEFAULT 'gmls'");
    }
}
