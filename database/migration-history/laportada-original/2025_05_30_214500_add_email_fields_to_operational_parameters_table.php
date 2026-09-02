<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('operational_parameters', function (Blueprint $table) {
            $table->string('support_telefono')->nullable()->after('support_email');
            $table->integer('verification_expiration_time')->nullable()->after('audit_email_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('operational_parameters', function (Blueprint $table) {
            $table->dropColumn('support_telefono');
            $table->dropColumn('verification_expiration_time');
        });
    }
};
