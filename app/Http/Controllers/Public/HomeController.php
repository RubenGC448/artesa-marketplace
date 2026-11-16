<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Shop;

class HomeController extends Controller
{
    public function __invoke()
    {
        return view('public.home', [
            'featured' => Product::inCatalog()->where('featured', true)->with('shop')->latest()->take(8)->get(),
            'topRated' => Product::inCatalog()->topRated(1, 4)->with('shop')->take(4)->get(),
            'shops' => Shop::verified()->withCount(['products' => fn ($q) => $q->active()])->take(6)->get(),
        ]);
    }
}
