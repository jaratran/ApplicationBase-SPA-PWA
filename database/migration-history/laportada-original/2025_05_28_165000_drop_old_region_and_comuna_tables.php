<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::dropIfExists('comuna');
        Schema::dropIfExists('region');
    }

    public function down(): void
    {
        Schema::create('region', function (Blueprint $table) {
            $table->bigIncrements('id')->nullable(false);
            $table->string('nombre_region');
            $table->unsignedBigInteger('orden_region');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('comuna', function (Blueprint $table) {
            $table->bigIncrements('id')->nullable(false);
            $table->string('nombre_comuna');
            $table->unsignedBigInteger('region_comuna');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('region_comuna', 'comuna_region_fk')
                  ->references('id')->on('region')
                  ->onUpdate('cascade');
        });
    }
};
