<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sucursales', function (Blueprint $table) {
            $table->bigIncrements('id')->nullable(false);

            $table->unsignedBigInteger('zona_id');                     // ID a tabla list_parameters con Tipo = 501
            $table->string('nombre_sucursal');
            $table->integer('codigo_siep')->nullable();
            $table->unsignedBigInteger('tipo_sucursal_id');            // ID a tabla list_parameters con Tipo = 502
            $table->unsignedBigInteger('comuna_id');                   // ID a tabla comunas
            $table->unsignedBigInteger('ciudad_id');                   // ID a tabla list_parameters con Tipo = 503
            $table->string('telefono')->nullable();
            $table->string('email')->nullable();
            $table->integer('km')->nullable();
            $table->decimal('tiempo_estimado_viaje', 5, 2)->nullable();
            $table->boolean('activo')->default(true);
            $table->text('observacion_inactividad')->nullable();

            // Campos de auditoría
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            // Relaciones (asumiendo claves foráneas referenciales)
            $table->foreign('zona_id')->references('id')->on('list_parameters')->onDelete('restrict'); // Una sucursal pertenece a una zona
            $table->foreign('tipo_sucursal_id')->references('id')->on('list_parameters')->onDelete('restrict'); // Una sucursal tiene un tipo
            $table->foreign('comuna_id')->references('id')->on('comuna')->onDelete('restrict'); // Una sucursal pertenece a una comuna
            $table->foreign('ciudad_id')->references('id')->on('list_parameters')->onDelete('restrict'); // Una sucursal pertenece a una ciudad
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sucursales');
    }
};
