<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Category;
use App\Models\Integration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    protected $integration;
    protected $categories;

    protected function setUp(): void
    {
        parent::setUp();

        // Create an Integration
        $this->integration = Integration::factory()->create();

        // Create Categories
        $this->categories = Category::factory()->count(3)->create(['integration_id' => $this->integration->id]);
    }

    /** @test */
    public function it_creates_a_product_with_categories()
    {
        // Create product with associated categories
        $product = Product::create([
            'id' => 'prod001',
            'integration_id' => $this->integration->id,
            'name' => 'Sample Product',
            'sku' => 'SP123',
            'quantity' => 10,
            'price' => 99.99,
        ]);

        // Attach categories to the product
        $product->categories()->sync($this->categories);

        $this->assertDatabaseHas('products', ['sku' => 'SP123']);
        $this->assertCount(3, $product->categories);  // Verify product has 3 categories
    }

    /** @test */
    public function it_retrieves_a_product_with_categories()
    {
        // Create a product with associated categories
        $product = Product::factory()->create([
            'id' => 'prod001',
            'integration_id' => $this->integration->id,
            'name' => 'Sample Product',
            'sku' => 'SP123',
        ]);
        $product->categories()->sync($this->categories);

        // Retrieve product and assert it has the correct categories
        $retrievedProduct = Product::with('categories')->find($product->auto_id);
        $this->assertEquals('Sample Product', $retrievedProduct->name);
        $this->assertCount(3, $retrievedProduct->categories);
    }

    /** @test */
    public function it_updates_a_product_and_categories()
    {
        // Create a product and attach categories
        $product = Product::factory()->create([
            'id' => 'prod001',
            'integration_id' => $this->integration->id,
            'name' => 'Sample Product',
            'sku' => 'SP123',
        ]);
        $product->categories()->sync($this->categories);

        // Create new categories and update product fields
        $newCategories = Category::factory()->count(2)->create(['integration_id' => $this->integration->id]);
        $product->update([
            'name' => 'Updated Product',
            'quantity' => 20,
        ]);
        $product->categories()->sync($newCategories);

        // Verify updates in the database
        $this->assertDatabaseHas('products', ['name' => 'Updated Product', 'quantity' => 20]);
        $this->assertCount(2, $product->fresh()->categories); // Check updated category count
    }

    /** @test */
    public function it_deletes_a_product_and_detaches_categories()
    {
        // Create a product and associate categories
        $product = Product::factory()->create([
            'id' => 'prod001',
            'integration_id' => $this->integration->id,
            'name' => 'Sample Product',
            'sku' => 'SP123',
        ]);
        $product->categories()->sync($this->categories);

        // Delete the product
        $product->delete();

        // Verify product deletion and category detachment
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
        $this->assertDatabaseMissing('category_product', ['product_id' => $product->id]);
    }
}
