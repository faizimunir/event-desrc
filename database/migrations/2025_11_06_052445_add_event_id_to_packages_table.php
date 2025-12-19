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
        Schema::table('packages', function (Blueprint $table) {
            // Add event_id column
            $table->foreignId('event_id')->nullable()->after('id')->constrained('events')->onDelete('cascade');
            
            // Update existing packages to have event_id from their category
            // This will be done via raw SQL after migration
        });
        
        // Update existing packages to set event_id from category
        DB::statement('
            UPDATE packages 
            SET event_id = (
                SELECT categories.event_id 
                FROM categories 
                WHERE categories.id = packages.category_id
            )
        ');
        
        // Make event_id required after data migration
        Schema::table('packages', function (Blueprint $table) {
            $table->foreignId('event_id')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropForeign(['event_id']);
            $table->dropColumn('event_id');
        });
    }
};
