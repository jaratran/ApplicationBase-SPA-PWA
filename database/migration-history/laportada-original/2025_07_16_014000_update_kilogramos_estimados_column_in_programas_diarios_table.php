<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('programas_diarios_detalle', function (Blueprint $table) {
            $table->unsignedInteger('kilogramos_estimados')->change();
        });
    }

    public function down(): void
    {
        Schema::table('programas_diarios_detalle', function (Blueprint $table) {
            $table->decimal('kilogramos_estimados', 8, 2)->nullable()->change();
        });
    }
};
