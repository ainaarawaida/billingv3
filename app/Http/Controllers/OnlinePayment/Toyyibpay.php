<?php

namespace App\Http\Controllers\OnlinePayment;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\TeamSetting;
use Illuminate\Http\Request;
use App\Models\PaymentMethod;
use App\Models\RecurringInvoice;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Filament\Notifications\Notification;

class Toyyibpay extends Controller
{
  //
  public function index($id, $payment_method_id)
  {
    $hashid = base64_encode("luqmanahmadnordin" . $id);
    $invoice = Invoice::find($id);
    $team_setting = TeamSetting::where('team_id', $invoice->team_id)->first();
    $toyyibpay_setting = $team_setting->payment_gateway['Toyyibpay'];

    $payment = Payment::create([
      'invoice_id' => $invoice->id,
      'payment_method_id' => $payment_method_id,
      'team_id' => $invoice->team_id,
      'total' => $invoice->balance,
      'payment_date' => date('Y-m-d'),
      'status' => 'processing'
    ]);

    // dd($invoice);
    $some_data = array(
      'userSecretKey' => $toyyibpay_setting['sandbox'] ? $toyyibpay_setting['tp_ToyyibPay_Sandbox_User_Secret_Key'] : $toyyibpay_setting['tp_ToyyibPay_User_Secret_Key'],
      'categoryCode' =>  $toyyibpay_setting['sandbox'] ? $toyyibpay_setting['tp_ToyyibPay_Sandbox_categoryCode'] : $toyyibpay_setting['tp_ToyyibPay_categoryCode'],
      'billName' => $team_setting->invoice_prefix_code . $invoice->numbering,
      'billDescription' => $team_setting->invoice_prefix_code . $invoice->numbering,
      'billPriceSetting' => 1,
      'billPayorInfo' => 1,
      'billAmount' => $invoice->balance * 100,
      'billReturnUrl' => url('invoice-pdf/' . $hashid . '?payment_id=' . $payment->id),
      'billCallbackUrl' => url('online-payment/toyyibpay-callback/' . $hashid . '?payment_id=' . $payment->id),
      'billExternalReferenceNo' => $payment->id,
      'billTo' => $invoice->customer->name,
      'billEmail' => $invoice->customer->email,
      'billPhone' => $invoice->customer->phone != '' ? $invoice->customer->phone : '0123456789',
      'billSplitPayment' => 0,
      'billSplitPaymentArgs' => '',
      'billPaymentChannel' => 2,
      'billContentEmail' => 'Thank you for purchasing our product!',
      'billChargeToCustomer' => isset($toyyibpay_setting['billChargeToCustomer']) && $toyyibpay_setting['billChargeToCustomer'] ? 0 : '',
      'billExpiryDate' => '',
      'billExpiryDays' => ''
    );

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_POST, 1);
    if ($toyyibpay_setting['sandbox']) {
      curl_setopt($curl, CURLOPT_URL, 'https://dev.toyyibpay.com/index.php/api/createBill');
    } else {
      curl_setopt($curl, CURLOPT_URL, 'https://toyyibpay.com/index.php/api/createBill');
    }
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $some_data);

    $result = curl_exec($curl);
    $info = curl_getinfo($curl);
    curl_close($curl);
    $obj = json_decode($result);

    if (isset($obj) && is_array($obj) && $obj[0]->BillCode) {
      if ($toyyibpay_setting['sandbox']) {
        return redirect()->away('https://dev.toyyibpay.com/' . $obj[0]->BillCode);
      } else {
        return redirect()->away('https://toyyibpay.com/' . $obj[0]->BillCode);
      }
    } else {
      Notification::make()
        ->title('Payment unsuccessfully ' . $obj->msg)
        ->danger()
        ->send();
    }
  }

  public function callback($id)
  {
    Log::build([
      'driver' => 'single',
      'path' => storage_path('logs/custom.log'),
    ])->info(json_encode($_POST));

    $hashid = $id;
    $id = str_replace('luqmanahmadnordin', "", base64_decode($id));
    $invoice = Invoice::find($id);

    if ($_POST) {
      if ($_POST['status'] == '1') {
        $status_payment = 'completed';
      } elseif ($_POST['status'] == '2') {
        $status_payment = 'processing';
      } elseif ($_POST['status'] == '3') {
        $status_payment = 'failed';
      } else {
        $status_payment = 'processing';
      }
      $payment_method = PaymentMethod::where('team_id', $invoice->team_id)
        ->where('payment_gateway_id', 2)->first();

      $payment = Payment::updateOrCreate(
        [
          'id' => $_POST['order_id'],
          'reference' => $_POST['refno'],
          'invoice_id' => $invoice->id
        ],
        [
          'team_id' => $invoice->team_id,
          'invoice_id' => $invoice->id,
          'payment_method_id' => $payment_method->id,
          'total' => $_POST['amount'],
          'notes' => 'billcode:' . $_POST['billcode'] . ' transaction id:' . $_POST['refno'],
          'reference' => $_POST['refno'],
          'status' => $status_payment,
        ]
      );
      if ($status_payment == 'completed') {
        $invoice->updateBalanceInvoice();
      }
    }
  }
  public function recurring($id, $payment_method_id)
  {
    $hashid = base64_encode("luqmanahmadnordin" . $id);
    $recurring_invoice = RecurringInvoice::find($id);
    $invoice = Invoice::where('recurring_invoice_id', $id)
    ->whereIn('invoice_status', ['new'])
    ->get();
    $team_setting = TeamSetting::where('team_id', $recurring_invoice->team_id)->first();
    $toyyibpay_setting = $team_setting->payment_gateway['Toyyibpay'];
   

    $payment_id = [];
    foreach($invoice AS $k => $v){
      $payment = Payment::create([
        'invoice_id' => $v->id,
        'payment_method_id' => $payment_method_id,
        'team_id' => $v->team_id,
        'total' => $v->balance,
        'payment_date' => date('Y-m-d'),
        'status' => 'processing'
      ]);
      $payment_id[] = $payment->id;
    }

    $some_data = array(
      'userSecretKey' => $toyyibpay_setting['sandbox'] ? $toyyibpay_setting['tp_ToyyibPay_Sandbox_User_Secret_Key'] : $toyyibpay_setting['tp_ToyyibPay_User_Secret_Key'],
      'categoryCode' =>  $toyyibpay_setting['sandbox'] ? $toyyibpay_setting['tp_ToyyibPay_Sandbox_categoryCode'] : $toyyibpay_setting['tp_ToyyibPay_categoryCode'],
      'billName' => $team_setting->recurring_invoice_prefix_code . $recurring_invoice->numbering,
      'billDescription' => $team_setting->recurring_invoice_prefix_code . $recurring_invoice->numbering,
      'billPriceSetting' => 1,
      'billPayorInfo' => 1,
      'billAmount' => $invoice->sum('balance') * 100,
      'billReturnUrl' => url('recurring-invoice-pdf/' . $hashid . '/?payment_id=' . base64_encode(json_encode($payment_id))),
      'billCallbackUrl' => url('online-payment/toyyibpay-recurring-callback/' . $hashid),
      'billExternalReferenceNo' => base64_encode(json_encode($payment_id)),
      'billTo' => $recurring_invoice->customer->name,
      'billEmail' => $recurring_invoice->customer->email,
      'billPhone' => $recurring_invoice->customer->phone != '' ? $recurring_invoice->customer->phone : '0123456789',
      'billSplitPayment' => 0,
      'billSplitPaymentArgs' => '',
      'billPaymentChannel' => 2,
      'billContentEmail' => 'Thank you for purchasing our product!',
      'billChargeToCustomer' => isset($toyyibpay_setting['billChargeToCustomer']) && $toyyibpay_setting['billChargeToCustomer'] ? 0 : '',
      'billExpiryDate' => '',
      'billExpiryDays' => ''
    );

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_POST, 1);
    if ($toyyibpay_setting['sandbox']) {
      curl_setopt($curl, CURLOPT_URL, 'https://dev.toyyibpay.com/index.php/api/createBill');
    } else {
      curl_setopt($curl, CURLOPT_URL, 'https://toyyibpay.com/index.php/api/createBill');
    }
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $some_data);

    $result = curl_exec($curl);
    $info = curl_getinfo($curl);
    curl_close($curl);
    $obj = json_decode($result);

    if (isset($obj) && is_array($obj) && $obj[0]->BillCode) {
     
      if ($toyyibpay_setting['sandbox']) {
        return redirect()->away('https://dev.toyyibpay.com/' . $obj[0]->BillCode);
      } else {
        return redirect()->away('https://toyyibpay.com/' . $obj[0]->BillCode);
      }
    } else {
      Notification::make()
      ->title('Payment unsuccessfully ' . $obj->msg)
      ->danger()
      ->send();
    }
  }

  public function recurring_callback($id)
  {
    Log::build([
      'driver' => 'single',
      'path' => storage_path('logs/custom.log'),
    ])->info(json_encode($_POST));


    $hashid = $id;
    $id = str_replace('luqmanahmadnordin', "", base64_decode($id));
    $recurring_invoice = RecurringInvoice::find($id)->first();
    $payment_id = json_decode(base64_decode($_POST['order_id']));

    if ($_POST) {
      if ($_POST['status'] == '1') {
        $status_payment = 'completed';
      } elseif ($_POST['status'] == '2') {
        $status_payment = 'processing';
      } elseif ($_POST['status'] == '3') {
        $status_payment = 'failed';
      } else {
        $status_payment = 'processing';
      }

      foreach ($payment_id as $key => $val) {
        $payment = Payment::find($val);
        $invoice = Invoice::find($payment->invoice_id);
        $payment->update([
          'notes' => 'billcode:' . $_POST['billcode'] . ' transaction id:' . $_POST['refno'],
          'reference' => $_POST['refno'],
          'status' => $status_payment,
        ]);

        if ($status_payment == 'completed') {
          $invoice->updateBalanceInvoice();
        }
      }
    }
  }

}
