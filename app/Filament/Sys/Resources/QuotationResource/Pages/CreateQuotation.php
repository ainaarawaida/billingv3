<?php

namespace App\Filament\Sys\Resources\QuotationResource\Pages;

use App\Models\Item;
use App\Models\Note;
use Filament\Actions;
use App\Models\Invoice;
use Livewire\Component;
use App\Models\Quotation;
use App\Models\TeamSetting;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\ViewField;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Forms\Components\ToggleButtons;
use App\Filament\Sys\Resources\InvoiceResource;
use App\Filament\Sys\Resources\QuotationResource;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class CreateQuotation extends CreateRecord
{
    protected static string $resource = QuotationResource::class;


    // public function getHeading(): string 
    // {
    //     // return "ddd";
    //     // $data = $this->form->getRawState();
    //     // return json_encode($data);
    //     return $this->heading ?? $this->getTitle()." ".;
    // }

    // protected function getHeaderActions(): array
    // {
    //     return [
            
    //     Actions\CreateAction::make(),
    //     ];
    // }

    public function mount(): void
    {

        // $tenant = Filament::getTenant();
        
        // dd($tenant->id);

        $this->form->fill();
    }

    protected function handleRecordCreation(array $data): Model
    {
       
        $tenant_id = Filament::getTenant()->id ;
        $team_setting = TeamSetting::where('team_id', $tenant_id )->first();
        $quotation_current_no = $team_setting->quotation_current_no ?? '0' ;
        if($team_setting){
            $team_setting->quotation_current_no = $quotation_current_no + 1 ;
            $team_setting->save();
        }else{
            $team_setting = TeamSetting::create([
                'team_id' => $tenant_id,
                'quotation_current_no' => Quotation::where('team_id', $tenant_id)->count('id') + 1 ,
            ]);
        }

      
        
        // $lastid = Quotation::where('team_id', $tenant_id)->count('id') + 1 ;
        $data['numbering'] = str_pad(($quotation_current_no + 1), 6, "0", STR_PAD_LEFT) ;
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

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
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
        if($this->form->getState()['content'] != ''){
            Note::create([
                'user_id' => auth()->user()->id,
                'team_id' => Filament::getTenant()->id,
                'type' => 'quotation',
                'type_id' => $record->id,
                'content' =>  $this->form->getState()['content'],
                
            ]);

        }

        return $record ;
    }

    protected function getCreateFormAction(): Action
    {
        return Action::make('create')
            ->label(__('filament-panels::resources/pages/create-record.form.actions.create.label'))
            ->keyBindings(['mod+s'])
            ->form(function(){
               if($this->data['quote_status'] == 'done'){
                   return [
                   ToggleButtons::make('status_create_invoice')
                       ->label(__('Create New Invoice from this Quotation?'))
                       ->boolean()
                       ->inline()
                   ];

               }else{
                return [];
               }
            })
            ->action(function (array $data, Component $livewire) {
                $this->create();
                if($data['status_create_invoice']){
                    $team_setting = TeamSetting::where('team_id', $this->record->team_id )->first();
                    $invoice_current_no = $team_setting->invoice_current_no ?? '0' ;    

                    $team_setting->invoice_current_no = $invoice_current_no + 1 ;
                    $team_setting->save();

                    $invoice =  Invoice::create([
                        'customer_id' => $this->record->customer_id ,
                        'team_id' => $this->record->team_id ,
                        'numbering' => str_pad(($invoice_current_no + 1), 6, "0", STR_PAD_LEFT),
                        'invoice_date' => now()->format('Y-m-d'),
                        'pay_before' => now()->format('Y-m-d'), // Valid days between 7 and 30
                        'invoice_status' => 'draft',
                        'summary' => $this->record->summary,
                        'sub_total' => $this->record->sub_total, // Subtotal between 1000 and 10000
                        'taxes' => $this->record->taxes, // Can be calculated based on percentage_tax and sub_total later
                        'percentage_tax' => $this->record->percentage_tax, // Tax percentage between 0 and 20
                        'delivery' => $this->record->delivery, // Delivery cost between 0 and 100
                        'final_amount' => $this->record->final_amount, //
                        'balance' => $this->record->final_amount, //
                        'terms_conditions' => $this->record->terms_conditions, //
                        'footer' => $this->record->footer, //
                    ]);
                    $item = Item::where('quotation_id', $this->record->id)->get();
                    foreach ($item as $key => $value) {
                        Item::create([
                            'invoice_id' => $invoice->id,
                            'product_id' => $value->product_id,
                            'title' => $value->title,
                            'price' => $value->price,
                            'tax' => $value->tax,
                            'quantity' => $value->quantity,
                            'unit' => $value->unit,
                            'total' => $value->total,
                        ]);
                    };

                    Notification::make()
                    ->title('Generate Invoice successfully')
                    ->success()
                    ->send();

                    $livewire->redirect(InvoiceResource::getUrl('edit', ['record' => $invoice->id]), navigate:true);
                }
            });
        
      
    }


  
}
