<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\Team;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Quotation;
use App\Models\TeamSetting;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class QuotationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
       

        
        //
        // Quotation::factory()->count(20)->create();
        for ($i = 0; $i < 30; $i++) {
            $faker = Faker::create();
            $team_id = $faker->randomElement(Team::all()->pluck('id')->toArray());
            $customer_id = $faker->randomElement(Customer::where('team_id', $team_id)->pluck('id')->toArray());
        
            $quotation = Quotation::create([
                'customer_id' => $customer_id ,
                'team_id' => $team_id ,
                'numbering' => null, // Assuming unique numbering format
                'quotation_date' => $faker->date(),
                'valid_days' => $faker->numberBetween(7, 30), // Valid days between 7 and 30
                'quote_status' => $faker->randomElement([
                    'draft',
                    'new',
                    'processing',
                    'done',
                    'expired',
                    'cancelled',
                ]),
                'summary' => $faker->sentence,
                'sub_total' => null, // Subtotal between 1000 and 10000
                'taxes' => null, // Can be calculated based on percentage_tax and sub_total later
                'percentage_tax' => $faker->numberBetween(0, 20), // Tax percentage between 0 and 20
                'delivery' => $faker->randomFloat(2, 0, 100), // Delivery cost between 0 and 100
                'final_amount' => null, //
                'terms_conditions' => $faker->sentence,
                'footer' => $faker->sentence,
            ]);
            $lastid = Quotation::where('team_id', $quotation->team_id)->count('id') ;
            $numbering = str_pad($lastid, 6, "0", STR_PAD_LEFT) ;
            $team_setting = TeamSetting::where('team_id', $quotation->team_id )->first();
            $team_setting = TeamSetting::firstOrCreate(
                ['team_id' =>  $quotation->team_id ],
                ['quotation_current_no' => 0]
            );
            $quotation_current_no = $team_setting?->quotation_current_no ?? '0' ;
            $team_setting->quotation_current_no = $quotation_current_no + 1 ;
            $team_setting->save();


            $quotation->update(['numbering' => $numbering]);
            echo $lastid. " ".$numbering . PHP_EOL;


            //item
            $itemlist = $faker->numberBetween(1, 5) ;
            $final_amount = 0 ;
            $sub_total = 0 ;
            $taxes = 0 ;

            $product = Product::where('team_id', $quotation->team_id)
            ->inRandomOrder()
            ->take($itemlist)
            ->get();

            foreach($product as $key => $val) {

                $total = $val->price * $val->quantity;

                $item = Item::create([
                    'quotation_id' => $quotation->id,
                    'product_id' => $val->id,
                    'title' => $val->title,
                    'price' => $val->price,
                    'tax' => $val->tax,
                    'quantity' => $val->quantity,
                    'unit' => $faker->randomElement([
                        'Unit' => 'Unit',
                        'Kg' => 'Kg',
                        'Gram' => 'Gram',
                        'Box' => 'Box',
                        'Pack' => 'Pack',
                        'Day' => 'Day',
                        'Month' => 'Month',
                        'Year' => 'Year',
                        'People' => 'People',
                    ]),
                    'total' => $total,
                ]);

                $sub_total = $sub_total + $total;
                if($val->tax){
                    $taxes = $taxes + ($quotation->percentage_tax / 100 * $total);

                }
                
            }

            $final_amount = ($sub_total + $taxes + $quotation->delivery);

            $quotation->update([
                'sub_total' => $sub_total,
                'taxes' => $taxes,
                'final_amount' => $final_amount,
            
            ]);
        }
    }
}
