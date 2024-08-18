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
            $invoice = Invoice::find($record->invoice_id);
            $invoice->updateBalanceInvoice();
            $invoice->update();
        }
        if($oriInvoice_id && $oriInvoice_id != $record->invoice_id){
            //update original invoice balance
            $invoice = Invoice::find($oriInvoice_id);
            $invoice->updateBalanceInvoice();
            $invoice->update();

        }

        return $record;
    }
    
}
