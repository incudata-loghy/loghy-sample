<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class LoghyHistoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'type' => $this->faker->sentence(),
            'request_data' => json_encode(['req' => ['foo' => 'bar']]),
            'response_data' => json_encode(['res' => ['foo' => 'bar']]),
        ];
    }
}
