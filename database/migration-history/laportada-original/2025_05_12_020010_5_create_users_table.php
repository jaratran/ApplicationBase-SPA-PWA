<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id')->nullable(false);

            $table->string('rut_usuario', 255)->nullable(false);
            $table->string('nombre_usuario', 255)->nullable(false);
            $table->string('apellidos_usuario', 255)->nullable(false);

            $table->bigInteger('sucursal')->nullable(false);
            $table->bigInteger('rol_usuario')->nullable(false);

            $table->text('telefono_usuario')->nullable();
            $table->string('email', 255)->nullable(false);
            $table->timestamp('email_verified_at')->nullable();

            $table->string('avatar', 255)->nullable();

            $table->bigInteger('comuna_id')->nullable(false);
            $table->string('direccion', 255)->nullable();

            $table->bigInteger('vigencia')->nullable(false);
            $table->bigInteger('esAdmin')->nullable(false);

            $table->string('fechaLogin', 255)->nullable();
            $table->string('remember_token', 255)->nullable();
            $table->string('password', 255)->nullable();

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
