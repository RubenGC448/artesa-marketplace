<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use Illuminate\Support\Facades\Gate;

class ShopController extends Controller
{
    public function show(Shop $shop)
    {
        abort_unless(Gate::allows('view', $shop), 404);

        return view('public.shops.show', [
            'shop' => $shop,
            'products' => $shop->products()->active()->orderBy('title')->get(),
        ]);
    }
}
