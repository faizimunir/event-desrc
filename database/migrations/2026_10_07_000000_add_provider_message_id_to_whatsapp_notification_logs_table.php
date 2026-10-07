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
            if (! Schema::hasColumn(self::TABLE, 'provider_message_id')) {
                $table->string('provider_message_id', 64)->nullable()->after('status');
            }
            if (! Schema::hasColumn(self::TABLE, 'delivery_status')) {
                $table->string('delivery_status', 16)->nullable()->after('provider_message_id');
            }
            if (! Schema::hasColumn(self::TABLE, 'delivered_at')) {
                $table->timestamp('delivered_at')->nullable()->after('sent_at');
            }
            if (! Schema::hasColumn(self::TABLE, 'read_at')) {
                $table->timestamp('read_at')->nullable()->after('delivered_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table(self::TABLE, function (Blueprint $table) {
            $table->dropColumn(['provider_message_id', 'delivery_status', 'delivered_at', 'read_at']);
        });
    }
};
