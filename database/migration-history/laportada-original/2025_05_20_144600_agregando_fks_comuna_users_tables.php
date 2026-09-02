<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // FK: comuna.region_comuna → region.id
        Schema::table('comuna', function (Blueprint $table) {
            $table->foreign('region_comuna', 'comuna_region_fk')
               ->references('id')->on('region')
               ->onDelete('restrict')
               ->onUpdate('cascade');
        });

        Schema::table('users', function (Blueprint $table) {
            // FK: users.sucursal → sucursales.id
            $table->foreign('sucursal', 'users_sucursal_fk')
                ->references('id')->on('sucursales')
                ->onDelete('restrict')
                ->onUpdate('cascade');

            // FK: users.rol_usuario → catalogos.id
            $table->foreign('rol_usuario', 'users_rol_usuario_fk')
                ->references('id')->on('catalogos')
                ->onDelete('restrict')
                ->onUpdate('cascade');

            // FK: users.comuna_id → comuna.id
            $table->foreign('comuna_id', 'users_comuna_fk')
                ->references('id')->on('comuna')
                ->onDelete('restrict')
                ->onUpdate('cascade');
        });

    }

    public function down(): void
    {
        // FK: comuna.region_comuna → region.id
        Schema::table('comuna', function (Blueprint $table) {
            $table->dropForeign('comuna_region_fk');
        });

        Schema::table('users', function (Blueprint $table) {
            // FK: users.sucursal → sucursales.id
            $table->dropForeign('users_sucursal_fk');

            // FK: users.rol_usuario → catalogos.id
            $table->dropForeign('users_rol_usuario_fk');

            // FK: users.comuna_id → comuna.id
            $table->dropForeign('users_comuna_fk');
        });

    }
};