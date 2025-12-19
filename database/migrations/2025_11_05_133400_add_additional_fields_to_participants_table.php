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
        Schema::table('participants', function (Blueprint $table) {
            $table->string('nickname')->nullable()->after('name');
            $table->string('number_plate')->nullable()->after('nickname');
            $table->string('komunitas')->nullable()->after('number_plate');
            $table->string('unique_code', 3)->nullable()->after('registration_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('participants', function (Blueprint $table) {
            $table->dropColumn(['nickname', 'number_plate', 'komunitas', 'unique_code']);
        });
    }
};
