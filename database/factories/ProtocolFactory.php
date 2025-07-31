<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
    
/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Protocol>
 */
class ProtocolFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence,
            'content' => $this->faker->paragraph(8),
            'tags' => $this->faker->randomElements(['detox', 'gut', 'energy', 'sleep'], 2),
            'author' => $this->faker->name,
            'rating' => $this->faker->randomFloat(1, 3, 5),
        ];
    }
    
}
