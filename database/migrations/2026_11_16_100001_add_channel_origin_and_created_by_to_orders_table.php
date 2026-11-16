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
        Schema::table('orders', function (Blueprint $table) {
            $table->string('channel_origin', 32)->default('web')->after('delivery_method');
            $table->foreignId('created_by')->nullable()->after('user_id')->constrained('users')->nullOnDelete();

            $table->index(['channel_origin', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropIndex(['channel_origin', 'status']);
            $table->dropColumn(['channel_origin', 'created_by']);
        });
    }
};
