<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('planificaciones', function (Blueprint $table) {
            $table->dropColumn('cantidad_bins_reponer');
        });
    }

    public function down(): void
    {
        Schema::table('planificaciones', function (Blueprint $table) {
            $table->integer('cantidad_bins_reponer')->default(0)->after('patente_rampla');
        });
    }
};