<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Después de 'rol_usuario' → empresa_id y sucursal_id
            $table->unsignedBigInteger('empresa_id')->nullable()->after('rol_usuario');
            $table->unsignedBigInteger('sucursal_id')->nullable()->after('empresa_id');

            // Después de 'telefono_usuario' → telefono
            $table->text('telefono')->nullable()->after('telefono_usuario');

            // Después de 'esAdmin' → es_admin
            $table->unsignedBigInteger('es_admin')->default(0)->after('esAdmin');

            // Después de 'fechaLogin' → fecha_login
            $table->string('fecha_login')->nullable()->after('fechaLogin');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('empresa_id');
            $table->dropColumn('sucursal_id');
            $table->dropColumn('telefono');
            $table->dropColumn('es_admin');
            $table->dropColumn('fecha_login');
        });
    }
};
