<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("UPDATE users SET empresa_id = empresa WHERE empresa IS NOT NULL");
        DB::statement("UPDATE users SET sucursal_id = sucursal WHERE sucursal IS NOT NULL");
        DB::statement("UPDATE users SET telefono = telefono_usuario WHERE telefono_usuario IS NOT NULL");
        DB::statement("UPDATE users SET es_admin = esAdmin WHERE esAdmin IS NOT NULL");
        DB::statement("UPDATE users SET fecha_login = fechaLogin WHERE fechaLogin IS NOT NULL");
    }

    public function down(): void
    {
        DB::statement("UPDATE users SET empresa_id = NULL");
        DB::statement("UPDATE users SET sucursal_id = NULL");
        DB::statement("UPDATE users SET telefono = NULL");
        DB::statement("UPDATE users SET es_admin = 0");
        DB::statement("UPDATE users SET fecha_login = NULL");
    }
};
