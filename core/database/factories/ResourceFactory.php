<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use XBB\Models\Properties\ResourceType;
use XBB\Models\Resource;
use XBB\Models\User;

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
            'fingerprint' => fake()->unique()->sha1(),
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

    public function svg(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ResourceType::IMAGE,
            'filename' => fake()->word().'.svg',
            'extension' => 'svg',
            'mime' => 'image/svg+xml',
        ]);
    }

    public function text(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ResourceType::TEXT,
            'filename' => fake()->word().'.json',
            'extension' => 'json',
            'mime' => 'application/json',
        ]);
    }

    public function pdf(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ResourceType::PDF,
            'filename' => fake()->word().'.pdf',
            'extension' => 'pdf',
            'mime' => 'application/pdf',
        ]);
    }

    public function video(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ResourceType::VIDEO,
            'filename' => fake()->word().'.mp4',
            'extension' => 'mp4',
            'mime' => 'video/mp4',
        ]);
    }

    public function link(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ResourceType::LINK,
            'data' => 'https://example.com/'.fake()->slug(),
            'filename' => null,
            'extension' => null,
            'mime' => null,
            'size' => null,
            'preview_type' => null,
            'preview_extension' => null,
        ]);
    }

    public function passwordProtected(string $password = 'secret'): static
    {
        // The model's 'hashed' cast hashes the value on assignment.
        return $this->state(fn (array $attributes) => [
            'password' => $password,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subDay(),
        ]);
    }

    public function withPreview(): static
    {
        return $this->state(fn (array $attributes) => [
            'preview_type' => ResourceType::IMAGE,
            'preview_extension' => 'webp',
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'preview_type' => ResourceType::FUTURE,
            'preview_extension' => null,
        ]);
    }
}
