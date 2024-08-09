<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Models\Customer;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
            for($a = 0 ; $a < 30; $a++){
                $faker = Faker::create();
                Customer::create([
                    //
                    'name' => $faker->name,
                    'email' => $faker->unique()->safeEmail,
                    'phone' => str_pad("01" . $faker->numerify('########'), 10, '0', STR_PAD_LEFT),
                    'company' => $faker->company,
                    'ssm' => $faker->randomNumber(7, true), // Assuming SSM is a 7-digit number
                    'address' => $faker->address,
                    'poscode' => $faker->postcode,
                    'city' => $faker->city,
                    'state' => $faker->randomElement([
                        'JHR',
                        'KDH',
                        'KTN',
                        'MLK',
                        'NSN',
                        'PHG',
                        'PRK',
                        'PLS',
                        'PNG',
                        'SBH',
                        'SWK',
                        'SGR',
                        'TRG',
                        'KUL',
                        'LBN',
                        'PJY'
                    ]),
                    'team_id' => $faker->randomElement(Team::all()->pluck('id')->toArray()), // Set to null for
                ]);

            }
        
    }
}
