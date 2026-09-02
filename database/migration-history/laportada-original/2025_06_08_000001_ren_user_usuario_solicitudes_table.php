<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('solicitudes', function (Blueprint $table) {
            // Agregar campo usuario_id
            $table->unsignedBigInteger('usuario_id')->nullable(false)->after('id');

            // Eliminamos FK: solicitudes.users_id → users.id
            $table->dropForeign('solicitudes_user_id_foreign');
            // Eliminar campo user_id
            $table->dropColumn('user_id');

            // Creamos FK: solicitudes.usuario_id → users.id
            $table->foreign('usuario_id', 'solicitudes_usuario_id_fk')
                ->references('id')->on('users')
                ->onDelete('restrict')
                ->onUpdate('cascade');
        });
    }

    public function down(): void {
        Schema::table('solicitudes', function (Blueprint $table) {
            // Agregar campo usuario_id
            $table->unsignedBigInteger('user_id')->nullable(false)->after('id');

            // Eliminamos FK: solicitudes.usuario_id → users.id
            $table->dropForeign('solicitudes_usuario_id_fk');
            // Eliminar campo usuario_id
            $table->dropColumn('usuario_id');

            // Creamos FK: solicitudes.usuario_id → users.id
            $table->foreign('user_id', 'solicitudes_user_id_foreign')
                ->references('id')->on('users')
                ->onDelete('restrict')
                ->onUpdate('cascade');
        });
    }
};
