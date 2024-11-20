<?php

namespace Tests\Feature;

use App\Models\Integration;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class IntegrationE2ETest extends TestCase
{
    use RefreshDatabase;

    private Integration $integration;
    private array $importData;

    protected function setUp(): void
    {
        parent::setUp();

        // Create integration
        $this->integration = Integration::factory()->create([
            'name' => 'Test Integration',
            'api_key' => 'test_api_key'
        ]);

        // Sample import data structure
        $this->importData = [
            'categories' => [
                [
                    'id' => 'cat1',
                    'name' => 'Electronics'
                ],
                [
                    'id' => 'cat2',
                    'name' => 'Smartphones'
                ]
            ],
            'products' => [
                [
                    'id' => 'prod1',
                    'sku' => 'PHONE-123',
                    'ean' => '1234567890123',
                    'name' => 'iPhone 15 Pro',
                    'quantity' => 50,
                    'price' => 999.99,
                    'currency' => 'EUR',
                    'tax' => 23,
                    'weight' => 0.2,
                    'height' => 15.0,
                    'length' => 7.5,
                    'width' => 1.0,
                    'description' => 'Latest iPhone model',
                    'man_name' => 'Apple',
                    'location' => 'Warehouse A',
                    'url' => 'https://example.com/iphone15pro',
                    'images' => ['iphone1.jpg', 'iphone2.jpg'],
                    'features' => [
                        ['name' => 'Color', 'value' => 'Titanium'],
                        ['name' => 'Storage', 'value' => '256GB']
                    ],
                    'delivery_time' => 1,
                    'variants' => [
                        'var1' => [
                            'name' => '256GB',
                            'price' => 999.99
                        ],
                        'var2' => [
                            'name' => '512GB',
                            'price' => 1199.99
                        ]
                    ],
                    'categories' => ['cat1', 'cat2']
                ],
                [
                    'id' => 'prod2',
                    'sku' => 'PHONE-456',
                    'name' => 'Samsung S24',
                    'quantity' => 30,
                    'price' => 899.99,
                    'currency' => 'EUR',
                    'categories' => ['cat1', 'cat2']
                ]
            ]
        ];
    }

    #[Test]
    public function complete_e2e_flow_from_import_to_api_responses()
    {
        // Step 1: Import Categories
        foreach ($this->importData['categories'] as $categoryData) {
            Category::create([
                'integration_id' => $this->integration->id,
                'id' => $categoryData['id'],
                'name' => $categoryData['name']
            ]);
        }

        // Step 2: Import Products
        foreach ($this->importData['products'] as $productData) {
            // Extract category IDs
            $categoryIds = $productData['categories'];
            unset($productData['categories']);

            // Create product
            $product = Product::create([
                'integration_id' => $this->integration->id,
                ...$productData
            ]);

            // Attach categories
            $categories = Category::whereIn('id', $categoryIds)
                ->where('integration_id', $this->integration->id)
                ->get();

            $product->categories()->attach($categories->pluck('auto_id'));
        }

        // Step 3: Test API Endpoints
        $baseUrl = "/api/integration/{$this->integration->id}/api";
        $validPayload = ['bl_pass' => 'test_api_key'];

        // 3.1 Test Categories Endpoint
        $categoriesResponse = $this->postJson($baseUrl, [
            ...$validPayload,
            'action' => 'ProductsCategories'
        ]);

        $categoriesResponse
            ->assertStatus(200)
            ->assertJson([
                'cat1' => 'Electronics',
                'cat2' => 'Smartphones'
            ]);

        // 3.2 Test ProductsList Endpoint
        $productsListResponse = $this->postJson($baseUrl, [
            ...$validPayload,
            'action' => 'ProductsList'
        ]);

        $productsListResponse
            ->assertStatus(200)
            ->assertJsonStructure([
                'prod1' => [
                    'name',
                    'quantity',
                    'price',
                    'sku',
                    'location',
                    'currency'
                ],
                'prod2' => [
                    'name',
                    'quantity',
                    'price',
                    'sku',
                    'location',
                    'currency'
                ]
            ]);

        // 3.3 Test ProductsData Endpoint
        $productsDataResponse = $this->postJson($baseUrl, [
            ...$validPayload,
            'action' => 'ProductsData',
            'products_id' => 'prod1'
        ]);

        $productsDataResponse
            ->assertStatus(200)
            ->assertJsonStructure([
                'prod1' => [
                    'name',
                    'sku',
                    'ean',
                    'quantity',
                    'price',
                    'currency',
                    'tax',
                    'weight',
                    'height',
                    'length',
                    'width',
                    'description',
                    'man_name',
                    'category_id',
                    'category_name',
                    'location',
                    'url',
                    'images',
                    'features',
                    'delivery_time',
                    'variants'
                ]
            ])
            ->assertJson([
                'prod1' => [
                    'name' => 'iPhone 15 Pro',
                    'sku' => 'PHONE-123',
                    'price' => 999.99,
                    'features' => [
                        ['name' => 'Color', 'value' => 'Titanium'],
                        ['name' => 'Storage', 'value' => '256GB']
                    ]
                ]
            ]);

        // 3.4 Test Filtering
        $filteredResponse = $this->postJson($baseUrl, [
            ...$validPayload,
            'action' => 'ProductsList',
            'filter_price_from' => 900
        ]);

        $responseData = $filteredResponse->json();

        $filteredResponse
            ->assertStatus(200)
            ->assertJsonFragment(['name' => 'iPhone 15 Pro']);

        // Remove 'pages' key and verify only one product matches the filter
        unset($responseData['pages']);
        $this->assertCount(1, $responseData);
        $this->assertEquals('iPhone 15 Pro', array_values($responseData)[0]['name']);

        // 3.5 Test Category Filter
        $categoryFilterResponse = $this->postJson($baseUrl, [
            ...$validPayload,
            'action' => 'ProductsList',
            'category_id' => 'cat1'
        ]);

        $categoryResponseData = $categoryFilterResponse->json();

        $categoryFilterResponse->assertStatus(200);

        // Remove 'pages' key and verify both products are in the category
        unset($categoryResponseData['pages']);
        $this->assertCount(2, $categoryResponseData);
        $this->assertArrayHasKey('prod1', $categoryResponseData);
        $this->assertArrayHasKey('prod2', $categoryResponseData);

        // 3.6 Test Prices Endpoint
        $pricesResponse = $this->postJson($baseUrl, [
            ...$validPayload,
            'action' => 'ProductsPrices',
            'product_ids' => ['prod1', 'prod2']
        ]);

        $pricesResponse
            ->assertStatus(200)
            ->assertJson([
                'prices' => [
                    'prod1' => 999.99,
                    'prod2' => 899.99
                ]
            ]);

        // 3.7 Test Quantities Endpoint
        $quantitiesResponse = $this->postJson($baseUrl, [
            ...$validPayload,
            'action' => 'ProductsQuantity',
            'product_ids' => ['prod1', 'prod2']
        ]);

        $quantitiesResponse
            ->assertStatus(200)
            ->assertJson([
                'quantities' => [
                    'prod1' => 50,
                    'prod2' => 30
                ]
            ]);
    }
}
