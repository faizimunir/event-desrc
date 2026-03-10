<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_id')->constrained()->cascadeOnDelete();
            $table->string('session_id', 255)->nullable()->index()->comment('Guest: pengait sementara via session');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete()->comment('Logged-in: pengait tambahan');
            $table->timestamps();

            $table->unique('registration_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
