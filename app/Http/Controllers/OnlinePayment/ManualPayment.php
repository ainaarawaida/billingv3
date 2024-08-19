<?php

namespace App\Http\Controllers\OnlinePayment;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\TeamSetting;
use Illuminate\Http\Request;
use App\Models\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Models\RecurringInvoice;
use Illuminate\Support\Facades\Storage;

class ManualPayment extends Controller
{
    //
    function index($id, $payment_method_id){
        $hashid = $id;
        $id = str_replace('luqmanahmadnordin', "", base64_decode($id));
        $invoice = Invoice::find($id);
        $record = $invoice ;

        $validatedData = request()->validate([
            'attachments' => 'required|file|mimes:pdf,jpg,jpeg,png|max:9048',
            'amount' => 'required|numeric', // Adjust validation rules as needed
        ]);
        $fileName = time() . '.' . request()->file('attachments')->getClientOriginalExtension();
        $path = [Storage::disk('public')->put('payment-attachments', request()->file('attachments'))] ;

        $payment = Payment::Create(
            [
                'team_id' => $record->team_id,
                'invoice_id' => $record->id,
                'payment_method_id' => $payment_method_id,
                'payment_date' => date('Y-m-d'),
                'total' => request()->post('amount'),
                'notes' => request()->post('notes'),
                'reference' => request()->post('reference'),
                'status' => 'processing',
                'attachments' => $path ,
            ]
        );
        $record->invoice_status = 'processing';
        $record->save();

       

        return redirect('/invoicepdf/'.$hashid.'/'.$payment_method_id.'/?payment_id='.base64_encode('luqmanahmadnordin' . $payment->id))->with(['message' => 'Success Manual Payment', 'payment' => $payment ]);
    }

    function recurring($id, $payment_method_id){
        $hashid = $id;
        $id = str_replace('luqmanahmadnordin', "", base64_decode($id));
        $recurring_invoice = RecurringInvoice::where('id', $id)->first(); 
        $invoice_id_all = json_decode(base64_decode(request()->post('id-all'))) ;
        $invoice = Invoice::whereIn('id',$invoice_id_all)->get();


        $validatedData = request()->validate([
            'attachments' => 'required|file|mimes:pdf,jpg,jpeg,png|max:9048',
            'amount' => 'required|numeric', // Adjust validation rules as needed
        ]);
        $fileName = time() . '.' . request()->file('attachments')->getClientOriginalExtension();
        $path = [Storage::disk('public')->put('payment-attachments', request()->file('attachments'))] ;
        // dd("tak siap");
        $total_payment = request()->post('amount') ; 
        $payment_collection = [];
        $lastInvoice = count($invoice)-1;
        foreach($invoice AS $key => $val){
            if($total_payment >= $val->balance ){
                if($key == $lastInvoice){
                    $total = $total_payment ;
                }else{
                    $total = $val->balance ;  
                }
            }else if($total_payment >= 0 && $total_payment < $val->balance){
                $total = $total_payment ;
            }else{
               continue ;
            }
            $payment = Payment::Create(
                [
                    'team_id' => $recurring_invoice->team_id,
                    'invoice_id' => $val->id,
                    'recurring_invoice_id' => $id,
                    'payment_method_id' => $payment_method_id,
                    'payment_date' => date('Y-m-d'),
                    'total' =>  $total,
                    'notes' => 'recurring '.request()->post('notes'),
                    'reference' => request()->post('reference'),
                    'status' => 'processing',
                    'attachments' => $path ,
                ]
            );
            $payment_collection[] = $payment->toArray() ;
            $val->invoice_status = 'processing';
            $val->save();

            $total_payment = $total_payment - $val->balance ;

        }

        return redirect('/recurringInvoicepdf/'.$hashid.'/?payment_method_id='.$payment_method_id.'&payment_id='.base64_encode('luqmanahmadnordin' . json_encode($payment_collection)))->with(['message' => 'Success Manual Payment', 'payment' => $payment ]);
    }
}
