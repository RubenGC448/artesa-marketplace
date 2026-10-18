<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_shops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            // Estado independiente por tienda dentro del pedido global
            $table->string('status')->default('pending'); // pending, preparing, ready, shipped, delivered, cancelled
            $table->decimal('subtotal', 10, 2);
            $table->decimal('shipping_cost', 10, 2)->default(0.00);
            $table->decimal('total', 10, 2);
            $table->timestamps();

            $table->unique(['order_id', 'shop_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_shops');
    }
};
