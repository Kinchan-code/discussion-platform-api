<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory; 
use App\Models\Protocol;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Review>
 */
class ReviewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'protocol_id' => Protocol::factory(),
            'rating' => $this->faker->numberBetween(1, 5),
            'feedback' => $this->faker->optional()->sentence,
        ];
    }
    
}
