<?php

namespace App\Filament\Sys\Resources\InvoiceResource\Pages;

use Filament\Actions;
use App\Models\Payment;
use Filament\Forms\Get;
use Livewire\Attributes\On;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Sys\Resources\InvoiceResource;


class EditInvoice extends EditRecord
{
    
    protected static string $resource = InvoiceResource::class;
    // public $customer_id ;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl;
        // return $this->getResource()::getUrl('index');
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        //update balance 
        $totalPayment = Payment::where('team_id', Filament::getTenant()->id)
        ->where('invoice_id', $record->id)
        ->where('status', 'completed')->sum('total');
        $totalRefunded = Payment::where('team_id', Filament::getTenant()->id)
        ->where('invoice_id', $record->id)
        ->where('status', 'refunded')->sum('total');

        $data['balance'] = $data['final_amount'] - $totalPayment + $totalRefunded;
        if($data['balance'] == 0){
            $data['invoice_status'] = 'done';
        }
      
        $record->update($data);
        return $record;
    }

    protected function getSaveFormAction(): Action
    {
        return Action::make('save')
            ->label(__('filament-panels::resources/pages/edit-record.form.actions.save.label'))
            ->keyBindings(['mod+s'])
            ->action(function () {
                $this->save();
            });
    }

    #[On('invoiceUpdateStatus')] 
    public function invoiceUpdateStatus($invoice)
    {
        // dd($this->data, $this->getRecord());
        $totalPayment = Payment::where('team_id', Filament::getTenant()->id)
        ->where('invoice_id', $invoice['id'])
        ->where('status', 'completed')->sum('total');
        $totalRefunded = Payment::where('team_id', Filament::getTenant()->id)
        ->where('invoice_id', $invoice['id'])
        ->where('status', 'refunded')->sum('total');

        $invoice['balance'] = $this->data['final_amount'] - $totalPayment + $totalRefunded; 
        if($invoice['balance'] == 0){
            $invoice['invoice_status'] = 'done'; 
        }elseif($invoice['invoice_status'] == 'done'){
            $invoice['invoice_status'] = 'new' ;
        }

        $this->data['invoice_status'] = $invoice['invoice_status'];
        $this->data['balance'] = $invoice['balance'];
    }

 
}
