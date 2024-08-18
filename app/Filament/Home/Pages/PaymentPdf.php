<?php

namespace App\Filament\Home\Pages;

use App\Models\Team;
use App\Models\Invoice;
use App\Models\Payment;
use Filament\Forms\Get;
use Filament\Forms\Set;
use App\Models\Customer;
use Filament\Pages\Page;
use App\Models\TeamSetting;
use Filament\Actions\Action;
use App\Models\PaymentMethod;
use Filament\Actions\StaticAction;
use Illuminate\Contracts\View\View;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\FileUpload;

class PaymentPdf extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $slug = 'payment-pdf/{id}';
    protected static string $view = 'filament.home.pages.payment-pdf';
    protected static bool $shouldRegisterNavigation = false;

    public $payment;
    public $id ; 

    public function getLayout(): string
    {
        return 'filament-panels::components.layout.base';
    }

    public function mount($id = null)
    {
        $id = str_replace('luqmanahmadnordin', "", base64_decode($id));
        $this->id = $id;
    }

    public function render(): View
    {
        $payment = Payment::find($this->id);
        $team = Team::where('id', $payment->team_id)->first();
        $team_setting = TeamSetting::where('team_id', $payment->team_id)->first();
        $this->payment = $payment->toArray();
        if($payment->invoice_id){
            $invoice = Invoice::find($payment->invoice_id);
            $customer = Customer::where('id', $invoice->customer_id)->first();
            $this->payment['invoices'] =  $invoice->toArray();
            $this->payment['address'] =  $team->address;
            $this->payment['poscode'] = $team->poscode;
            $this->payment['city'] = $team->city;
            $this->payment['state'] = $team->state;
            $this->payment['to_address'] = $customer->address;
            $this->payment['to_poscode'] = $customer->poscode;
            $this->payment['to_city'] = $customer->city;
            $this->payment['to_state'] = $customer->state;
            $this->payment['invoice_prefix'] = $team_setting->invoice_prefix_code ?? '#I';
        }

        $this->payment['logo'] =  $team->photo;
        $this->payment['payment_method'] = PaymentMethod::where('status', 1)->where('team_id', $payment->team_id)->pluck('bank_name', 'id')->toArray();
        
      
        // $this->payment['recurring_invoice_prefix'] = $team_setting->recurring_invoice_prefix_code ?? '#RI';

        // $this->payment['final_amount'] = collect($this->payment['invoices'])->sum('final_amount');
        // $this->payment['payments'] = $recurring_invoice->payments->toArray() ;
        // $this->payment['payments_total'] = $recurring_invoice->payments->whereIn('status', ['new' ,'process','done' ,'expired'])->sum('total') ;
        // $this->payment['total_balance'] = collect($this->payment['invoices'])->sum('balance');
        
        // // dd($this->payment);

        return view($this->getView(), $this->getViewData())
            ->layout($this->getLayout(), [
                'livewire' => $this,
                'maxContentWidth' => $this->getMaxContentWidth(),
                ...$this->getLayoutData(),
            ]);
    }


    public function printAction(): Action
    {
        return Action::make('print')
            ->button()
            ->url('#')
            ->icon('heroicon-m-printer')
            ->color('info')
            ->extraAttributes(['class' => 'printme']);
    }



}
