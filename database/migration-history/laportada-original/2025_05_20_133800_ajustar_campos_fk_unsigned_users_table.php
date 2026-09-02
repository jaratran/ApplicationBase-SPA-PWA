<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('sucursal')->change();
            $table->unsignedBigInteger('rol_usuario')->change();
            $table->unsignedBigInteger('comuna_id')->change();
        });
    }

    public function down(): void
    {
        // Solo si estás seguro de querer revertir, agregar:
        // $table->bigInteger('sucursal')->change();
    }
};
