<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;

class ShopVerificationController extends Controller
{
    public function index()
    {
        return view('admin.shops.pending', [
            'shops' => Shop::pendingVerification()->with('user')->latest()->get(),
            'verified' => Shop::verified()->with('user')->orderBy('name')->get(),
        ]);
    }

    /**
     * Verificación de un taller: se invoca desde código el comando
     * artesa:verify-shop (la misma operación está disponible en consola).
     */
    public function verify(Shop $shop)
    {
        Gate::authorize('verify', $shop);

        Artisan::call('artesa:verify-shop', ['shop' => $shop->id]);

        return back()->with('status', __('Taller «:name» verificado.', ['name' => $shop->name]));
    }

    public function revoke(Shop $shop)
    {
        Gate::authorize('verify', $shop);

        Artisan::call('artesa:verify-shop', ['shop' => $shop->id, '--revoke' => true]);

        return back()->with('status', __('Verificación retirada a «:name».', ['name' => $shop->name]));
    }
}
