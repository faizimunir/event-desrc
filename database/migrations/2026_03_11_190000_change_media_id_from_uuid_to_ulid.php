<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Change media.id from uuid to string(36).
     * Keeps compatibility with existing UUID (36 chars); new records use ULID (26 chars).
     */
    public function up(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->dropPrimary(['id']);
        });
        Schema::table('media', function (Blueprint $table) {
            $table->string('id', 36)->comment('ULID (26) or legacy UUID (36)')->change();
        });
        Schema::table('media', function (Blueprint $table) {
            $table->primary('id');
        });
    }

    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->dropPrimary(['id']);
        });
        Schema::table('media', function (Blueprint $table) {
            $table->uuid('id')->change();
        });
        Schema::table('media', function (Blueprint $table) {
            $table->primary('id');
        });
    }
};
