<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {

        $faker = \Faker\Factory::create('hu_HU');

        $firstName = $faker->firstName;
        $lastName = $faker->lastName;
        $randomNumber = rand(10, 99);

        return [
            'name' => $firstName . ' ' . $lastName,
            'email' => strtolower(Str::ascii($firstName) . '.' . Str::ascii($lastName)) . $randomNumber .'@events.hu',
            'phone' => $faker->numerify('+36## ### ####'),
            //'is_admin' => false,
            'email_verified_at' => now(),
            'password' => bcrypt('jelszo123'), 
            'remember_token' => Str::random(10),
        ];
    }
}