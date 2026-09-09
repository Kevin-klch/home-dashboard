<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\ShoppingItem>
 */
class ShoppingItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Milch', 'Brot', 'Tomaten', 'Kaffee', 'Butter', 'Eier']),
            'note' => null,
            'completed_at' => null,
        ];
    }

    public function done(): static
    {
        return $this->state(fn () => ['completed_at' => now()]);
    }
}
