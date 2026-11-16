<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CatalogController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'integer', 'exists:categories,id'],
        ]);

        $products = Product::inCatalog()
            ->search($filters['q'] ?? null)
            ->when($filters['category'] ?? null, function ($query, $categoryId) {
                $query->where(function ($q) use ($categoryId) {
                    $q->where('category_id', $categoryId)
                        ->orWhereHas('category', fn ($c) => $c->where('parent_id', $categoryId));
                });
            })
            ->with('shop')
            ->orderBy('title')
            ->paginate(12)
            ->withQueryString();

        return view('public.catalog.index', [
            'products' => $products,
            'categories' => Category::whereNull('parent_id')->orderBy('name')->pluck('name', 'id')->all(),
            'filters' => $filters,
        ]);
    }

    public function show(Product $product)
    {
        // Un borrador o un producto de un taller sin verificar no existe para el público
        abort_unless(Gate::allows('view', $product), 404);

        $product->load(['shop', 'category', 'variants', 'tags', 'reviews' => fn ($q) => $q->where('is_approved', true)->with('customer')]);

        return view('public.catalog.show', compact('product'));
    }
}
