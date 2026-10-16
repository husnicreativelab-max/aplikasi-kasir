<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class PlaceholderController extends Controller
{
    public function service(): View
    {
        return view('placeholders.service');
    }

    public function goodsIn(): View
    {
        return view('placeholders.goods-in');
    }
}
