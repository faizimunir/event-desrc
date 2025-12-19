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
        Schema::table('packages', function (Blueprint $table) {
            // Drop the foreign key constraint first
            $table->dropForeign(['category_id']);
        });
        
        Schema::table('packages', function (Blueprint $table) {
            // Make category_id nullable using raw SQL for better compatibility
            $table->unsignedBigInteger('category_id')->nullable()->change();
        });
        
        Schema::table('packages', function (Blueprint $table) {
            // Re-add the foreign key constraint with nullable
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            // Drop the foreign key constraint
            $table->dropForeign(['category_id']);
        });
        
        Schema::table('packages', function (Blueprint $table) {
            // Make category_id NOT NULL again
            $table->unsignedBigInteger('category_id')->nullable(false)->change();
        });
        
        Schema::table('packages', function (Blueprint $table) {
            // Re-add the foreign key constraint
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
        });
    }
};
