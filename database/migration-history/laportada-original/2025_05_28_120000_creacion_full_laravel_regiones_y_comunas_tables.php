<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Crear tabla regiones
        Schema::create('regiones', function (Blueprint $table) {
            $table->bigIncrements('id')->nullable(false);
            $table->string('nombre');
            $table->bigInteger('orden');
            $table->timestamps();
        });

        // Insertar datos desde 'region'
        DB::statement("
            INSERT INTO regiones (id, nombre, orden, created_at, updated_at)
            SELECT id, nombre_region, orden_region, created_at, updated_at FROM region
        ");

        // Crear tabla comunas
        Schema::create('comunas', function (Blueprint $table) {
            $table->bigIncrements('id')->nullable(false);
            $table->string('nombre');
            $table->unsignedBigInteger('region_id');
            $table->timestamps();

            $table->foreign('region_id')->references('id')->on('regiones')->onUpdate('cascade');
        });

        // Insertar datos desde 'comuna'
        DB::statement("
            INSERT INTO comunas (id, nombre, region_id, created_at, updated_at)
            SELECT id, nombre_comuna, region_comuna, created_at, updated_at FROM comuna
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('comunas');
        Schema::dropIfExists('regiones');
    }
};
