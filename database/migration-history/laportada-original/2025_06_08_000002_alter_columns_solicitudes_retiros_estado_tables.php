<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Ajustes a la tabla solicitudes
        Schema::table('solicitudes', function (Blueprint $table) {
            if (Schema::hasColumn('solicitudes', 'estado')) {
                $table->dropColumn('estado');
            }
            if (Schema::hasColumn('solicitudes', 'observaciones')) {
                $table->dropColumn('observaciones');
            }
        });

        // Ajustes a la tabla retiros
        Schema::table('retiros', function (Blueprint $table) {
            if (!Schema::hasColumn('retiros', 'estado')) {
                $table->tinyInteger('estado')->default(0)->after('cantidad_bins');
            }
            if (Schema::hasColumn('retiros', 'volumen_estimado')) {
                $table->dropColumn('volumen_estimado');
            }
        });
    }

    public function down(): void
    {
        // Revertir ajustes en solicitudes
        Schema::table('solicitudes', function (Blueprint $table) {
            if (!Schema::hasColumn('solicitudes', 'estado')) {
                $table->tinyInteger('estado')->default(0)->after('maquila_id');
            }
            if (!Schema::hasColumn('solicitudes', 'observaciones')) {
                $table->text('observaciones')->nullable()->after('estado');
            }
        });

        // Revertir ajustes en retiros
        Schema::table('retiros', function (Blueprint $table) {
            if (Schema::hasColumn('retiros', 'estado')) {
                $table->dropColumn('estado');
            }
            if (!Schema::hasColumn('retiros', 'volumen_estimado')) {
                $table->decimal('volumen_estimado', 8, 2)->nullable()->after('cantidad_bins');
            }
        });
    }
};
