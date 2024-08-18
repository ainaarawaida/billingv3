<?php

namespace App\Filament\Home\Pages;

use App\Models\Item;
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
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\FileUpload;
use Illuminate\Contracts\View\View;

class InvoicePdf extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $slug = 'invoice-pdf/{id}';
    protected static string $view = 'filament.home.pages.invoice-pdf';
    protected static bool $shouldRegisterNavigation = false;

    public $invoice;
    public $id;
    public $items = null;

    public function getLayout(): string
    {
        return 'filament-panels::components.layout.base';
    }

    public function mount($id = null)
    {
        $id = str_replace('luqmanahmadnordin', "", base64_decode($id));
        $this->id = $id;
        $this->updateInfo($id);
    }

    function updateInfo($invoice_id){
        $invoice = Invoice::find($invoice_id);
        $customer = Customer::where('id', $invoice->customer_id)->first();
        $team = Team::where('id', $invoice->team_id)->first();
        $team_setting = TeamSetting::where('team_id', $invoice->team_id)->first();
        $invoice->logo = $team->photo;
        $invoice->address = $team->address;
        $invoice->poscode = $team->poscode;
        $invoice->city = $team->city;
        $invoice->state = $team->state;
        $invoice->to_address = $customer->address;
        $invoice->to_poscode = $customer->poscode;
        $invoice->to_city = $customer->city;
        $invoice->to_state = $customer->state;
        $invoice->prefix = $team_setting->invoice_prefix_code ?? '#I';
        $invoice->payment = Payment::where('invoice_id', $invoice->id)->get()->toArray();
        $invoice->payment_method = PaymentMethod::where('status', 1)->where('team_id', $invoice->team_id)->pluck('bank_name', 'id')->toArray();
        $invoice->totalPayment = Invoice::find($invoice->id)->getTotalPayment();

        $this->items = Item::with('product')->where('invoice_id', $invoice->id)->get();
        $this->invoice = (object)$invoice->toArray();


    }

    public function render(): View
    {
        $this->updateInfo($this->id);
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

    public function paymentAction(): Action
    {
        return Action::make('payment')
            ->button()
            ->icon('heroicon-m-credit-card')
            ->color('info')
            ->form([
                Select::make('payment_method_id')
                    ->label('Payment Method')
                    ->options(function ($state, Set $set) {
                        $payment_method = PaymentMethod::where('team_id', $this->invoice->team_id)
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
                    $this->replaceMountedAction('paymentgatewaypay', (array)$payment_method);
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
                        ->default($this->invoice->final_amount)
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
