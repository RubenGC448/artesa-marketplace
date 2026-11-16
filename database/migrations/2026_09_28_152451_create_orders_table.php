<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            // restrictOnDelete: borrar (físicamente) un cliente no puede eliminar sus ventas.
            // El borrado normal de usuarios es lógico (SoftDeletes).
            $table->foreignId('customer_id')->constrained('users')->restrictOnDelete();
            // address_id: solo referencia a la libreta del cliente. Si la dirección se edita
            // o se borra, el pedido conserva la copia congelada de las columnas shipping_*.
            $table->foreignId('address_id')->nullable()->constrained('addresses')->nullOnDelete();
            // Copia congelada de los datos de envío usados en la venta
            $table->string('shipping_name')->nullable();
            $table->string('shipping_phone')->nullable();
            $table->string('shipping_street')->nullable();
            $table->string('shipping_city')->nullable();
            $table->string('shipping_province')->nullable();
            $table->string('shipping_postal_code', 10)->nullable();
            $table->string('shipping_country', 2)->nullable();
            $table->string('status')->default('pending'); // pending, paid, preparing, shipped, delivered, cancelled
            $table->decimal('subtotal', 10, 2);
            $table->decimal('shipping_cost', 10, 2)->default(0.00);
            $table->decimal('total', 10, 2);
            $table->text('notes')->nullable(); // instrucciones de entrega / empaquetado
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
