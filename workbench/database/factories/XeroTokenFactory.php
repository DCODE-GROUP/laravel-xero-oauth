<?php

namespace Workbench\Database\Factories;

use Dcodegroup\LaravelXeroOauth\Models\XeroToken;
use Illuminate\Database\Eloquent\Factories\Factory;

class XeroTokenFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<XeroToken>
     */
    protected $model = XeroToken::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $expiresAt = now()->addSeconds(3600);

        return [
            'id_token' => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIn0.signature',
            'token_type' => 'Bearer',
            'access_token' => 'access_'.$this->faker->sha256(),
            'refresh_token' => 'refresh_'.$this->faker->sha256(),
            'scope' => 'openid email profile offline_access',
            'current_tenant_id' => $this->faker->uuid(),
            'expires' => $expiresAt->timestamp,
        ];
    }
}
