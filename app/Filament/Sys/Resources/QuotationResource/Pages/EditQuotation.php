<?php

namespace App\Filament\Sys\Resources\QuotationResource\Pages;

use App\Models\Item;
use App\Models\Note;
use Filament\Actions;
use App\Models\Invoice;
use Livewire\Component;
use App\Models\TeamSetting;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Forms\Components\ToggleButtons;
use App\Filament\Sys\Resources\InvoiceResource;
use App\Filament\Sys\Resources\QuotationResource;

class EditQuotation extends EditRecord
{
    protected static string $resource = QuotationResource::class;


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
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record->update($data);

        return $record;
    }

    protected function getSaveFormAction(): Action
    {
        return Action::make('save')
            ->label(__('filament-panels::resources/pages/edit-record.form.actions.save.label'))
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
            ->action(function (array $data,Component $livewire) {
                $this->save();
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
