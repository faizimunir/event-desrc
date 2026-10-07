<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE = 'whatsapp_notification_logs';

    public function up(): void
    {
        Schema::table(self::TABLE, function (Blueprint $table) {
            if (! Schema::hasColumn(self::TABLE, 'scheduled_at')) {
                $table->timestamp('scheduled_at')->nullable()->after('failed_reason');
            }
            if (! Schema::hasColumn(self::TABLE, 'expires_at')) {
                $table->timestamp('expires_at')->nullable()->after('scheduled_at');
            }
        });

        Schema::table(self::TABLE, function (Blueprint $table) {
            $table->index(['status', 'expires_at'], 'wa_logs_status_expires_idx');
        });
    }

    public function down(): void
    {
        Schema::table(self::TABLE, function (Blueprint $table) {
            $table->dropIndex('wa_logs_status_expires_idx');
        });

        Schema::table(self::TABLE, function (Blueprint $table) {
            $table->dropColumn(['scheduled_at', 'expires_at']);
        });
    }
};
