<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('retiros', function (Blueprint $table) {
            $table->unsignedBigInteger('estado_id')->nullable()->after('cantidad_bins');
        });

        // Copiamos los valores antiguos de 'estado' al nuevo campo 'estado_id'
        DB::statement('UPDATE retiros SET estado_id = estado');

        Schema::table('retiros', function (Blueprint $table) {
            $table->dropColumn('estado');
            $table->foreign('estado_id')->references('id')->on('catalogos')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('retiros', function (Blueprint $table) {
            $table->tinyInteger('estado')->default(0)->after('cantidad_bins');
        });

        // Restauramos los valores desde 'estado_id'
        DB::statement('UPDATE retiros SET estado = estado_id');

        Schema::table('retiros', function (Blueprint $table) {
            $table->dropForeign(['estado_id']);
            $table->dropColumn('estado_id');
        });
    }
};
