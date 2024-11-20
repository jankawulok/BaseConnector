<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Integration;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition()
    {
        return [
            'id' => $this->faker->numberBetween(100,1000000),
            'sku' => $this->faker->unique()->ean8,
            'name' => $this->faker->name,
            'price' => $this->faker->randomFloat(2, 10, 1000),
            'quantity' => $this->faker->numberBetween(0, 100),
            'integration_id' => Integration::factory(),
            'tax' => 23,
        ];
    }
}
