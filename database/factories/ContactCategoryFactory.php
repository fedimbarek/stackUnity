<?php

namespace Database\Factories;

use App\Models\ContactCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ContactCategory> */
class ContactCategoryFactory extends Factory
{
    protected $model = ContactCategory::class;

    public function definition(): array
    {
        return [
            'name'  => fake()->unique()->words(2, true),
            'icon'  => fake()->randomElement([
                'bi-hospital', 'bi-shield-check', 'bi-lightning-charge',
                'bi-capsule', 'bi-fire', 'bi-droplet', 'bi-telephone',
            ]),
            'color' => fake()->hexColor(),
        ];
    }
}
