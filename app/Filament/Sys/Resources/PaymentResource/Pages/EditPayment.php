<?php

namespace App\Filament\Sys\Resources\PaymentResource\Pages;

use Filament\Actions;
use App\Models\Invoice;
use App\Models\Payment;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Sys\Resources\PaymentResource;

class EditPayment extends EditRecord
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\ViewAction::make(),
            // Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        //update balance on invoice
        $needUpdateBalance = false ;
        $oriInvoice_id = $record->getOriginal('invoice_id');
        if(
            ($record->getOriginal('invoice_id') && !$record->invoice_id)
            ||
            ($record->invoice_id)
        ){
            $needUpdateBalance = true; 
        }

        $record->update($data);

        if($needUpdateBalance && $record->invoice_id){
            $totalPayment = Payment::where('team_id', Filament::getTenant()->id)
            ->where('invoice_id', $record->invoice_id)
            ->where('status', 'completed')->sum('total');
            $totalRefunded = Payment::where('team_id', Filament::getTenant()->id)
            ->where('invoice_id', $record->invoice_id)
            ->where('status', 'refunded')->sum('total');

            $invoice = Invoice::find($record->invoice_id);
            $invoice->balance = $invoice->final_amount - $totalPayment + $totalRefunded; 
            if($invoice->balance == 0){
                $invoice->invoice_status = 'paid';
            }elseif($invoice->invoice_status == 'done'){
                $invoice->invoice_status = 'new' ;
            }

            $invoice->update();
        }
        if($oriInvoice_id && $oriInvoice_id != $record->invoice_id){
            //update original invoice balance
            $totalPayment = Payment::where('team_id', Filament::getTenant()->id)
            ->where('invoice_id', $oriInvoice_id)
            ->where('status', 'completed')->sum('total');
            $totalRefunded = Payment::where('team_id', Filament::getTenant()->id)
            ->where('invoice_id', $oriInvoice_id)
            ->where('status', 'refunded')->sum('total');

            $invoice = Invoice::find($oriInvoice_id);
            $invoice->balance = $invoice->final_amount - $totalPayment + $totalRefunded; 
            if($invoice->balance == 0){
                $invoice->invoice_status = 'paid';
            }elseif($invoice->invoice_status == 'done'){
                $invoice->invoice_status = 'new' ;
            }
            $invoice->update();

        }

        return $record;
    }
    
}
