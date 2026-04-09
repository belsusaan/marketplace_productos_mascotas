<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Store>
 */
class StoreFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id'     => User::factory(),
            'name'        => $this->faker->company(),
            'description' => $this->faker->sentence(),
            'logo_url'    => null,
            'is_active'   => true,
        ];
    }
}
