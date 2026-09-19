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
     * The name of the factory's corresponding model.
     *
     * @var class-string<Client>
     */
    protected $model = Client::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'secret' => 'secret',
            'redirect_uris' => ['http://localhost/callback'],
            'grant_types' => ['authorization_code', 'refresh_token'],
            'first_party' => false,
            'revoked' => false,
        ];
    }

    /**
     * Indicate that the client is one of our own products.
     */
    public function firstParty(): static
    {
        return $this->state(fn (array $attributes) => ['first_party' => true]);
    }
}
