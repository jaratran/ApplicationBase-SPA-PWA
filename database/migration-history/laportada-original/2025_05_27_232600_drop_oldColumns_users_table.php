<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'empresa',
                'sucursal',
                'rol_usuario',
                'telefono_usuario',
                'esAdmin',
                'fechaLogin'
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('empresa')->nullable()->after('rol_id');
            $table->unsignedBigInteger('sucursal')->nullable()->after('empresa');
            $table->unsignedBigInteger('rol_usuario')->nullable()->after('sucursal');
            $table->text('telefono_usuario')->nullable()->after('email');
            $table->unsignedBigInteger('esAdmin')->default(0)->after('direccion');
            $table->string('fechaLogin')->nullable()->after('esAdmin');
        });
    }
};
