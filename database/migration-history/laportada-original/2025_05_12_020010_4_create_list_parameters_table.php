<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('list_parameters', function (Blueprint $table) {
            $table->bigIncrements('ID')->nullable(false);

            $table->bigInteger('Tipo')->nullable(false);
            $table->string('Nombre', 255)->nullable();

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('list_parameters');
    }
};
