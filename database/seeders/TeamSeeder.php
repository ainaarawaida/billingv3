<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Models\User;
use App\Models\TeamSetting;
use Faker\Factory as Faker;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class TeamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
      
        //
        $user = User::all();
        foreach($user as $k => $v){
            $faker = Faker::create();
            $name = $faker->name;
            $team = Team::create([
                //
                'name' => $name,
                'slug' => Str::slug($name), // Generate a unique slug
                'email' => $faker->unique()->safeEmail,
                'phone' => str_pad("03" . $faker->numerify('########'), 10, '0', STR_PAD_LEFT),
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
                'photo' => null,
            ]);

            $team->members()->attach($v->id);
        }


        $team = Team::all() ;
        foreach($team AS $key => $val){
            $teamSetting = TeamSetting::updateOrCreate(
                ['team_id' => $val->id], // Search by email
                [
                    'quotation_prefix_code' => '#Q',
                    'quotation_current_no' => 0,
                    'quotation_template' => 1,
                    'invoice_prefix_code' => '#I',
                    'invoice_current_no' => 0,
                    'invoice_template' => 1,
                    'recurring_invoice_prefix_code' => '#RI',
                    'recurring_invoice_current_no' => 0,
                ]
            );


        }

    }
}
