<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Eliminar las FKs que apuntan erróneamente a la tabla `comuna`
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign('users_comuna_fk');
        });

        Schema::table('empresas', function (Blueprint $table) {
            $table->dropForeign('empresas_comuna_id_foreign');
        });

        Schema::table('sucursales', function (Blueprint $table) {
            $table->dropForeign('sucursales_comuna_id_foreign');
        });

        // Crear nuevas FKs apuntando a `comunas`
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('comuna_id', 'users_comuna_id_foreign')
                  ->references('id')->on('comunas')
                  ->onUpdate('cascade');
        });

        Schema::table('empresas', function (Blueprint $table) {
            $table->foreign('comuna_id', 'empresas_comuna_id_foreign')
                  ->references('id')->on('comunas')
                  ->onUpdate('cascade');
        });

        Schema::table('sucursales', function (Blueprint $table) {
            $table->foreign('comuna_id', 'sucursales_comuna_id_foreign')
                  ->references('id')->on('comunas')
                  ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        // Revertir a las FKs originales apuntando a `comuna`
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign('users_comuna_id_foreign');
        });

        Schema::table('empresas', function (Blueprint $table) {
            $table->dropForeign('empresas_comuna_id_foreign');
        });

        Schema::table('sucursales', function (Blueprint $table) {
            $table->dropForeign('sucursales_comuna_id_foreign');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('comuna_id', 'users_comuna_fk')
                  ->references('id')->on('comuna')
                  ->onUpdate('cascade');
        });

        Schema::table('empresas', function (Blueprint $table) {
            $table->foreign('comuna_id', 'empresas_comuna_id_foreign')
                  ->references('id')->on('comuna');
        });

        Schema::table('sucursales', function (Blueprint $table) {
            $table->foreign('comuna_id', 'sucursales_comuna_id_foreign')
                  ->references('id')->on('comuna');
        });
    }
};
