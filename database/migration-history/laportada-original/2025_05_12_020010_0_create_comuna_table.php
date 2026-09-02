<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comuna', function (Blueprint $table) {
            $table->bigIncrements('id')->nullable(false);

            $table->string('nombre_comuna', 255)->nullable(false);
            $table->bigInteger('region_comuna')->nullable(false);

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comuna');
    }
};
