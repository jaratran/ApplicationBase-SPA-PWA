<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('retiros', function (Blueprint $table) {
            // Cambiar tipo de campo fecha_retiro de DATE a DATETIME
            $table->dateTime('fecha_retiro')->change();

            // Eliminar campos no utilizados
            $table->dropColumn(['es_urgente', 'observaciones']);

            // Agregar campos utilizados por el formulario
            $table->decimal('kilogramos_estimados', 8, 2)->after('tipo_retiro_id');
            $table->boolean('requiere_reposicion')->default(false)->after('kilogramos_estimados');
            $table->unsignedInteger('cantidad_bins')->nullable()->after('requiere_reposicion');
        });
    }

    public function down(): void
    {
        Schema::table('retiros', function (Blueprint $table) {
            // Restaurar tipo original de fecha_retiro
            $table->date('fecha_retiro')->change();

            // Restaurar campos eliminados
            $table->boolean('es_urgente')->default(false);
            $table->text('observaciones')->nullable();

            // Eliminar campos agregados
            $table->dropColumn(['kilogramos_estimados', 'requiere_reposicion', 'cantidad_bins']);
        });
    }
};
