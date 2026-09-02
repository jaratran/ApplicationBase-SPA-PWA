<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Eliminar índices antiguos innecesarios
            $table->dropIndex('users_empresa_fk');
            $table->dropIndex('users_sucursal_fk');
            $table->dropIndex('users_rol_usuario_fk');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Restaurar índices antiguos si fuera necesario (sin FK, solo índice)
            $table->index('empresa', 'users_empresa_fk');
            $table->index('sucursal', 'users_sucursal_fk');
            $table->index('rol_usuario', 'users_rol_usuario_fk');
        });
    }
};
