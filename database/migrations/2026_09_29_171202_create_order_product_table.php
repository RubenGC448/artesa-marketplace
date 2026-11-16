<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_shop_id')->constrained('order_shops')->cascadeOnDelete();
            // restrictOnDelete: el catálogo no puede borrar físicamente líneas vendidas
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->restrictOnDelete();
            // Nombre de la pieza y de la variante congelados (para albaranes y facturas)
            $table->string('product_title')->nullable();
            $table->string('variant_name')->nullable();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 10, 2);       // Precio congelado al momento de compra
            $table->decimal('applied_tax', 5, 2)->default(0.00);
            $table->decimal('artisan_commission_rate', 5, 2)->default(0.00);
            $table->string('line_status')->default('pending'); // pending, preparing, ready, cancelled
            $table->text('customization_notes')->nullable();
            $table->timestamps();

            // PK propia (id): dos variantes del mismo producto en un pedido son dos líneas
            // distintas y no se pisan. Índice para consultas por subpedido y producto.
            $table->index(['order_shop_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_product');
    }
};
