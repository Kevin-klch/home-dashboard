<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Task>
 */
class TaskFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement([
                'Müll rausbringen',
                'Rechnung Stadtwerke',
                'Pflanzen gießen',
                'Fahrrad aufpumpen',
            ]),
            'due_on' => null,
            'completed_at' => null,
        ];
    }

    public function dueOn(string $date): static
    {
        return $this->state(fn () => ['due_on' => $date]);
    }

    public function done(): static
    {
        return $this->state(fn () => ['completed_at' => now()]);
    }
}
