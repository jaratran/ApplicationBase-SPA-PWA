<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalogos', function (Blueprint $table) {
            $table->bigIncrements('id')->nullable(false);

            // Autorreferencia: cada valor puede pertenecer a una categoría/lista definida en esta misma tabla
            $table->unsignedBigInteger('catalogo_id')->nullable()->comment('Referencia al catálogo padre');

            $table->string('nombre', 255)->nullable(false);
            $table->integer('orden')->default(0);
            $table->boolean('activo')->default(true);

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();

            // Clave foránea a sí misma
            $table->foreign('catalogo_id')->references('id')->on('catalogos')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalogos');
    }
};
