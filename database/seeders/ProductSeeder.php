<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Models\Product;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //

            for($a = 0 ; $a < 30; $a++){
                $faker = Faker::create();
                Product::create([
                    //
                    'title' => $faker->sentence(3), // Generate a 3-word sentence for title
                    'team_id' => $faker->randomElement(Team::all()->pluck('id')->toArray()), // Set to null for now (can be overridden later)
                    'tax' => $faker->numberBetween(0, 1), // Generate random tax between 0 and 20 (2 decimal places)
                    'quantity' => $faker->numberBetween(1, 5), // Generate random quantity between 0 and 100
                    'price' => $faker->numberBetween(1, 100), // Generate random 5-digit price (assuming whole numbers)
                ]);

            }
    }
}
