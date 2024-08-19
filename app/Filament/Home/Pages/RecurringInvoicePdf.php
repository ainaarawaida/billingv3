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
use App\Models\RecurringInvoice;
use Filament\Actions\StaticAction;
use Illuminate\Contracts\View\View;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\FileUpload;
use App\Http\Controllers\OnlinePayment\Toyyibpay;

class RecurringInvoicePdf extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $slug = 'recurring-invoice-pdf/{id}';
    protected static string $view = 'filament.home.pages.recurring-invoice-pdf';
    protected static bool $shouldRegisterNavigation = false;

    public $id;
    public $recurring_invoice;

    public function mount($id = null)
    {
        $id = str_replace('luqmanahmadnordin', "", base64_decode($id));
        $this->id = $id;
    }


    public function render(): View
    {
        $recurring_invoice = RecurringInvoice::find($this->id);
        $team_setting = TeamSetting::where('team_id', $recurring_invoice->team_id)->first();
        $customer = Customer::where('id', $recurring_invoice->customer_id)->first();
        $team = Team::where('id', $recurring_invoice->team_id)->first();


        $this->recurring_invoice = $recurring_invoice->toArray();
        $this->recurring_invoice['invoices'] = $recurring_invoice->invoices->whereIn('invoice_status', ['new', 'processing', 'done', 'expired'])->toArray();
        $this->recurring_invoice['logo'] =  $team->photo;
        $this->recurring_invoice['address'] =  $team->address;
        $this->recurring_invoice['poscode'] = $team->poscode;
        $this->recurring_invoice['city'] = $team->city;
        $this->recurring_invoice['state'] = $team->state;
        $this->recurring_invoice['to_address'] = $customer->address;
        $this->recurring_invoice['to_poscode'] = $customer->poscode;
        $this->recurring_invoice['to_city'] = $customer->city;
        $this->recurring_invoice['to_state'] = $customer->state;
        $this->recurring_invoice['invoice_prefix'] = $team_setting->invoice_prefix_code ?? '#I';
        $this->recurring_invoice['recurring_invoice_prefix'] = $team_setting->recurring_invoice_prefix_code ?? '#RI';

        $this->recurring_invoice['final_amount'] = collect($this->recurring_invoice['invoices'])->sum('final_amount');
        $this->recurring_invoice['payments'] = $recurring_invoice->payments->toArray();
        $this->recurring_invoice['payments_total'] = $recurring_invoice->payments->whereIn('status', ['new', 'processing', 'done', 'expired'])->sum('total');
        $this->recurring_invoice['total_balance'] = collect($this->recurring_invoice['invoices'])->sum('balance');

        $this->recurring_invoice['payment_method'] = PaymentMethod::where('status', 1)->where('team_id', $recurring_invoice->team_id)->pluck('bank_name', 'id')->toArray();
        // dd($this->recurring_invoice);

        return view($this->getView(), $this->getViewData())
            ->layout($this->getLayout(), [
                'livewire' => $this,
                'maxContentWidth' => $this->getMaxContentWidth(),
                ...$this->getLayoutData(),
            ]);
    }

    public function getLayout(): string
    {
        return 'filament-panels::components.layout.base';
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

    public function paymentAction(): Action
    {
        return Action::make('payment')
            ->button()
            ->hidden(fn() => collect($this->recurring_invoice['invoices'])->where('invoice_status', '!=', 'new')->first())
            ->icon('heroicon-m-credit-card')
            ->color('info')
            ->form([
                Select::make('payment_method_id')
                    ->label('Payment Method')
                    ->options(function ($state, Set $set) {
                        $payment_method = PaymentMethod::where('team_id', $this->recurring_invoice['team_id'])
                            ->where('status', 1);
                        $set('payment_method_details', json_encode($payment_method->get()->toArray()));
                        return $payment_method->pluck('bank_name', 'id');
                    })
                    ->required(),
                Hidden::make('payment_method_details')
            ])
            ->action(function (array $data, array $arguments, Get $get): void {
                $payment_method_details = json_decode($data['payment_method_details']);
                $payment_method = collect($payment_method_details)->where('id', $data['payment_method_id'])->first();
                if ($payment_method->type == 'manual') {
                    $this->replaceMountedAction('manualpay', (array)$payment_method);
                } else {
                    if ($payment_method->payment_gateway_id == 2) {
                        $toyyibpay = (new Toyyibpay())->recurring($this->id, $data['payment_method_id']);
                    } else {
                        $this->replaceMountedAction('paymentgatewaypay', (array)$payment_method);
                    }
                }
            })
            ->modalSubmitAction(fn(StaticAction $action) => $action->label('Proceed'))
            ->modalCancelAction(fn(StaticAction $action) => $action->label('Close'));
    }

    public function manualpayAction(): Action
    {
        return Action::make('manualpay')
            ->label('Manual Payment')
            ->button()
            ->icon('heroicon-m-credit-card')
            ->color('info')
            ->form(function (array $arguments) {
                // dd($this->invoice);
                return [
                    TextInput::make('bank_name')
                        ->default($arguments['bank_name'])
                        ->readonly(),
                    TextInput::make('account_name')
                        ->default($arguments['account_name'])
                        ->readonly(),
                    TextInput::make('bank_account')
                        ->default($arguments['bank_account'])
                        ->readonly(),
                    TextInput::make('amount')
                        ->default($this->recurring_invoice['final_amount'])
                        ->readonly()
                        ->numeric()
                        ->required(),
                    TextInput::make('reference')
                        ->required(),
                    Textarea::make('notes')
                        ->required(),
                    FileUpload::make('attachments')
                        ->required()
                        ->label('Receipt (docx,pdf,jpg,jpeg,png )')
                ];
            })
            ->action(function (array $data, array $arguments): void {
                foreach ($this->recurring_invoice['invoices']  as $k => $v) {
                    $payment = Payment::create([
                        'team_id' => $this->recurring_invoice['team_id'],
                        'invoice_id' => $v['id'],
                        'recurring_invoice_id' => $this->recurring_invoice['id'],
                        'payment_method_id' => $arguments['id'],
                        'payment_date' => date('Y-m-d'),
                        'total' => $v['balance'],
                        'notes' => $data['notes'],
                        'reference' => $data['reference'],
                        'status' => 'processing',
                        'attachments' => $data['attachments'],
                    ]);
                    $invoice = Invoice::find($v['id']);
                    $invoice->update([
                        'invoice_status' => 'processing',
                    ]);
                }
                Notification::make()
                    ->title('Payment successfully')
                    ->success()
                    ->send();
            })
            ->modalSubmitAction(fn(StaticAction $action) => $action->label('Proceed'))
            ->modalCancelAction(fn(StaticAction $action) => $action->label('Close'));
    }

    public function paymentgatewaypay(): Action
    {
        return Action::make('paymentgatewaypay')
            ->label('Payment Gateway')
            ->button()
            ->icon('heroicon-m-credit-card')
            ->color('info')
            ->form([
                Select::make('payment_method_id')
                    ->label('Payment Method')
                    ->options(PaymentMethod::where('team_id', $this->invoice->team_id)
                        ->where('status', 1)->pluck('bank_name', 'id')->toArray())
                    ->required(),
            ])
            ->action(function (array $data, array $arguments): void {
                $payment = Payment::create([
                    'team_id' => $this->invoice->team_id,
                    'invoice_id' => $this->invoice->id,
                    'recurring_invoice_id' => $this->invoice->recurring_invoice_id,
                    'payment_method_id' => $arguments['id'],
                    'payment_date' => date('Y-m-d'),
                    'total' => $data['amount'],
                    'notes' => $data['notes'],
                    'reference' => $data['reference'],
                    'status' => 'processing',
                    'attachments' => $data['attachments'],
                ]);

                $invoice = Invoice::find($this->invoice->id);
                $invoice->updateBalanceInvoice();


                Notification::make()
                    ->title('Payment successfully')
                    ->success()
                    ->send();
            })
            ->modalSubmitAction(fn(StaticAction $action) => $action->label('Proceed'))
            ->modalCancelAction(fn(StaticAction $action) => $action->label('Close'));
    }
}
