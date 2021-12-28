<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Customer;

class LocationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            //
            'address' => $this->faker->address(), 
            'city' => $this->faker->city(), 
            'state' => $this->faker->state(), 
            'zip' => $this->faker->postcode(),
            'customer_id' => Customer::factory(),
        ];
    }
}
