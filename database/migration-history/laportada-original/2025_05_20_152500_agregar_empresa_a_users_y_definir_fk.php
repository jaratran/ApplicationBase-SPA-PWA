<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // 1. Agregar campo empresa y permitir que sucursal sea nullable
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('empresa')->nullable()->before('sucursal');
            $table->unsignedBigInteger('sucursal')->nullable()->change();
        });

        // 2. Obtener IDs válidos de empresas
        $empresas = DB::table('empresas')->pluck('id')->toArray();

        // 3. Alternar empresa y sucursal en los registros
        $usuarios = DB::table('users')->get();
        $contador = 0;

        foreach ($usuarios as $usuario) {
            $usarEmpresa = $contador % 2 === 0;
            $empresaId = $empresas[array_rand($empresas)];

            DB::table('users')
              ->where('id', $usuario->id)
              ->update([
                  'empresa'  => $usarEmpresa ? $empresaId : null,
                  'sucursal' => $usarEmpresa ? null : $usuario->sucursal,
              ]);

            $contador++;
        }

        // 4. Definir FK hacia empresas
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('empresa', 'users_empresa_fk')
                  ->references('id')->on('empresas')
                  ->onDelete('restrict')
                  ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        // 1. Eliminar FK y campo empresa, y revertir nulabilidad de sucursal (si fuera necesario)
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign('users_empresa_fk');
            $table->dropColumn('empresa');
            $table->unsignedBigInteger('sucursal')->nullable(false)->change();
        });
    }
};
