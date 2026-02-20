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
    // Connectivity test or manual browse check
    if ($request->isMethod('get')) {
      return response()->json([
        'error' => true,
        'error_code' => 'no_password',
        'error_text' => 'Integration active. Use POST for API communications.'
      ]);
    }

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
      'SupportedMethods',
      'FileVersion',
      'ProductsCategories',
      'ProductsList',
      'ProductsData',
      'ProductsPrices',
      'ProductsQuantity'
    ]);
  }

  /**
   * Return file version of the integration.
   */
  private function fileVersion()
  {
    return response()->json([
      'platform' => 'BaseConnector',
      'version' => '1.1.0',
      'standard' => 4
    ]);
  }

  /**
   * Return a list of product categories.
   */
  private function productsCategories($integrationId)
  {
    $categories = Category::where('integration_id', $integrationId)->get();

    $categoryData = [];
    foreach ($categories as $c) {
      $categoryData[(string) $c->id] = $c->name;
    }

    // Using (object) to handle cases where all IDs are numeric and sequential
    return response()->json((object) $categoryData);
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

    $productData = [];
    foreach ($products as $p) {
      $productData[(string) $p->id] = [
        'name' => $p->name,
        'quantity' => (int) $p->quantity,
        'price' => number_format($p->price, 2, '.', ''),
        'sku' => $p->sku,
        'location' => $p->location,
        'currency' => $p->currency,
      ];
    }
    $productData['pages'] = (int) $products->lastPage();

    return response()->json($productData);
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

    $result = [];
    foreach ($products as $product) {
      $features = [];
      if (is_array($product->features)) {
        foreach ($product->features as $f) {
          $name = $f['name'] ?? ($f[0] ?? '');
          $value = $f['value'] ?? ($f[1] ?? '');
          if ($name !== '') {
            $features[] = [$name, $value];
          }
        }
      }

      $variants = [];
      if (is_array($product->variants)) {
        foreach ($product->variants as $vId => $vData) {
          $variants[(string) $vId] = $vData;
        }
      }

      $result[(string) $product->id] = [
        'name' => $product->name,
        'sku' => $product->sku,
        'ean' => $product->ean,
        'quantity' => (int) $product->quantity,
        'price' => number_format($product->price, 2, '.', ''),
        'currency' => $product->currency,
        'tax' => (int) $product->tax,
        'weight' => (float) $product->weight,
        'height' => (float) $product->height,
        'length' => (float) $product->length,
        'width' => (float) $product->width,
        'description' => $product->description,
        'description_extra1' => $product->description_extra1,
        'description_extra2' => $product->description_extra2,
        'description_extra3' => $product->description_extra3,
        'description_extra4' => $product->description_extra4,
        'man_name' => $product->man_name,
        'category_id' => (string) optional($product->categories->first())->id,
        'category_name' => optional($product->categories->first())->name,
        'location' => $product->location,
        'url' => $product->url,
        'images' => $product->images ?: [],
        'features' => $features,
        'delivery_time' => (int) $product->delivery_time,
        'variants' => (object) $variants
      ];
    }

    return response()->json((object) $result);
  }

  /**
   * Return product prices.
   */
  private function productsPrices($integrationId, Request $request)
  {
    $query = Product::where('integration_id', $integrationId);

    $page = $request->input('page', 1);
    $products = $query->paginate(500, ['*'], 'page', $page);

    $priceData = [];
    foreach ($products as $p) {
      $data = ['0' => number_format($p->price, 2, '.', '')];
      if ($p->variants) {
        foreach ($p->variants as $vId => $vData) {
          $data[(string) $vId] = number_format($vData['price'] ?? $p->price, 2, '.', '');
        }
      }
      // Use (object) to force json_encode to output an object {"0": "...", "vId": "..."}
      // instead of a numerical array ["...", "..."] which would loose IDs.
      $priceData[(string) $p->id] = (object) $data;
    }

    $priceData['pages'] = (int) $products->lastPage();
    return response()->json($priceData);
  }

  /**
   * Return product quantities.
   */
  private function productsQuantity($integrationId, Request $request)
  {
    $query = Product::where('integration_id', $integrationId);

    $page = $request->input('page', 1);
    $products = $query->paginate(500, ['*'], 'page', $page);

    $quantityData = [];
    foreach ($products as $p) {
      $data = ['0' => (int) $p->quantity];
      if ($p->variants) {
        foreach ($p->variants as $vId => $vData) {
          $data[(string) $vId] = (int) ($vData['quantity'] ?? 0);
        }
      }
      // Using (object) is CRITICAL here to preserve "0" and variant IDs as keys.
      $quantityData[(string) $p->id] = (object) $data;
    }

    $quantityData['pages'] = (int) $products->lastPage();
    return response()->json($quantityData);
  }
}
