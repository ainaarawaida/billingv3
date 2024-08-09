<?php

namespace App\Filament\Sys\Resources\PaymentResource\Pages;

use Filament\Actions;
use App\Models\Invoice;
use App\Models\Payment;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Sys\Resources\PaymentResource;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class CreatePayment extends CreateRecord
{
    protected static string $resource = PaymentResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Model
    {
        $record = new ($this->getModel())($data);

        if (
            static::getResource()::isScopedToTenant() &&
            ($tenant = Filament::getTenant())
        ) {
            return $this->associateRecordWithTenant($record, $tenant);
        }

        $record->save();

        return $record;
    }

    protected function associateRecordWithTenant(Model $record, Model $tenant): Model
    {
        $relationship = static::getResource()::getTenantRelationship($tenant);

        if ($relationship instanceof HasManyThrough) {
            $record->save();

            return $record;
        }

        $record = $relationship->save($record);
        
       
         //update balance on invoice
         if($record->invoice_id){
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

        return $record ;
    }

    


}
