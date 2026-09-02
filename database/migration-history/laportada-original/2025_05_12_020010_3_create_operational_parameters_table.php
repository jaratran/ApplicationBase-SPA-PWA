<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operational_parameters', function (Blueprint $table) {
            $table->bigIncrements('id')->nullable(false);
            
            // Email de contacto de soporte IT
            $table->string('support_email', 255)->nullable();
    
            // Hora de ejecución del programa diario
            $table->time('daily_program_execution_time')->nullable();
    
            // Indica si se necesita emisión automática del programa diario
            $table->boolean('auto_emit_daily_program')->default(false);
    
            // Casilla donde se enviará copia de todos los correos para auditoría
            $table->string('audit_email', 255)->nullable();
    
            // Indica si está activa la funcionalidad de envío de copia de correos
            $table->boolean('audit_email_enabled')->default(false);
    
            // Indica si los usuarios pueden modificar sus datos de perfil
            $table->boolean('allow_profile_editing')->default(true);

            // Indica la velocidad promedio de los camiones
            $table->integer('average_truck_speed')->nullable();

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operational_parameters');
    }
};
