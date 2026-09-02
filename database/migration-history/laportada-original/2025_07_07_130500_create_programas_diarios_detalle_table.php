<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends \Illuminate\Database\Migrations\Migration {
    public function up(): void
    {
        Schema::create('programas_diarios_detalle', function (Blueprint $table) {
            $table->bigIncrements('id')->nullable(false);

            // Relaciones base
            $table->unsignedBigInteger('programa_id')->nullable(false);
            $table->unsignedBigInteger('retiro_id')->nullable(false);

            // Control de estado y novedad
            $table->tinyInteger('estado')->default(0);   // 0=en proceso, 1=efectuada, 2=cancelada
            $table->tinyInteger('novedad')->default(0);  // 0=sin cambios, 1=actualizado, 2=nuevo

            // Campos referenciados desde retiros, planificaciones, maquila, sucursales, camiones
            $table->unsignedBigInteger('sucursal_id')->nullable();              // maquila.sucursal_id (via retiros->solicitudes->maquilas)
            $table->unsignedBigInteger('comuna_id')->nullable();                // sucursales.comuna_id (vía retiros->solicitudes->maquilas->sucursales)
            $table->unsignedBigInteger('proveedor_id')->nullable();             // maquilas.empresa_id  (via retiros->solicitudes->maquilas)
            $table->dateTime('fecha_hora_retiro')->nullable();                  // planificaciones.fecha_hora_planificada (via retiros->planificaciones)
            $table->unsignedBigInteger('camion_id')->nullable();                // planificaciones.camion_id (via retiros->planificaciones)
            $table->unsignedBigInteger('tipo_retiro_id')->nullable();           // retiros.tipo_retiro_id
            $table->string('duracion_viaje', 5)->nullable();                    // planificaciones.duracion_viaje (via retiros->planificaciones)
            $table->dateTime('eta')->nullable();                                // planificaciones.hora_llegada_estimada (via retiros->planificaciones)
            $table->decimal('kilogramos_estimados', 8, 2)->nullable();          // retiros.kilogramos_estimados
            $table->unsignedBigInteger('producto_id')->nullable();              // planificaciones.tipo_materia_prima_id (via retiros->planificaciones)
            $table->unsignedBigInteger('especie_id')->nullable();               // planificaciones.especie_id (via retiros->planificaciones)
            $table->unsignedInteger('bins')->nullable();                        // retiros.cantidad_bins

            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            // Foreign keys
            $table->foreign('programa_id')->references('id')->on('programas_diarios')->onDelete('cascade');
            $table->foreign('retiro_id')->references('id')->on('retiros')->onUpdate('cascade');
            $table->foreign('sucursal_id')->references('id')->on('sucursales')->onUpdate('cascade');
            $table->foreign('proveedor_id')->references('id')->on('empresas')->onUpdate('cascade');
            $table->foreign('comuna_id')->references('id')->on('comunas')->onUpdate('cascade');
            $table->foreign('camion_id')->references('id')->on('camiones')->onUpdate('cascade');
            $table->foreign('tipo_retiro_id')->references('id')->on('catalogos')->onUpdate('cascade');
            $table->foreign('producto_id')->references('id')->on('catalogos')->onUpdate('cascade');
            $table->foreign('especie_id')->references('id')->on('catalogos')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('programas_diarios_detalle');
    }
};
