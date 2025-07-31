<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Protocol;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Thread>
 */
class ThreadFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'protocol_id' => Protocol::factory(), // temp, you can override
            'title' => $this->faker->sentence,
            'body' => $this->faker->paragraph(6),
        ];
    }
    
}
