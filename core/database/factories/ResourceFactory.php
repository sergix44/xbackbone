<?php

namespace Database\Factories;

use App\Models\Properties\ResourceType;
use App\Models\Resource;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ResourceFactory extends Factory
{
    protected $model = Resource::class;

    public function definition(): array
    {
        return [
            'type' => ResourceType::FILE,
            'user_id' => User::factory(),
            'code' => fake()->unique()->lexify('??????????'),
            'filename' => fake()->word().'.bin',
            'extension' => 'bin',
            'size' => fake()->numberBetween(1, 10 * 1024 * 1024),
            'mime' => 'application/octet-stream',
        ];
    }

    public function image(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ResourceType::IMAGE,
            'filename' => fake()->word().'.png',
            'extension' => 'png',
            'mime' => 'image/png',
        ]);
    }
}
