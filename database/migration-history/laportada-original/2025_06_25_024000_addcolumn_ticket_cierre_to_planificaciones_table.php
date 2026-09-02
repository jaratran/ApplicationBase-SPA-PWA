<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('planificaciones', function (Blueprint $table) {
            $table->string('ticket_cierre', 20)->nullable()->after('estado_id');
        });
    }

    public function down(): void
    {
        Schema::table('planificaciones', function (Blueprint $table) {
            $table->dropColumn('ticket_cierre');
        });
    }
};
