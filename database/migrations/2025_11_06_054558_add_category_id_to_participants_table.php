<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('participants', function (Blueprint $table) {
            // Add category_id column
            $table->foreignId('category_id')->nullable()->after('package_id')->constrained('categories')->onDelete('cascade');
        });
        
        // Update existing participants to set category_id from their package
        DB::statement('
            UPDATE participants 
            SET category_id = (
                SELECT packages.category_id 
                FROM packages 
                WHERE packages.id = participants.package_id
                LIMIT 1
            )
        ');
        
        // Make category_id required after data migration
        Schema::table('participants', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('participants', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
        });
    }
};
