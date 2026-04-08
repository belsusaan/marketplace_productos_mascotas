<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id'          => User::factory(),
            'total_amount'     => $this->faker->randomFloat(2, 10, 500),
            'status'           => Order::STATUS_PENDING,
            'shipping_address' => $this->faker->address(),
            'notes'            => $this->faker->sentence(),
        ];
    }
}
