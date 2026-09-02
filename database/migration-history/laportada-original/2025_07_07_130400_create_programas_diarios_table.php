<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends \Illuminate\Database\Migrations\Migration {
    public function up(): void
    {
        Schema::create('programas_diarios', function (Blueprint $table) {
            $table->bigIncrements('id')->nullable(false);
            $table->date('fecha_programa')->nullable(false);
            $table->dateTime('fecha_emision')->nullable(false);
            $table->unsignedInteger('version')->default(0);
            $table->unsignedBigInteger('usuario_id')->nullable(false);

            $table->tinyInteger('estado')->default(0); // 0=emitido, opcional expandir a más estados

            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->foreign('usuario_id')->references('id')->on('users')->onUpdate('cascade');

            $table->unique(['fecha_programa', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('programas_diarios');
    }
};
