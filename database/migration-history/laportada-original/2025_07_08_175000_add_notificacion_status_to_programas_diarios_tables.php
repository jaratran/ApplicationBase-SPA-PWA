<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('programas_diarios', function (Blueprint $table) {
            $table->tinyInteger('estado_notif_correo')->default(0)->after('estado');
            $table->dateTime('inicio_notif_correo')->nullable()->after('estado_notif_correo');
            $table->dateTime('fin_notif_correo')->nullable()->after('inicio_notif_correo');
            
            $table->tinyInteger('estado_notif_telegram')->default(0)->after('fin_notif_correo');
            $table->dateTime('inicio_notif_telegram')->nullable()->after('estado_notif_telegram');
            $table->dateTime('fin_notif_telegram')->nullable()->after('inicio_notif_telegram');
        });

        Schema::table('programas_diarios_detalle', function (Blueprint $table) {
            $table->tinyInteger('estado_notif_correo')->default(0)->after('bins');
            $table->dateTime('fecha_envio_correo')->nullable()->after('estado_notif_correo');
            
            $table->tinyInteger('estado_notif_telegram')->default(0)->after('fecha_envio_correo');
            $table->dateTime('fecha_envio_telegram')->nullable()->after('estado_notif_telegram');
        });
    }

    public function down(): void
    {
        Schema::table('programas_diarios', function (Blueprint $table) {
            $table->dropColumn([
                'estado_notif_correo',
                'inicio_notif_correo',
                'fin_notif_correo',
                'estado_notif_telegram',
                'inicio_notif_telegram',
                'fin_notif_telegram',
            ]);
        });

        Schema::table('programas_diarios_detalle', function (Blueprint $table) {
            $table->dropColumn([
                'estado_notif_correo',
                'fecha_envio_correo',
                'estado_notif_telegram',
                'fecha_envio_telegram',
            ]);
        });
    }
};
