<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->words(2, true);
        return [
            'name'        => $name,
            'description' => fake()->sentence(),
            'slug'        => Str::slug($name),
            'image_url'   => fake()->imageUrl(),
            'is_active'   => true,
        ];
    }
}
