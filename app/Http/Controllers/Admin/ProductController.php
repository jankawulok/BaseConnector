<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;

class ProductController extends Controller
{
    public function show($productAutoId)
    {
        $product = Product::findOrFail($productAutoId);
        return response()->json($product);
    }
}
