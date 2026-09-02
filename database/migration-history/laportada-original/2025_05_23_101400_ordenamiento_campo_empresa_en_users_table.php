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
            $table->unsignedBigInteger('empresa_tmp')->nullable();
        });

        // Paso 2: Copiar datos desde empresa a empresa_tmp
        DB::statement('UPDATE users SET empresa_tmp = empresa');

        // Paso 3: Eliminar campo empresa
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign('users_empresa_fk'); // importante: eliminar FK antes
            $table->dropColumn('empresa');
        });

        // Paso 4: Crear campo empresa en la posición deseada (después de apellidos_usuario)
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('empresa')->nullable()->after('apellidos_usuario');
        });

        // Paso 5: Copiar los datos desde empresa_tmp a empresa
        DB::statement('UPDATE users SET empresa = empresa_tmp');

        // Paso 6: Eliminar campo empresa_tmp
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('empresa_tmp');
        });

        // Paso 7: Restaurar clave foránea
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('empresa', 'users_empresa_fk')
                  ->references('id')->on('empresas')
                  ->onDelete('restrict')
                  ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        // Esta reversión sólo devuelve el campo al final
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['empresa']);
            $table->dropColumn('empresa');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('empresa')->nullable()->after('direccion');
            $table->foreign('empresa', 'users_empresa_fk')
                  ->references('id')->on('empresas')
                  ->onDelete('restrict')
                  ->onUpdate('cascade');
        });
    }
};
