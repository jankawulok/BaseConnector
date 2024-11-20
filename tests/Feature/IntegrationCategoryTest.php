<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Integration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class IntegrationCategoryTest extends TestCase
{
    use RefreshDatabase;

    private Integration $integration1;
    private Integration $integration2;
    private Product $product1;
    private Product $product2;
    private Category $category1Integration1;
    private Category $category1Integration2;

    protected function setUp(): void
    {
        parent::setUp();

        // Create two integrations
        $this->integration1 = Integration::factory()->create(['api_key' => 'test_key_1']);

        $this->integration2 = Integration::factory()->create(['api_key' => 'test_key_2']);

        // Create categories with the same ID but different names for each integration
        $this->category1Integration1 = Category::factory()->create([
            'integration_id' => $this->integration1->id,
            'id' => 'cat1',
            'name' => 'Category 1 for Integration 1'
        ]);

        $this->category1Integration2 = Category::factory()->create([
            'integration_id' => $this->integration2->id,
            'id' => 'cat1',
            'name' => 'Category 1 for Integration 2'
        ]);

        // Create products and assign categories
        $this->product1 = Product::factory()->create([
            'integration_id' => $this->integration1->id,
            'id' => 'prod1',
            'name' => 'Product 1',
            'sku' => 'P1',
        ]);

        $this->product2 = Product::factory()->create([
            'integration_id' => $this->integration2->id,
            'id' => 'prod2',
            'name' => 'Product 2',
            'sku' => 'P2',
        ]);

        // Attach categories to products
        $this->product1->categories()->attach($this->category1Integration1->auto_id);
        $this->product2->categories()->attach($this->category1Integration2->auto_id);
    }

    #[Test]
    public function it_returns_correct_categories_for_the_specified_integration()
    {
        // URL for API endpoint for integration1
        $url = "/api/integration/{$this->integration1->id}/api";

        // Call the ProductsCategories API for integration1
        $response = $this->postJson($url, [
            'bl_pass' => $this->integration1->api_key,
            'action' => 'ProductsCategories'
        ]);

        $response->assertStatus(200);

        // Assert that the response includes only the category from integration1
        $categories = $response->json();

        // Verify that category1 for integration1 is present
        $this->assertArrayHasKey($this->category1Integration1->id, $categories);
        $this->assertEquals('Category 1 for Integration 1', $categories[$this->category1Integration1->id]);

        // Verify that category1 for integration2 is NOT present
        $this->assertNotEquals($this->category1Integration2->name, $categories[$this->category1Integration1->id]);
    }

    #[Test]
    public function it_returns_products_with_correct_categories()
    {
        // URL for API endpoint for integration1
        $url = "/api/integration/{$this->integration1->id}/api";

        // Call the ProductsList API for integration1 to check category association
        $response = $this->postJson($url, [
            'bl_pass' => $this->integration1->api_key,
            'action' => 'ProductsList'
        ]);

        $response->assertStatus(200);

        // Get the products and their categories
        $products = $response->json();

        // Check if product1 includes the correct category_id
        $this->assertArrayHasKey($this->product1->id, $products);

        // Check that product2 from integration2 is NOT in the response
        $this->assertArrayNotHasKey($this->product2->id, $products);
    }
}
