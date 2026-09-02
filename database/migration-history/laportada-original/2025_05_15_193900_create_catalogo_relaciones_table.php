<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCatalogoRelacionesTable extends Migration
{
    /**
     * Ejecuta la creación de la tabla catalogo_relaciones.
     */
    public function up()
    {
        Schema::create('catalogo_relaciones', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('valor_origen_id');   // ej: BIN
            $table->unsignedBigInteger('valor_destino_id');  // ej: Camión Simple
            $table->unsignedBigInteger('tipo_relacion_id');  // ej: Agrupamiento

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();

            $table->foreign('valor_origen_id')->references('id')->on('catalogos')->onDelete('cascade');
            $table->foreign('valor_destino_id')->references('id')->on('catalogos')->onDelete('cascade');
            $table->foreign('tipo_relacion_id')->references('id')->on('catalogos')->onDelete('restrict');
        });
    }

    /**
     * Reversión de la migración.
     */
    public function down()
    {
        Schema::dropIfExists('catalogo_relaciones');
    }
}
