<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {

        return [
            'company_name' => fake()->company(),
            'company_address' => fake()->streetAddress(),
            'company_address_city' => fake()->city(),
            'company_address_state' => fake()->state(),
            'company_address_zip' => fake()->postcode(),
            'company_address_country' => fake()->country(),
        ];
    }
}
