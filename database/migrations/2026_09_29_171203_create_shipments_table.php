<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            // Cada envío pertenece a la parte de una tienda dentro del pedido
            $table->foreignId('order_shop_id')->constrained('order_shops')->cascadeOnDelete();
            $table->string('carrier')->nullable();           // MRW, SEUR, local, etc.
            $table->string('tracking_number')->nullable();
            $table->string('status')->default('pending');   // pending, in_transit, delivered, failed
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
