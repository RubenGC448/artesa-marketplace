<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->restrictOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('base_price', 10, 2);
            $table->unsignedInteger('stock')->default(0); // solo si el producto no tiene variantes
            $table->decimal('tax_rate', 5, 2)->default(21.00); // tipo de IVA propio del producto
            $table->date('available_from')->nullable(); // piezas bajo encargo / próxima hornada
            $table->string('sku')->unique()->nullable();
            $table->string('status')->default('draft'); // draft, active, inactive (active = publicado en el catálogo)
            $table->boolean('featured')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
