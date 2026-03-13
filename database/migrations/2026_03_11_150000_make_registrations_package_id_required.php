<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Set null package_id to first package of the event (for existing data)
        $registrationsWithNullPackage = DB::table('registrations')->whereNull('package_id')->pluck('event_id', 'id');
        $eventIds = $registrationsWithNullPackage->values()->unique();
        $firstPackageByEvent = DB::table('event_packages')
            ->whereIn('event_id', $eventIds)
            ->orderBy('id')
            ->get()
            ->groupBy('event_id')
            ->map(fn ($row) => $row->first()->id);

        foreach ($registrationsWithNullPackage as $regId => $eventId) {
            $firstPackageId = $firstPackageByEvent->get($eventId);
            if ($firstPackageId !== null) {
                DB::table('registrations')->where('id', $regId)->update(['package_id' => $firstPackageId]);
            }
        }

        // Remove any remaining registrations with null package_id (event has no packages)
        DB::table('registrations')->whereNull('package_id')->delete();

        Schema::table('registrations', function (Blueprint $table) {
            // Drop existing FK (was ON DELETE SET NULL)
            $table->dropForeign(['package_id']);
        });

        Schema::table('registrations', function (Blueprint $table) {
            // Make column NOT NULL
            $table->unsignedBigInteger('package_id')->nullable(false)->change();

            // Re-add FK without SET NULL (use CASCADE or RESTRICT as needed)
            $table->foreign('package_id')
                ->references('id')
                ->on('event_packages')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            // Drop current FK
            $table->dropForeign(['package_id']);
        });

        Schema::table('registrations', function (Blueprint $table) {
            // Make column nullable again
            $table->unsignedBigInteger('package_id')->nullable()->change();

            // Restore original FK behavior: ON DELETE SET NULL
            $table->foreign('package_id')
                ->references('id')
                ->on('event_packages')
                ->nullOnDelete();
        });
    }
};
