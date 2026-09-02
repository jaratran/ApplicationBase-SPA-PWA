<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Eliminar constraints antiguas
            $table->dropForeign('users_empresa_fk');
            $table->dropForeign('users_sucursal_fk');
            $table->dropForeign('users_rol_usuario_fk');

            // Agregar nuevas foreign keys
            $table->foreign('empresa_id')
                  ->references('id')->on('empresas')
                  ->onUpdate('cascade');

            $table->foreign('sucursal_id')
                  ->references('id')->on('sucursales')
                  ->onUpdate('cascade');

            $table->foreign('rol_id')
                  ->references('id')->on('catalogos')
                  ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Eliminar las FKs nuevas
            $table->dropForeign(['empresa_id']);
            $table->dropForeign(['sucursal_id']);
            $table->dropForeign(['rol_id']);

            // Restaurar constraints antiguas
            $table->foreign('empresa')
                  ->references('id')->on('empresas')
                  ->onUpdate('cascade');

            $table->foreign('sucursal')
                  ->references('id')->on('sucursales')
                  ->onUpdate('cascade');

            $table->foreign('rol_usuario')
                  ->references('id')->on('catalogos')
                  ->onUpdate('cascade');
        });
    }
};
