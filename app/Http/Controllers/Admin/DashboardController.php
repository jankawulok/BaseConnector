<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Integration;
use App\Models\Product;
use App\Models\Log;
use App\Models\Category;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $data = [
            'total_integrations' => Integration::count(),
            'total_products' => Product::count(),
            'total_categories' => Category::count(),
            'total_logs' => Log::count(),
            'recent_logs' => Log::with('integration')->latest()->limit(5)->get(),
            'integrations_stats' => DB::table('products')
                ->join('integrations', 'products.integration_id', '=', 'integrations.id')
                ->select('integrations.name', DB::raw('count(*) as count'))
                ->groupBy('integrations.name')
                ->get(),
        ];

        return view('vendor.backpack.ui.dashboard', $data);
    }
}
