<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_brackets', function (Blueprint $table) {
            $table->boolean('hide_quota')->default(false)->after('quota');
        });
    }

    public function down(): void
    {
        Schema::table('event_brackets', function (Blueprint $table) {
            $table->dropColumn('hide_quota');
        });
    }
};
