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
        Schema::table('payments', function (Blueprint $table) {
            $table->string('gateway', 32)->default('mock')->after('method');
            $table->string('gateway_mode', 16)->default('mock')->after('gateway');
            $table->string('external_transaction_id', 100)->nullable()->after('reference')->index();
            $table->string('idempotency_key', 100)->nullable()->after('external_transaction_id')->index();
            $table->string('currency', 3)->default('USD')->after('amount');
            $table->json('raw_response')->nullable()->after('processed_at');
            $table->text('error_message')->nullable()->after('raw_response');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['external_transaction_id']);
            $table->dropIndex(['idempotency_key']);
            $table->dropColumn([
                'gateway',
                'gateway_mode',
                'external_transaction_id',
                'idempotency_key',
                'currency',
                'raw_response',
                'error_message',
            ]);
        });
    }
};
