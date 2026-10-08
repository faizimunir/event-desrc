<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Satu organizer (dan event-eventnya) bisa dikelola oleh banyak user.
 * Menggantikan kolom tunggal organizers.user_id dengan tabel pivot organizer_user.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizer_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organizer_id')->constrained('organizers')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['organizer_id', 'user_id']);
        });

        // Backfill: pindahkan admin organizer yang sudah ada ke pivot.
        $now = now();
        DB::table('organizers')
            ->whereNotNull('user_id')
            ->orderBy('id')
            ->get(['id', 'user_id'])
            ->chunk(500)
            ->each(function ($rows) use ($now) {
                DB::table('organizer_user')->insert(
                    $rows->map(fn ($row) => [
                        'organizer_id' => $row->id,
                        'user_id' => $row->user_id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])->all()
                );
            });

        Schema::table('organizers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('organizers', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
        });

        // Kembalikan user pertama (paling awal ditambahkan) sebagai user_id tunggal.
        $first = DB::table('organizer_user')
            ->select('organizer_id', DB::raw('MIN(user_id) as user_id'))
            ->groupBy('organizer_id')
            ->get();

        foreach ($first as $row) {
            DB::table('organizers')->where('id', $row->organizer_id)->update(['user_id' => $row->user_id]);
        }

        Schema::dropIfExists('organizer_user');
    }
};
