<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_of_ceremonies', function (Blueprint $table) {
            if (! Schema::hasColumn('master_of_ceremonies', 'avatar_mc')) {
                $table->string('avatar_mc')->nullable()->after('link');
            }
        });
    }

    public function down(): void
    {
        Schema::table('master_of_ceremonies', function (Blueprint $table) {
            if (Schema::hasColumn('master_of_ceremonies', 'avatar_mc')) {
                $table->dropColumn('avatar_mc');
            }
        });
    }
};
