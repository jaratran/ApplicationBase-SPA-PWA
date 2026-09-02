<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('empresas', function (Blueprint $table) {
            $table->bigIncrements('id')->nullable(false);

            $table->unsignedBigInteger('sucursal_id');                 // ID a tabla sucursales que atiende a la empresa
            $table->unsignedBigInteger('tipo_empresa_id');             // ID a tabla list_parameters
            $table->string('rut_empresa', 20);
            $table->string('razon_social');
            $table->string('direccion');
            $table->unsignedBigInteger('comuna_id');                   // ID a tabla comunas
            $table->string('telefono')->nullable();
            $table->string('email_contacto')->nullable();
            $table->string('telefono_contacto')->nullable();
            $table->boolean('activo')->default(true);
            $table->text('observacion_inactividad')->nullable();

            // Campos de auditoría
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            // Relaciones (asumiendo claves foráneas referenciales)
            $table->foreign('sucursal_id')->references('id')->on('sucursales')->onDelete('restrict');               // Una empresa es atendida por una sucursal
            $table->foreign('tipo_empresa_id')->references('id')->on('list_parameters')->onDelete('restrict');
            $table->foreign('comuna_id')->references('id')->on('comuna')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empresas');
    }
};
