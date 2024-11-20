<?php

namespace Tests\Feature;

use App\Models\Integration;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class IntegrationControllerTest extends TestCase
{
    use RefreshDatabase;

    private $integration;
    private $headers;

    protected function setUp(): void
    {
        parent::setUp();

        // Create an integration with a known API key
        $this->integration = Integration::factory()->create(['api_key' => 'test_bl_pass']);
        $this->headers = ['bl_pass' => 'test_bl_pass'];
    }

    #[Test]
    public function it_validates_bl_pass()
    {
        $response = $this->postJson("/api/integration/{$this->integration->id}/api", [
            'bl_pass' => 'invalid_key',
            'action' => 'SupportedMethods'
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'error' => true,
                'error_code' => 'invalid_bl_pass',
                'error_text' => 'Invalid API key',
            ]);
    }

    #[Test]
    public function it_returns_supported_methods()
    {
        $response = $this->postJson("/api/integration/{$this->integration->id}/api", [
            'bl_pass' => $this->integration->api_key,
            'action' => 'SupportedMethods'
        ]);

        $response->assertStatus(200)
            ->assertJson([
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

    #[Test]
    public function it_returns_file_version()
    {
        $response = $this->postJson("/api/integration/{$this->integration->id}/api", [
            'bl_pass' => $this->integration->api_key,
            'action' => 'FileVersion'
        ]);

        $response->assertStatus(200)
            ->assertJson(['file_version' => '1.0.0']);
    }

    #[Test]
    public function it_returns_products_categories()
    {
        // Create sample categories for the integration
        $categories = Category::factory()->count(3)->create(['integration_id' => $this->integration->id]);

        $response = $this->postJson("/api/integration/{$this->integration->id}/api", [
            'bl_pass' => $this->integration->api_key,
            'action' => 'ProductsCategories'
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment([$categories[0]->id => $categories[0]->name]);
    }

    #[Test]
    public function it_returns_filtered_products_list()
    {
        $category = Category::factory()->create(['id' => '11111', 'integration_id'=> $this->integration->id]);
        // Create products with different attributes for filtering
        $p = Product::factory()->create([
            'integration_id' => $this->integration->id,
            'sku' => 'ABC123',
            'name' => 'Test Product 1',
            'price' => 100,
            'quantity' => 10,
        ]);


        (Product::factory()->create([
            'integration_id' => $this->integration->id,
            'sku' => 'DEF456',
            'name' => 'Test Product 2',
            'price' => 150,
            'quantity' => 5,
        ]));


        $response = $this->postJson("/api/integration/{$this->integration->id}/api", [
            'bl_pass' => $this->integration->api_key,
            'action' => 'ProductsList',
            'filter_sku' => 'ABC123'
        ]);
        $response->assertStatus(200)
            ->assertJsonFragment(['sku' => 'ABC123']);
    }

    #[Test]
    public function it_filters_by_sku()
    {
        $category = Category::factory()->create(['id' => '11111', 'integration_id'=> $this->integration->id]);
        // Create products with different attributes for filtering
        Product::factory()->create([
            'integration_id' => $this->integration->id,
            'sku' => 'ABC123',
            'name' => 'Test Product 1',
            'price' => 100,
            'quantity' => 10,
        ]);

        Product::factory()->create([
            'integration_id' => $this->integration->id,
            'sku' => 'DEF456',
            'name' => 'Test Product 2',
            'price' => 150,
            'quantity' => 5,
        ]);

        $response = $this->postJson("/api/integration/{$this->integration->id}/api", [
            'bl_pass' => $this->integration->api_key,
            'action' => 'ProductsList',
            'filter_sku' => 'ABC123456'
        ]);
        $response->assertStatus(200)
            ->assertJsonFragment([]);
    }

    #[Test]
    public function it_returns_product_data()
    {
        $category = Category::factory()->create(['id' => '11111', 'integration_id'=> $this->integration->id]);

        $product = Product::factory()->create([
            'integration_id' => $this->integration->id,
            'sku' => 'ABC123',
            'name' => 'Example Product',
            'price' => 100,
            'quantity' => 10,
        ]);

        $response = $this->postJson("/api/integration/{$this->integration->id}/api", [
            'bl_pass' => $this->integration->api_key,
            'action' => 'ProductsData',
            'products_id' => $product->id
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Example Product', 'sku' => 'ABC123']);
    }

    #[Test]
    public function it_returns_product_prices()
    {
        $product = Product::factory()->create([
            'integration_id' => $this->integration->id,
            'sku' => 'ABC123',
            'price' => 100
        ]);

        $response = $this->postJson("/api/integration/{$this->integration->id}/api", [
            'bl_pass' => $this->integration->api_key,
            'action' => 'ProductsPrices',
            'product_ids' => [$product->id]
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment([$product->id => 100]);
    }

    #[Test]
    public function it_returns_product_quantities()
    {
        $product = Product::factory()->create([
            'integration_id' => $this->integration->id,
            'sku' => 'ABC123',
            'quantity' => 15
        ]);

        $response = $this->postJson("/api/integration/{$this->integration->id}/api", [
            'bl_pass' => $this->integration->api_key,
            'action' => 'ProductsQuantity',
            'product_ids' => [$product->id]
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment([$product->id => 15]);
    }
}
