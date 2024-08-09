<?php

namespace Database\Seeders;

use Carbon\Carbon;
use App\Models\Item;
use App\Models\Team;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Customer;
use App\Models\TeamSetting;
use Faker\Factory as Faker;
use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;
use App\Models\RecurringInvoice;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class RecurringInvoiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
      
        for ($i = 0; $i < 30; $i++) {
            $faker = Faker::create();
            $team_id = $faker->randomElement(Team::all()->pluck('id')->toArray());
            $customer_id = $faker->randomElement(Customer::where('team_id', $team_id)->pluck('id')->toArray());
        
            $recurring_invoice = RecurringInvoice::create([
                'team_id' => $team_id ,
                'customer_id' => $customer_id ,
                'numbering' => null ,
                'summary' =>  $faker->sentence ,
                'start_date' => Carbon::createFromTimestamp(rand(strtotime('2020-01-01'), 
                strtotime(date('Y-m-d', strtotime("next year January 1 - 1 day"))))) ,
                'stop_date' => null ,
                'every' => $faker->randomElement([
                    'One Time',
                    'Daily',
                    'Monthly',
                    'Yearly',
                ]) ,
                'generate_before' => $faker->numberBetween(0, 5) * $faker->numberBetween(0, 5),
                'status' => $faker->boolean ,
                'terms_conditions' => $faker->sentence,
                'footer' => $faker->sentence,
                'attachments' => null ,
               
            ]);
            $lastid = RecurringInvoice::where('team_id', $recurring_invoice->team_id)->count('id') ;
        
            $team_setting = TeamSetting::where('team_id', $recurring_invoice->team_id )->first();
            $team_setting = TeamSetting::firstOrCreate(
                ['team_id' =>  $recurring_invoice->team_id ],
                ['recurring_invoice_current_no' => 0]
            );
            $recurring_invoice_current_no = $team_setting?->recurring_invoice_current_no ?? '0' ;
            $team_setting->recurring_invoice_current_no = $recurring_invoice_current_no + 1 ;
            $team_setting->save();
            $numbering = str_pad($recurring_invoice_current_no + 1, 6, "0", STR_PAD_LEFT) ;
            $recurring_invoice->update(['numbering' => $numbering]);

            //generate invoice recurring
            $get_recurring_every = $recurring_invoice->every;
            $get_recurring_start = $recurring_invoice->start_date;
            $gen_recur = $faker->numberBetween(1, 5) ;
           

            for($j = 0 ; $j < $gen_recur ; $j++) {
                $lastid = Invoice::where('team_id', $recurring_invoice->team_id)->count('id') ;
                $team_setting = TeamSetting::where('team_id', $recurring_invoice->team_id )->first();
                $team_setting = TeamSetting::firstOrCreate(
                    ['team_id' =>  $recurring_invoice->team_id ],
                    ['invoice_current_no' => 0]
                );
                $invoice_current_no = $team_setting?->invoice_current_no ?? '0' ;
                $team_setting->invoice_current_no = $invoice_current_no + 1 ;
                $team_setting->save();
                $numbering = str_pad($invoice_current_no + 1, 6, "0", STR_PAD_LEFT) ;
               
                if($j == 0){

                    $invoice = Invoice::create([
                        'customer_id' => $recurring_invoice->customer_id ,
                        'team_id' => $recurring_invoice->team_id ,
                        'numbering' => $numbering, // Assuming unique numbering format
                        'invoice_date' => $get_recurring_start,
                        'pay_before' => $get_recurring_start, // Valid days between 7 and 30
                        'invoice_status' => $faker->randomElement([
                            'draft',
                            'new',
                            'process',
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
                        'recurring_invoice_id' => $recurring_invoice->id,
                        'terms_conditions' => $faker->sentence,
                        'footer' => $faker->sentence,
                        'attachments' => null,
                    ]);
    
                    $itemPrice = $faker->numberBetween(1, 100) ;
                    $itemQty = $faker->numberBetween(1, 5);
                    $itemTotal = $itemPrice * $itemQty;
                    $item = Item::create([
                        'invoice_id' => $invoice->id,
                        'product_id' => null,
                        'title' => $faker->sentence(3),
                        'price' => $itemPrice,
                        'tax' => $faker->numberBetween(0, 1),
                        'quantity' => $itemQty,
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
                        'total' => $itemTotal,
                    ]);
    
                    $taxes = 0 ;
                    if($item->tax){
                        $taxes = $invoice->percentage_tax / 100 * $itemTotal;
                    }
                    $final_amount = ($itemTotal + $taxes + $invoice->delivery);
                    $invoice->update([
                        'sub_total' => $itemTotal,
                        'taxes' => $taxes,
                        'final_amount' => $final_amount,
                        'balance' => $final_amount,
                    
                    ]);
                    
                }else{
                    $invoice = Invoice::create([
                        'customer_id' => $recurring_invoice->customer_id ,
                        'team_id' => $recurring_invoice->team_id ,
                        'numbering' => $numbering, // Assuming unique numbering format
                        'invoice_date' => $get_recurring_start,
                        'pay_before' => $get_recurring_start, // Valid days between 7 and 30
                        'invoice_status' => $faker->randomElement([
                            'draft',
                            'new',
                            'process',
                            'done',
                            'expired',
                            'cancelled',
                        ]),
                        'summary' => $faker->sentence,
                        'sub_total' => $invoice->sub_total, // Subtotal between 1000 and 10000
                        'taxes' => $invoice->taxes, // Can be calculated based on percentage_tax and sub_total later
                        'percentage_tax' => $invoice->percentage_tax, // Tax percentage between 0 and 20
                        'delivery' => $invoice->delivery, // Delivery cost between 0 and 100
                        'final_amount' => $invoice->final_amount, //
                        'balance' => $invoice->balance, //
                        'recurring_invoice_id' => $recurring_invoice->id,
                        'terms_conditions' => $faker->sentence,
                        'footer' => $faker->sentence,
                        'attachments' => null,
                    ]);

                    $item = Item::create([
                        'invoice_id' => $invoice->id,
                        'product_id' => null,
                        'title' => $item->title,
                        'price' => $item->price,
                        'tax' => $item->tax,
                        'quantity' => $item->quantity,
                        'unit' => $item->unit,
                        'total' => $item->total,
                    ]);

                }
                $payment_method = PaymentMethod::where('team_id', $invoice->team_id)->first();
                if($invoice->invoice_status == 'process'){
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


                $recurring_invoice->update(['stop_date' => $get_recurring_start]);
                if($get_recurring_every == 'One Time') {
                    break;
                }elseif($get_recurring_every == 'Daily'){
                    if($get_recurring_start > date('Y-m-d')){
                        break;
                    }
                    $newDate = strtotime("+1 day", strtotime($get_recurring_start)); // Add 1 month to timestamp
                   
                    $get_recurring_start = date("Y-m-d", $newDate); // Update the startDate for next iteration
    
                }elseif($get_recurring_every == 'Monthly'){
                    if($get_recurring_start > date('Y-m-d')){
                        break;
                    }
                    $newDate = strtotime("+1 month", strtotime($get_recurring_start)); // Add 1 month to timestamp
                   
                    $get_recurring_start = date("Y-m-d", $newDate); // Update the startDate for next iteration

                }elseif($get_recurring_every == 'Yearly'){
                    if($get_recurring_start > date('Y-m-d')){
                        break;
                    }
                  $newDate = strtotime("+1 year", strtotime($get_recurring_start)); // Add 1 month to timestamp
                   
                    $get_recurring_start = date("Y-m-d", $newDate); // Update the startDate for next iteration
    

                }else{
                    break;
                }
               

            }

        }
    }
}
