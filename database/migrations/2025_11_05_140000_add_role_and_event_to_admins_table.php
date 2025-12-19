<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->enum('role', ['super_admin', 'admin_event'])->default('admin_event')->after('status');
            $table->foreignId('event_id')->nullable()->after('role')->constrained('events')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->dropForeign(['event_id']);
            $table->dropColumn(['role', 'event_id']);
        });
    }
};

