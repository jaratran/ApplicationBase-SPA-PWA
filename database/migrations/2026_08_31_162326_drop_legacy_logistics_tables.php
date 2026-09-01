<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('programas_diarios_detalle');
        Schema::dropIfExists('planificaciones');
        Schema::dropIfExists('retiros_comentarios');
        Schema::dropIfExists('retiros_historial');
        Schema::dropIfExists('telegram_links');
        Schema::dropIfExists('programas_diarios');
        Schema::dropIfExists('camiones');
        Schema::dropIfExists('conductores');
        Schema::dropIfExists('retiros');
        Schema::dropIfExists('solicitudes');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        throw new LogicException(
            'This migration is intentionally irreversible: the legacy logistics schema is not part of ApplicationBase.'
        );
    }
};
