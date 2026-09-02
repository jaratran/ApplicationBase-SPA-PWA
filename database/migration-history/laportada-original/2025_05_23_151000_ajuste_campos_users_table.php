<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Paso 1: Crear campo temporal al final
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('activo')->default(true)->after('esAdmin');
            $table->text('observacion_inactividad')->nullable()->after('activo');;
        });

        // Paso 2: Copiar datos desde vigencia a activo
        DB::statement('UPDATE users SET activo = CASE WHEN vigencia = 1 THEN 1 ELSE 0 END');

        // Paso 3: Eliminar campo empresa
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('vigencia');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->bigInteger('vigencia')->nullable(false)->after('esAdmin');
        });

        DB::statement('UPDATE users SET vigencia = CASE WHEN activo = 1 THEN 1 ELSE 0 END');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('activo');
            $table->dropColumn('observacion_inactividad');
        });
    }
};
