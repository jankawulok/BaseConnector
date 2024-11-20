<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition()
    {
    return [
        "name" => $this->faker->name,
        "id" =>$this->faker->numberBetween(1,10000),
        "integration_id" => $this->faker->numberBetween(1,100),
    ];
    }
}
