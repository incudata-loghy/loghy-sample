<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SocialIdentityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'loghy_id' => (string)($this->faker->unique()->randomNumber(9)),
            'type' => 'google',
            'sub' => '11111111111111111111',
        ];
    }
}
