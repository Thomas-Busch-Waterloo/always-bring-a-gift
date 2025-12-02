<?php

namespace Database\Factories;

use App\Models\EventType;
use App\Models\Person;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'person_id' => Person::factory(),
            'event_type_id' => EventType::factory(),
            'is_annual' => false,
            'date' => fake()->dateTimeBetween('-1 year', '+1 year'),
            'budget' => fake()->optional()->randomFloat(2, 10, 500),
        ];
    }

    /**
     * Indicate that the event is annual recurring
     */
    public function annual(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_annual' => true,
        ]);
    }

    /**
     * Indicate that the event is upcoming (in the next 30 days)
     */
    public function upcoming(): static
    {
        return $this->state(fn (array $attributes) => [
            'date' => fake()->dateTimeBetween('now', '+30 days'),
        ]);
    }
}
