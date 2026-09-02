<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('camiones', function (Blueprint $table) {
            $table->bigIncrements('id')->nullable(false);
            
            $table->unsignedBigInteger('empresa_id');                   // ID a tabla empresas que posee el camión
            $table->unsignedBigInteger('conductor_id');                 // ID a tabla conductores que es el conductor por defecto del camión


            $table->unsignedBigInteger('tipo_camion_id');               // ID a tabla list_parameters
            $table->string('patente', 20);
            $table->string('patente_rampla', 20)->nullable();
            $table->boolean('arrendado')->default(false);
            $table->decimal('rendimiento_optimo', 6, 2)->nullable();
            $table->boolean('activo')->default(true);
            $table->text('observacion_inactividad')->nullable();

            // Campos de auditoría
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            // Relaciones referenciales
            $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('restrict');          // Un camión es propiedad de una empresa
            $table->foreign('conductor_id')->references('id')->on('conductores')->onDelete('restrict');     // Un camión tiene un conductor por defecto
            $table->foreign('tipo_camion_id')->references('id')->on('list_parameters')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('camiones');
    }
};
