<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Integration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    protected Product $product;
    protected Integration $integration;
    protected Category $category;
    protected Category $category1;
    protected Category $category2;

    protected function setUp(): void
    {
        parent::setUp();

        // Create Integration, Categories, and Products for testing
        $this->integration = Integration::factory()->create(['api_key' => 'test_key']);
        $this->category = Category::factory()->create(['integration_id' => $this->integration->id, 'name' => 'Test Category']);
        $this->category1 = Category::factory()->create(['integration_id' => $this->integration->id, 'name' => 'Category 1']);
        $this->category2 = Category::factory()->create(['integration_id' => $this->integration->id, 'name' => 'Category 2']);

        // Sample Product with Features and Variants
        $this->product = Product::factory()->create([
            'integration_id' => $this->integration->id,
            'id' => 'prod123',
            'name' => 'Sample Product',
            'sku' => 'SP123',
            'quantity' => 10,
            'price' => 99.99,
            'features' => [
                ['Material', 'Polyester'],
                ['Size', 'L | XL'],
            ],
            'variants' => [
                'v1' => [
                    'full_name' => 'Sample Product - Variant 1',
                    'name' => 'Variant 1',
                    'price' => 49.99,
                    'quantity' => 5,
                    'sku' => 'SP123-V1',
                ],
            ]
        ]);

        // Attach categories via the pivot table after creating the product
        $this->product->categories()->attach($this->category->auto_id);
    }

    #[Test]
    public function it_fetches_products_data_in_correct_format()
    {
        $url = "/api/integration/{$this->integration->id}/api";

        $response = $this->postJson($url, [
            'bl_pass' => $this->integration->api_key,
            'action' => 'ProductsData',
            'products_id' => 'prod123'
        ]);

        $response->assertStatus(200);

        // Check basic product structure
        $response->assertJsonStructure([
            'prod123' => [
                'sku', 'name', 'quantity', 'price', 'features', 'variants', 'category_id'
            ]
        ]);

        // Check features format
        $features = $response->json('prod123.features');
        $this->assertIsArray($features);
        $this->assertCount(2, $features);
        $this->assertEquals(['Material', 'Polyester'], $features[0]);
        $this->assertEquals(['Size', 'L | XL'], $features[1]);

        // Check variants format
        $variants = $response->json('prod123.variants');
        $this->assertArrayHasKey('v1', $variants);
        $this->assertEquals('Sample Product - Variant 1', $variants['v1']['full_name']);
    }

    #[Test]
    public function it_lists_products_with_filters_and_pagination()
    {
        // Add more products than the pagination limit
        $totalProducts = 7;
        Product::factory()->count($totalProducts - 1)->create(['integration_id' => $this->integration->id]);

        $url = "/api/integration/{$this->integration->id}/api";
        $filterLimit = 3;

        // First page request with a limit of 3 items per page
        $response = $this->postJson($url, [
            'bl_pass' => $this->integration->api_key,
            'action' => 'ProductsList',
            'filter_limit' => $filterLimit,
            'page' => 1
        ]);
        $response->assertStatus(200);
        $productsPage1 = $response->json();


        // Calculate the expected page count based on total products and limit
        $expectedPageCount = (int) ceil($totalProducts / $filterLimit);

        // Check that the total number of pages is as expected
        $this->assertEquals($expectedPageCount, (int) $response->json('pages'));

        // Check that the first page has the correct number of items (3)
        unset($productsPage1['pages']); // remove 'pages' key as that is not product
        $this->assertCount($filterLimit, $productsPage1);



        // Check that a request for the last page contains the remaining products
        $response = $this->postJson($url, [
            'bl_pass' => $this->integration->api_key,
            'action' => 'ProductsList',
            'filter_limit' => $filterLimit,
            'page' => $expectedPageCount
        ]);

        $response->assertStatus(200);
        $productsLastPage = $response->json();
        unset($productsLastPage['pages']);
        // Ensure that the last page has the remaining products (1 in this case)
        $this->assertCount($totalProducts % $filterLimit, $productsLastPage);
    }

    #[Test]
    public function it_applies_multiple_filters_correctly()
    {
        // Create additional test products
        Product::factory()->create(['integration_id' => $this->integration->id, 'price' => 50, 'quantity' => 2]);
        Product::factory()->create(['integration_id' => $this->integration->id, 'price' => 120, 'quantity' => 5]);
        Product::factory()->create(['integration_id' => $this->integration->id, 'price' => 20, 'quantity' => 3]);
        Product::factory()->create(['integration_id' => $this->integration->id, 'price' => 200, 'quantity' => 0]);
        Product::factory()->create(['integration_id' => $this->integration->id, 'price' => 202, 'quantity' => 300]);
        $url = "/api/integration/{$this->integration->id}/api";

        // Test multiple filters - price range and quantity
        $response = $this->postJson($url, [
            'bl_pass' => $this->integration->api_key,
            'action' => 'ProductsList',
            'filter_price_from' => 40,
            'filter_price_to' => 100,
            'filter_quantity_from' => 1,
            'filter_quantity_to' => 10,
        ]);

        $response->assertStatus(200);
        $filteredProducts = $response->json();

        unset($filteredProducts['pages']);
        // Check if only products within specified price and quantity are returned
        foreach ($filteredProducts as $product) {
            $this->assertGreaterThanOrEqual(40, $product['price']);
            $this->assertLessThanOrEqual(100, $product['price']);
            $this->assertGreaterThanOrEqual(1, $product['quantity']);
            $this->assertLessThanOrEqual(10, $product['quantity']);
        }
    }

    #[Test]
    public function it_sorts_products_correctly()
    {
        // Create test products with various prices
        Product::factory()->create(['integration_id' => $this->integration->id, 'price' => 50]);
        Product::factory()->create(['integration_id' => $this->integration->id, 'price' => 120]);
        Product::factory()->create(['integration_id' => $this->integration->id, 'price' => 30]);

        $url = "/api/integration/{$this->integration->id}/api";

        // Request sorting by price in descending order
        $response = $this->postJson($url, [
            'bl_pass' => $this->integration->api_key,
            'action' => 'ProductsList',
            'filter_sort' => 'price DESC'
        ]);

        $response->assertStatus(200);
        $products = $response->json();
        unset($products['pages']);
        $sortedProducts = array_values($products); // Convert to numerically indexed array
        // Assert that the products are sorted by price in descending order
        for ($i = 0; $i < count($sortedProducts) -1; $i++) {
            $this->assertGreaterThanOrEqual(
                $sortedProducts[$i+1]['price'],
                $sortedProducts[$i]['price']
            );
        }
    }
}
