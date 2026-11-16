<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dispute;

class DisputeController extends Controller
{
    public function index()
    {
        return view('admin.disputes.index', [
            'disputes' => Dispute::with(['order', 'shop', 'customer'])->latest()->get(),
        ]);
    }
}
