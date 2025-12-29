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
        Schema::table('admins', function (Blueprint $table) {
            // Update enum to include co_admin_event
            $table->enum('role', ['super_admin', 'admin_event', 'co_admin_event'])->default('admin_event')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            // Revert to original enum values
            $table->enum('role', ['super_admin', 'admin_event'])->default('admin_event')->change();
        });
    }
};
