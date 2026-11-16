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
        Schema::create('return_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number', 50)->unique();
            $table->foreignId('order_id')->constrained('orders')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('order_item_id')->constrained('order_items')->restrictOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->string('size', 20)->nullable();
            
            // Motivo y requerimiento del cliente
            $table->string('reason', 150);
            $table->text('problem_description');
            $table->string('requested_solution', 50);
            
            // Estados: recibida -> en_revision -> resuelta / rechazada
            $table->string('status', 32)->default('recibida');
            
            // Peritaje técnico e inspección física
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('inspected_condition', 50)->default('pendiente');
            
            // Solución de cambio
            $table->foreignId('replacement_product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('replacement_size', 20)->nullable();
            $table->text('admin_decision')->nullable();
            
            // Control de inventario
            $table->boolean('is_restocked')->default(false);
            $table->timestamp('restocked_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['order_id', 'order_item_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('return_requests');
    }
};
