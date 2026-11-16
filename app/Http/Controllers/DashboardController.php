<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Tras iniciar sesión, cada usuario va al panel de su rol.
 */
class DashboardController extends Controller
{
    private const ROLE_HOME = [
        'admin' => 'admin.dashboard',
        'moderator' => 'admin.dashboard',
        'artisan' => 'artisan.dashboard',
        'courier' => 'courier.dashboard',
        'customer' => 'customer.dashboard',
    ];

    public function __invoke(Request $request)
    {
        foreach (self::ROLE_HOME as $role => $route) {
            if ($request->user()->hasRole($role)) {
                return redirect()->route($route);
            }
        }

        return view('dashboard');
    }
}
