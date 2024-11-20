<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Category;
use App\Models\Product;
use App\Models\Integration;
use PHPUnit\Framework\Attributes\Test;

class ProductCategyApiTest extends TestCase
{
    use RefreshDatabase;

    protected Integration $integration;
    protected Product $product1;
    protected Product $product2;
    protected Category $category1;
    protected Category $category2;

    protected function setUp(): void
    {
        parent::setUp();

        // Create Integration
        $this->integration = Integration::factory()->create(['api_key' => 'test_key']);

        // Create Multiple Categories
        $this->category1 = Category::factory()->create(['integration_id' => $this->integration->id, 'id' => 'cat1', 'name' => 'Category 1']);
        $this->category2 = Category::factory()->create(['integration_id' => $this->integration->id, 'id' => 'cat2', 'name' => 'Category 2']);

        // Create Product and Assign Multiple Categories
        $this->product1 = Product::factory()->create([
            'integration_id' => $this->integration->id,
            'id' => 'prod123',
            'name' => 'Sample Product',
            'sku' => 'SP123',
            'quantity' => 10,
            'price' => 99.99,
            'features' => [
                ['Material', 'Polyester'],
                ['Size', 'L | XL'],
            ]
        ]);
        // Attach both categories to the product
        $this->product1->categories()->attach([$this->category1->auto_id, $this->category2->auto_id]);

        $this->product2 = Product::factory()->create([
            'integration_id' => $this->integration->id,
            'id' => 'prod456',
            'name' => 'Product from second category'
        ]);

        $this->product2->categories()->attach([$this->category2->auto_id]);
    }

    #[Test]
    public function it_filters_products_by_category_id()
    {


        // URL for API endpoint
        $url = "/api/integration/{$this->integration->id}/api";

        // Test filter by category1
        $response = $this->postJson($url, [
            'bl_pass' => $this->integration->api_key,
            'action' => 'ProductsList',
            'category_id' => $this->category1->id
        ]);
        $response->assertStatus(200);
        // Assert that the response includes the product in the specified category
        $this->assertArrayHasKey($this->product1->id, $response->json());
        $this->assertArrayNotHasKey($this->product2->id, $response->json());
        $this->assertEquals($this->product1->name, $response->json("{$this->product1->id}.name"));
    }

    #[Test]
    public function it_returns_single_category_id_in_products_data()
    {
        $url = "/api/integration/{$this->integration->id}/api";

        // Test ProductsData to check `category_id` response format
        $response = $this->postJson($url, [
            'bl_pass' => $this->integration->api_key,
            'action' => 'ProductsData',
            'products_id' => $this->product1->id
        ]);

        $response->assertStatus(200);
        // Assert that only one category_id is returned
        $data = $response->json($this->product1->id);
        $this->assertArrayHasKey('category_id', $data);
        $this->assertContains($data['category_id'], [$this->category1->id, $this->category2->id]);

        // Optionally, assert other expected fields in the response
        $this->assertEquals('Sample Product', $data['name']);
        $this->assertEquals('SP123', $data['sku']);
    }
}
