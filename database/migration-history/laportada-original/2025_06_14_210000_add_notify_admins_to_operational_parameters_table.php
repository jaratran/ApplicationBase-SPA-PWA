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
            $table->boolean('notify_admins_as_coordinators')->default(false)->after('audit_email_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('operational_parameters', function (Blueprint $table) {
            $table->dropColumn('notify_admins_as_coordinators');
        });
    }
};
