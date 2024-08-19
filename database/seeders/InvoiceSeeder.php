<?php

namespace Database\Seeders;

use Carbon\Carbon;
use App\Models\Item;
use App\Models\Team;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Customer;
use App\Models\TeamSetting;
use Faker\Factory as Faker;
use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class InvoiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
    

        
        //
        // Invoice::factory()->count(20)->create();
        for ($i = 0; $i < 100; $i++) {
            $faker = Faker::create();
            $team_id = $faker->randomElement(Team::all()->pluck('id')->toArray());
            $customer_id = $faker->randomElement(Customer::where('team_id', $team_id)->pluck('id')->toArray());
        
            $invoice_date = Carbon::createFromTimestamp(rand(strtotime('2020-01-01'), 
            strtotime(date('Y-m-d', strtotime("next year January 1 - 1 day"))))) ;
            
            $pay_before = Carbon::parse($invoice_date)->addDays($faker->numberBetween(7, 30));

            $invoice = Invoice::create([
                'customer_id' => $customer_id ,
                'team_id' => $team_id ,
                'numbering' => null, // Assuming unique numbering format
                'invoice_date' => $invoice_date,
                'pay_before' => $pay_before, // Valid days between 7 and 30
                'invoice_status' => $faker->randomElement([
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
                'balance' => null, //
                'recurring_invoice_id' => null,
                'terms_conditions' => $faker->sentence,
                'footer' => $faker->sentence,
                'attachments' => null,
            ]);
            $lastid = Invoice::where('team_id', $invoice->team_id)->count('id') ;
            $numbering = str_pad($lastid, 6, "0", STR_PAD_LEFT) ;
            $team_setting = TeamSetting::where('team_id', $invoice->team_id )->first();
            $invoice_current_no = $team_setting->invoice_current_no ?? '0' ;
            $team_setting->invoice_current_no = $invoice_current_no + 1 ;
            $team_setting->save();
            
            $invoice->update(['numbering' => $numbering]);
            echo $lastid. " ".$numbering . PHP_EOL;


            //item
            $itemlist = $faker->numberBetween(1, 5) ;
            $final_amount = 0 ;
            $sub_total = 0 ;
            $taxes = 0 ;

            $product = Product::where('team_id', $invoice->team_id)
            ->inRandomOrder()
            ->take($itemlist)
            ->get();


            foreach($product as $key => $val) {

                $total = $val->price * $val->quantity;

                $item = Item::create([
                    'invoice_id' => $invoice->id,
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
                    $taxes = $taxes + ($invoice->percentage_tax / 100 * $total);

                }



            }

            $final_amount = ($sub_total + $taxes + $invoice->delivery);

            $invoice->update([
                'sub_total' => $sub_total,
                'taxes' => $taxes,
                'final_amount' => $final_amount,
                'balance' => $final_amount,
            
            ]);

           
            $payment_method = PaymentMethod::where('team_id', $invoice->team_id)->first();
            if($invoice->invoice_status == 'processing'){
                $payment = Payment::Create(
                    [
                        'team_id' => $invoice->team_id,
                        'invoice_id' => $invoice->id,
                        'payment_method_id' => $payment_method->id,
                        'payment_date' => date('Y-m-d'),
                        'total' => $invoice->balance,
                        'notes' => $faker->sentence,
                        'reference' => $faker->randomNumber(8, true),
                        'status' => 'processing',
                        'attachments' => null ,
                    ]
                );
               

            }elseif($invoice->invoice_status == 'done'){
                $payment = Payment::Create(
                    [
                        'team_id' => $invoice->team_id,
                        'invoice_id' => $invoice->id,
                        'payment_method_id' => $payment_method->id,
                        'payment_date' => date('Y-m-d'),
                        'total' => $invoice->balance,
                        'notes' => $faker->sentence,
                        'reference' => $faker->randomNumber(8, true),
                        'status' => 'completed',
                        'attachments' => null ,
                    ]
                );
                $invoice->balance = 0;
                $invoice->save();
        
                
            }
           



        }
    }
}
