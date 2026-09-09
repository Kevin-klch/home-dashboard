<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Note>
 */
class NoteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'body' => fake()->randomElement([
                'WLAN-Passwort Gäste steht im Flurschrank',
                'Ersatzschlüssel bei Familie Weber',
                'Heizung Wartungstermin im Oktober vereinbaren',
            ]),
            'pinned_at' => null,
        ];
    }

    public function pinned(): static
    {
        return $this->state(fn () => ['pinned_at' => now()]);
    }
}
