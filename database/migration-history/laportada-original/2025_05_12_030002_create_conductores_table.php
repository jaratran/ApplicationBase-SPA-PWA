<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('conductores', function (Blueprint $table) {
            $table->bigIncrements('id')->nullable(false);

            $table->unsignedBigInteger('empresa_id');                   // ID a tabla empresas que emplea al conductor

            $table->string('rut', 20);
            $table->string('nombre');
            $table->string('apellido');
            $table->string('telefono')->nullable();
            $table->boolean('activo')->default(true);
            $table->text('observacion_inactividad')->nullable();

            // Campos de auditoría
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            // Relaciones referenciales
            $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('restrict'); // Un conductor es empleado por una empresa
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conductores');
    }
};
