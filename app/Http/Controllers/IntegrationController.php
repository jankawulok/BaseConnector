<?php

namespace App\Http\Controllers;

use App\Models\Integration;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;

class IntegrationController extends Controller
{
  /**
   * Handle all requests based on 'action' and validate 'bl_pass'.
   */
  public function handleRequest($integrationId, Request $request)
  {
    // Validate 'bl_pass' against integration's API key
    $integration = Integration::findOrFail($integrationId);
    if ($request->input('bl_pass') !== $integration->api_key) {
      return response()->json([
        'error' => true,
        'error_code' => 'invalid_bl_pass',
        'error_text' => 'Invalid API key'
      ], 403);
    }

    // Determine action and call the corresponding method
    $action = $request->input('action');
    switch ($action) {
      case 'SupportedMethods':
        return $this->supportedMethods();
      case 'FileVersion':
        return $this->fileVersion();
      case 'ProductsCategories':
        return $this->productsCategories($integrationId);
      case 'ProductsList':
        return $this->productsList($integrationId, $request);
      case 'ProductsData':
        return $this->productsData($integrationId, $request);
      case 'ProductsPrices':
        return $this->productsPrices($integrationId, $request);
      case 'ProductsQuantity':
        return $this->productsQuantity($integrationId, $request);
      default:
        return response()->json([
          'error' => true,
          'error_code' => 'invalid_action',
          'error_text' => 'Invalid action'
        ], 400);
    }
  }

  /**
   * List of all supported methods.
   */
  private function supportedMethods()
  {
    return response()->json([
      'methods' => [
        'SupportedMethods',
        'FileVersion',
        'ProductsCategories',
        'ProductsList',
        'ProductsData',
        'ProductsPrices',
        'ProductsQuantity'
      ]
    ]);
  }

  /**
   * Return file version of the integration.
   */
  private function fileVersion()
  {
    return response()->json(['file_version' => '1.0.0']);
  }

  /**
   * Return a list of product categories.
   */
  private function productsCategories($integrationId)
  {
    $categories = Category::where('integration_id', $integrationId)->get();
    return response()->json($categories->mapWithKeys(fn($c) => [ $c->id =>$c->name]));
  }

  /**
   * Return a list of products with advanced filters.
   */
  private function productsList($integrationId, Request $request)
  {
    $query = Product::where('integration_id', $integrationId);

    // Apply filters
    if ($request->filled('category_id') && $request->input('category_id') != 'all') {
        $categoryId = $request->input('category_id');
        $query->whereHas('categories', fn($q) => $q->where('id', $categoryId));
    }
    if ($request->filled('filter_id')) {
      $query->where('id', $request->input('filter_id'));
    }
    if ($request->filled('filter_ids_list')) {
      $ids = explode(',', $request->input('filter_ids_list'));
      $query->whereIn('id', $ids);
    }
    if ($request->filled('filter_sku')) {
      $query->where('sku', $request->input('filter_sku'));
    }
    if ($request->filled('filter_name')) {
      $query->where('name', 'like', '%' . $request->input('filter_name') . '%');
    }
    if ($request->filled('filter_ean')) {
      $query->where('ean', $request->input('filter_ean'));
    }
    if ($request->filled('filter_price_from')) {
      $query->where('price', '>=', $request->input('filter_price_from'));
    }
    if ($request->filled('filter_price_to')) {
      $query->where('price', '<=', $request->input('filter_price_to'));
    }
    if ($request->filled('filter_quantity_from')) {
      $query->where('quantity', '>=', $request->input('filter_quantity_from'));
    }
    if ($request->filled('filter_quantity_to')) {
      $query->where('quantity', '<=', $request->input('filter_quantity_to'));
    }
    if ($request->filled('filter_available')) {
      $query->where('quantity', $request->input('filter_available') == 1 ? '>' : '=', 0);
    }
    if ($request->filled('filter_sort')) {
      $sort = explode(' ', string: $request->input('filter_sort'));
      $query->orderBy($sort[0], direction: $sort[1] ?? 'ASC');
    }

    // Pagination
    $page = $request->input('page', 1);
    $perPage = $request->input('filter_limit', 200);
    $products = $query->paginate($perPage, ['*'], 'page', $page);

    $productData = $products->mapWithKeys(fn($p) => [
      $p->id => [
        'name' => $p->name,
        'quantity' => $p->quantity,
        'price' => $p->price,
        'sku' => $p->sku,
        'location' => $p->location,
        'currency' => $p->currency,
      ]
    ]);
    $productData['pages'] = $products->lastPage();

    return response()->json( $productData);
  }

  /**
   * Return detailed product data.
   */
  private function productsData($integrationId, Request $request)
{
    $productsId = explode(',', $request->input('products_id'));

    // Retrieve products based on `integration_id` and specified product IDs
    $products = Product::where('integration_id', $integrationId)
        ->whereIn('id', $productsId)
        ->with('categories')  // Load categories to access category_id and name
        ->get();

    // Return error response if no products found
    if ($products->isEmpty()) {
        return response()->json([
            'error' => true,
            'error_code' => 'not_found',
            'error_text' => 'Product not found'
        ], 404);
    }

    // Map response format based on Baselinker requirements
    return response()->json(
        $products->mapWithKeys(function ($product) {
            return [
                $product->id => [
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'ean' => $product->ean,
                    'quantity' => $product->quantity,
                    'price' => $product->price,
                    'currency' => $product->currency,
                    'tax' => $product->tax,
                    'weight' => $product->weight,
                    'height' => $product->height,
                    'length' => $product->length,
                    'width' => $product->width,
                    'description' => $product->description,
                    'description_extra1' => $product->description_extra1,
                    'description_extra2' => $product->description_extra2,
                    'description_extra3' => $product->description_extra3,
                    'description_extra4' => $product->description_extra4,
                    'man_name' => $product->man_name,
                    'category_id' => optional($product->categories->first())->id,  // Only one category ID
                    'category_name' => optional($product->categories->first())->name,
                    'location' => $product->location,
                    'url' => $product->url,
                    'images' => $product->images,
                    'features' => $product->features ?? [],  // Ensure features format: [["name", "value"], ...]
                    'delivery_time' => $product->delivery_time,
                    'variants' => $product->variants ?? []  // Ensure variants format with variant ID as keys
                ]
            ];
        })
    );
}

  /**
   * Return product prices.
   */
  private function productsPrices($integrationId, Request $request)
  {
    $productIds = $request->input('product_ids', []);
    $prices = Product::where('integration_id', $integrationId)
      ->whereIn('id', $productIds)
      ->get(['id', 'price']);

    $priceData = $prices->mapWithKeys(fn($p) => [$p->id => $p->price]);
    return response()->json(['prices' => $priceData]);
  }

  /**
   * Return product quantities.
   */
  private function productsQuantity($integrationId, Request $request)
  {
    $productIds = $request->input('product_ids', []);
    $quantities = Product::where('integration_id', $integrationId)
      ->whereIn('id', $productIds)
      ->get(['id', 'quantity']);

    $quantityData = $quantities->mapWithKeys(fn($p) => [$p->id => $p->quantity]);
    return response()->json(['quantities' => $quantityData]);
  }
}
