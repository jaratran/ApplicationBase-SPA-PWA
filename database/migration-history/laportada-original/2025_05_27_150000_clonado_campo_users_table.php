<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Agregar campo rol_id después de rol_usuario
            $table->unsignedBigInteger('rol_id')->nullable()->after('rol_usuario');
        });

        // Poblar el nuevo campo con los valores de rol_usuario
        DB::statement("UPDATE users SET rol_id = rol_usuario WHERE rol_usuario IS NOT NULL");
    }

    public function down(): void
    {
        // Revertir: eliminar campo rol_id
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('rol_id');
        });
    }
};
