<?php

namespace Database\Seeders;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Faker\Factory as Faker;

class PaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();
        //
        // dd("start");
        $invoice = Invoice::where('invoice_status', 'new')->get();

        foreach($invoice AS $key => $val){
            $payment = Payment::create([
                'team_id' => $val->team_id,
                'invoice_id' => $val->id,
                'recurring_invoice_id' => $val->recurring_invoice_id,
                'payment_method_id' => $val->team_id,
                'payment_date' => $val->pay_before,
                'total' => $val->balance,
                'notes' => $faker->sentence,
                'reference' => $faker->randomNumber(7, true),
                'status' => $faker->randomElement([
                    'completed',
                    'pending_payment','on_hold','processing'
                ]),
                'attachments' => null,
            ]);

            if($payment->status == 'completed'){
                $val->balance = 0 ;
                $val->invoice_status = 'paid';
            }
            $val->save();
        }
    }
}
