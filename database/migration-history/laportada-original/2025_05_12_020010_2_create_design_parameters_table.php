<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('design_parameters', function (Blueprint $table) {
            $table->bigIncrements('id')->nullable(false);

            // Titulo de la aplicación
            $table->string('titulo_design', 255)->nullable();

            // Imagenes de logo, emblema, favicon y fondo de pantalla
            $table->string('logo_design', 255)->nullable(false);
            $table->string('emblema_design', 255)->nullable(false);
            $table->string('favicon_design', 255)->nullable(false);
            $table->string('fondo_pantalla_design', 255)->nullable(false);

            // Personalización de colores de la aplicación
            $table->string('custom_primary')->collation('utf8_unicode_ci')->nullable(false);
            $table->string('custom_secondary')->collation('utf8_unicode_ci')->nullable(false);
            $table->string('custom_success')->collation('utf8_unicode_ci')->nullable(false);
            $table->string('custom_warning')->collation('utf8_unicode_ci')->nullable(false);
            $table->string('custom_danger')->collation('utf8_unicode_ci')->nullable(false);
            $table->string('custom_info')->collation('utf8_unicode_ci')->nullable(false);

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('design_parameters');
    }
};
