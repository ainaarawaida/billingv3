<?php

namespace App\Filament\Sys\Resources\RecurringInvoiceResource\Pages;

use App\Models\Item;
use App\Models\Note;
use Filament\Actions;
use App\Models\Invoice;
use App\Models\TeamSetting;
use Filament\Facades\Filament;
use App\Models\RecurringInvoice;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Model;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Sys\Resources\RecurringInvoiceResource;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class CreateRecurringInvoice extends CreateRecord
{
    protected static string $resource = RecurringInvoiceResource::class;

    protected function handleRecordCreation(array $data): Model
    {
       
        $tenant_id = Filament::getTenant()->id ;
        $team_setting = TeamSetting::where('team_id', $tenant_id )->first();
        $recurring_invoice_current_no = $team_setting->recurring_invoice_current_no ?? '0' ;
        if($team_setting){
            $team_setting->recurring_invoice_current_no = $recurring_invoice_current_no + 1 ;
            $team_setting->save();
        }else{
            $team_setting = TeamSetting::create([
                'team_id' => $tenant_id,
                'recurring_invoice_current_no' => RecurringInvoice::where('team_id', $tenant_id)->count('id') + 1 ,
            ]);
        }

        $data['numbering'] = str_pad(($recurring_invoice_current_no + 1), 6, "0", STR_PAD_LEFT) ;
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
        
         //save note
         if($this->data['content'] != ''){
            Note::create([
                'user_id' => auth()->user()->id,
                'team_id' => Filament::getTenant()->id,
                'type' => 'recurring_invoice',
                'type_id' => $record->id,
                'content' =>  $this->form->getState()['content'],
                
            ]);

        }
        
        //create invoice
        if($this->data['status']){
            $getData = $this->form->getState();
      
            $team_setting = TeamSetting::where('team_id', $record->team_id )->first();
            $invoice_current_no = $team_setting->invoice_current_no ?? '0' ;    

            $team_setting->invoice_current_no = $invoice_current_no + 1 ;
            $team_setting->save();
            $prefix = TeamSetting::where('team_id', Filament::getTenant()->id )->first()->recurring_invoice_prefix_code ?? '#I' ;
            $invoice =  Invoice::create([
                'customer_id' => $record->customer_id ,
                'team_id' => $record->team_id ,
                'numbering' => str_pad(($invoice_current_no + 1), 6, "0", STR_PAD_LEFT),
                'invoice_date' => $record->start_date,
                'pay_before' => $record->stop_date, // Valid days between 7 and 30
                'invoice_status' => 'new',
                'summary' => 'Recurring Invoice: '. $prefix.$record->numbering,
                'sub_total' =>  $getData['sub_total'], // Subtotal between 1000 and 10000
                'taxes' => $getData['taxes'], // Can be calculated based on percentage_tax and sub_total later
                'percentage_tax' => $getData['percentage_tax'], // Tax percentage between 0 and 20
                'delivery' => $getData['delivery'], // Delivery cost between 0 and 100
                'final_amount' => $getData['final_amount'], //
                'balance' => $getData['final_amount'], //
                'recurring_invoice_id' => $record->id, //
                'terms_conditions' => $record->terms_conditions, //
                'footer' => $record->footer, //
                'attachments' => '', //
            ]);
            foreach ($this->data['items'] as $key => $value) {
                Item::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $value['product_id'],
                    'title' => $value['title'],
                    'price' => (float)str_replace(",", "", $value['price']),
                    'tax' => $value['tax'],
                    'quantity' => $value['quantity'],
                    'unit' => $value['unit'],
                    'total' => (float)str_replace(",", "", $value['total']),
                ]);
            };

        }

        return $record ;
    }

    protected function getRedirectUrl(): string
    {
        $resource = static::getResource();

        // if ($resource::hasPage('view') && $resource::canView($this->getRecord())) {
        //     return $resource::getUrl('view', ['record' => $this->getRecord(), ...$this->getRedirectUrlParameters()]);
        // }

        // if ($resource::hasPage('edit') && $resource::canEdit($this->getRecord())) {
        //     return $resource::getUrl('edit', ['record' => $this->getRecord(), ...$this->getRedirectUrlParameters()]);
        // }

        return $resource::getUrl('index');
    }

    protected function getCreateFormAction(): Action
    {
        return Action::make('create')
            ->label(__('filament-panels::resources/pages/create-record.form.actions.create.label'))
            ->keyBindings(['mod+s'])
            ->action(function () {
                $this->create();
            });
    }

    

}
