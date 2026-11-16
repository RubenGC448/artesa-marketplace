<?php

namespace App\Http\Controllers\Artisan;

use App\Http\Controllers\Controller;
use App\Http\Requests\Artisan\StoreProductRequest;
use App\Http\Requests\Artisan\UpdateProductRequest;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

/**
 * CRUD del catálogo privado del taller. Cada artesano solo ve y toca
 * los productos de su taller (consulta acotada + ProductPolicy).
 */
class ProductController extends Controller
{
    public function index(Request $request)
    {
        $shop = $request->user()->shop;

        return view('artisan.products.index', [
            'shop' => $shop,
            'products' => $shop
                ? $shop->products()->with(['category', 'variants'])->orderByDesc('updated_at')->paginate(15)
                : collect(),
        ]);
    }

    public function create()
    {
        Gate::authorize('create', Product::class);

        return view('artisan.products.create', $this->formData(new Product([
            'status' => Product::STATUS_DRAFT,
            'tax_rate' => config('artesa.default_tax_rate'),
            'stock' => 1,
        ])));
    }

    public function store(StoreProductRequest $request)
    {
        $data = $request->validated();

        $product = $request->user()->shop->products()->create($data + [
            'slug' => $this->uniqueSlug($data['title']),
        ]);

        return redirect()->route('artisan.products.index')
            ->with('status', __('Producto «:title» creado.', ['title' => $product->title]));
    }

    public function edit(Product $product)
    {
        Gate::authorize('update', $product);

        return view('artisan.products.edit', $this->formData($product));
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $product->update($request->validated());

        return redirect()->route('artisan.products.index')
            ->with('status', __('Producto «:title» actualizado.', ['title' => $product->title]));
    }

    public function destroy(Product $product)
    {
        Gate::authorize('delete', $product);

        $product->delete(); // borrado lógico: las líneas de pedido históricas se conservan

        return redirect()->route('artisan.products.index')
            ->with('status', __('Producto «:title» eliminado.', ['title' => $product->title]));
    }

    private function formData(Product $product): array
    {
        return [
            'product' => $product,
            'categories' => Category::orderBy('name')->pluck('name', 'id')->all(),
            'statuses' => [
                Product::STATUS_DRAFT => __('Borrador'),
                Product::STATUS_ACTIVE => __('Publicado'),
                Product::STATUS_INACTIVE => __('Retirado'),
            ],
            'taxRates' => config('artesa.tax_rates'),
        ];
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $i = 2;

        while (Product::withTrashed()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
