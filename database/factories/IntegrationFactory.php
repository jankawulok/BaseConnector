<?php

namespace Database\Factories;

use App\Models\Integration;
use Illuminate\Database\Eloquent\Factories\Factory;

class IntegrationFactory extends Factory
{
    protected $model = Integration::class;

    public function definition()
    {
        return [
            'api_key' => $this->faker->uuid(),
            'name' => $this->faker->company(),
            'enabled' => true,
        ];
    }
}
