<?php

declare(strict_types=1);

namespace PhoneNumbers\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use PhoneNumbers\Models\PhoneNumber;

class PhoneNumberFactory extends Factory
{
    protected $model = PhoneNumber::class;

    public function definition(): array
    {
        return [
            'type' => $this->faker->randomElement(['mobile', 'home', 'work', 'fax']),
            'is_primary' => false,
            'country_code' => '+1',
            'number' => $this->faker->numerify('##########'),
            'extension' => $this->faker->optional(0.2)->numerify('###'),
            'formatted' => null,
            'is_verified' => false,
            'metadata' => null,
        ];
    }

    public function primary(): static
    {
        return $this->state(fn () => ['is_primary' => true]);
    }

    public function mobile(): static
    {
        return $this->state(fn () => ['type' => 'mobile']);
    }

    public function home(): static
    {
        return $this->state(fn () => ['type' => 'home']);
    }

    public function work(): static
    {
        return $this->state(fn () => ['type' => 'work']);
    }

    public function fax(): static
    {
        return $this->state(fn () => ['type' => 'fax']);
    }

    public function verified(): static
    {
        return $this->state(fn () => ['is_verified' => true]);
    }

    public function withExtension(): static
    {
        return $this->state(fn () => [
            'extension' => $this->faker->numerify('###'),
        ]);
    }
}
