<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('events', 'payment_methods')) {
            Schema::table('events', function (Blueprint $table) {
                $table->json('payment_methods')->nullable()->after('location_id');
            });
        }

        if (! Schema::hasTable('event_account')) {
            Schema::create('event_account', function (Blueprint $table) {
                $table->foreignId('event_id')->constrained()->cascadeOnDelete();
                $table->foreignId('account_id')->constrained()->cascadeOnDelete();
                $table->primary(['event_id', 'account_id']);
            });
        }

        if (Schema::hasColumn('events', 'account_id')) {
            foreach (DB::table('events')->whereNotNull('account_id')->cursor() as $row) {
                DB::table('event_account')->insertOrIgnore([
                    'event_id' => $row->id,
                    'account_id' => $row->account_id,
                ]);
            }

            DB::table('events')->update([
                'payment_methods' => json_encode(['manual', 'qris']),
            ]);

            Schema::table('events', function (Blueprint $table) {
                $table->dropForeign(['account_id']);
                $table->dropColumn('account_id');
            });
        }

        if (! Schema::hasColumn('payments', 'method')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->string('method', 32)->default('manual')->after('amount');
            });
        }

        if (! Schema::hasColumn('payments', 'manual_account_id')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->foreignId('manual_account_id')->nullable()->after('method')->constrained('accounts')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'manual_account_id')) {
                $table->dropForeign(['manual_account_id']);
                $table->dropColumn('manual_account_id');
            }
        });

        Schema::table('events', function (Blueprint $table) {
            if (! Schema::hasColumn('events', 'account_id')) {
                $table->foreignId('account_id')->nullable()->after('location_id')->constrained('accounts')->nullOnDelete();
            }
        });

        if (Schema::hasTable('event_account')) {
            foreach (DB::table('event_account')->orderBy('event_id')->orderBy('account_id')->cursor() as $row) {
                $exists = DB::table('events')->where('id', $row->event_id)->whereNotNull('account_id')->exists();
                if (! $exists) {
                    DB::table('events')->where('id', $row->event_id)->update(['account_id' => $row->account_id]);
                }
            }

            Schema::dropIfExists('event_account');
        }

        Schema::table('events', function (Blueprint $table) {
            if (Schema::hasColumn('events', 'payment_methods')) {
                $table->dropColumn('payment_methods');
            }
        });
    }
};
