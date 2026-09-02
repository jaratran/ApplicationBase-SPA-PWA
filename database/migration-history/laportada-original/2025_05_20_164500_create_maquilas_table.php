<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('maquilas', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('empresa_id');
            $table->unsignedBigInteger('sucursal_id');

            $table->date('fecha_inicio')->nullable();
            $table->boolean('activo')->default(true);
            $table->text('observaciones')->nullable();

            $table->timestamps();

            $table->foreign('empresa_id', 'maquilas_empresa_fk')
                  ->references('id')->on('empresas')
                  ->onDelete('restrict')
                  ->onUpdate('cascade');

            $table->foreign('sucursal_id', 'maquilas_sucursal_fk')
                  ->references('id')->on('sucursales')
                  ->onDelete('restrict')
                  ->onUpdate('cascade');

            $table->unique(['empresa_id', 'sucursal_id'], 'maquilas_empresa_sucursal_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maquilas');
    }
};
