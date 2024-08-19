<?php

namespace App\Livewire;

use Filament\Forms ;
use Filament\Tables;
use App\Models\Payment;
use Filament\Forms\Get;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\TeamSetting;
use Livewire\Attributes\On;
use App\Models\PaymentMethod;
use Filament\Facades\Filament;
use Livewire\Component as Livewire;
use Filament\Support\Enums\MaxWidth;
use Filament\Forms\Components\Section;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\Component;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Sys\Resources\InvoiceResource;
use Filament\Widgets\TableWidget as BaseWidget;

class PaymentTable extends BaseWidget
{

    public ?Model $record = null;
    
    public function table(Table $table): Table
    {
        $prefix = TeamSetting::where('team_id', Filament::getTenant()->id )->first()->invoice_prefix_code ?? '#I' ;
        
        return $table
            ->heading(false)
            ->query(
                Payment::query()
                ->where('team_id', Filament::getTenant()->id)
                ->where('invoice_id', $this->record->id)
            )
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->model(Payment::class)
                    ->form($this->paymentForm())
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['invoice_id'] = $this->record->id;
                        $data['team_id'] = Filament::getTenant()->id;
                        return $data;
                    })
                    ->using(function (array $data, string $model): Model {
                        $payment = $model::create($data);
                        //update balance on invoice
                        $totalPayment = Payment::where('team_id', Filament::getTenant()->id)
                        ->where('invoice_id', $this->record->id)
                        ->where('status', 'completed')->sum('total');
                        $totalRefunded = Payment::where('team_id', Filament::getTenant()->id)
                        ->where('invoice_id', $this->record->id)
                        ->where('status', 'refunded')->sum('total');

                        $this->record->balance = $this->record->final_amount - $totalPayment + $totalRefunded; 
                        if($this->record->balance == 0){
                            $this->record->invoice_status = 'done'; 
                        }elseif($this->record->invoice_status == 'done'){
                            $this->record->invoice_status = 'new' ;
                        }
                        $this->record->update();
                        $this->dispatch('invoiceUpdateStatus', $this->record);

                        return $payment;
                    })
                    ->modalWidth(MaxWidth::Screen)
                    ->slideOver(), // Add the custom action button
            ])
            ->columns([
                Tables\Columns\TextColumn::make('invoice.numbering')
                    ->numeric()
                    ->sortable()
                    ->searchable()
                    ->formatStateUsing(function(string $state, $record): string {
                            return "{$state}";
                        } 
                    )
                    ->color('primary')
                    ->prefix($prefix)
                    ->url(fn($record) => InvoiceResource::getUrl('edit', ['record' => $record->invoice_id])),
                Tables\Columns\TextColumn::make('payment_method.bank_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('payment_date')
                    ->date('j F, Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total')
                    ->prefix('RM ')
                    ->numeric()
                    ->searchable(),
                Tables\Columns\TextColumn::make('notes')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->searchable()
                    ->formatStateUsing(fn (string $state): string => __(ucwords($state))),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\DeleteAction::make()
                        ->after(function () {
                             //update balance on invoice
                            $totalPayment = Payment::where('team_id', Filament::getTenant()->id)
                            ->where('invoice_id', $this->record->id)
                            ->where('status', 'completed')->sum('total');
                            $totalRefunded = Payment::where('team_id', Filament::getTenant()->id)
                            ->where('invoice_id', $this->record->id)
                            ->where('status', 'refunded')->sum('total');
    
                            $this->record->balance = $this->record->final_amount - $totalPayment + $totalRefunded; 
                            if($this->record->balance == 0){
                                $this->record->invoice_status = 'done'; 
                            }elseif($this->record->invoice_status == 'done'){
                                $this->record->invoice_status = 'new' ;
                            }
                            $this->record->update();
                            $this->dispatch('invoiceUpdateStatus', $this->record);
                            
                        }),
                    Tables\Actions\EditAction::make()
                        ->record($this->record)
                        ->form($this->paymentForm())
                        ->mutateRecordDataUsing(function (array $data): array {
                            $data['invoice_id'] = $this->record->id;
                            $data['team_id'] = Filament::getTenant()->id;
                            return $data;
                        })
                        ->using(function (Model $record, array $data): Model {
                            // dd($record,$data, $this->record);
                            $record->update($data);
                            //update balance on invoice
                            $totalPayment = Payment::where('team_id', Filament::getTenant()->id)
                            ->where('invoice_id', $this->record->id)
                            ->where('status', 'completed')->sum('total');
                            $totalRefunded = Payment::where('team_id', Filament::getTenant()->id)
                            ->where('invoice_id', $this->record->id)
                            ->where('status', 'refunded')->sum('total');
    
                            $this->record->balance = $this->record->final_amount - $totalPayment + $totalRefunded; 
    
                            if($this->record->balance == 0){
                                $this->record->invoice_status = 'done'; 
                            }elseif($this->record->invoice_status == 'done'){
                                $this->record->invoice_status = 'new' ;
                            }
                            $this->record->update();
                            $this->dispatch('invoiceUpdateStatus', $this->record);
                            return $record;
                        })
                        ->modalWidth(MaxWidth::Screen)
                        ->slideOver(),

                ])
            ])
            ->defaultSort('updated_at', 'desc');
    }


    function paymentForm(){
        return [
            Section::make()
                ->schema([
                    Forms\Components\Select::make('payment_method_id')
                        ->label("Payment Method")
                        ->options(function (Get $get, string $operation){
                            $payment_method = PaymentMethod::where('team_id', Filament::getTenant()->id)
                            ->where('status', 1)->get()->pluck('bank_name', 'id');
                            return $payment_method ;
                        })
                        ->searchable()
                        ->preload()
                        ->required(),
                    Forms\Components\DatePicker::make('payment_date')
                        ->required()
                        ->default(now()),
                    Forms\Components\TextInput::make('total')
                        ->required()
                        ->prefix('RM')
                        ->regex('/^[0-9]*(?:\.[0-9]*)?(?:,[0-9]*(?:\.[0-9]*)?)*$/')
                        ->formatStateUsing(fn (?string $state): ?string => number_format($state, 2))
                        ->dehydrateStateUsing(fn (?string $state): ?string => (float)str_replace(",", "", $state))
                        ->default(abs($this->record->balance)),
                    Forms\Components\Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'pending_payment' => 'Pending payment',
                                'on_hold' => 'On hold',
                                'processing' => 'Processing',
                                'completed' => 'Completed',
                                'failed' => 'Failed',
                                'cancelled' => 'Cancelled',
                                'refunded' => 'Refunded',
                            ])
                            ->default($this->record->balance < 0 ? 'refunded' : 'completed')
                            ->searchable()
                            ->preload()
                            ->required(),
                    Forms\Components\TextInput::make('reference')
                                ->required(),    
                    Forms\Components\FileUpload::make('attachments')
                                ->label(__('Attachments'))
                                ->directory('payment-attachments')
                                ->multiple()
                                ->downloadable(),
                        
                    Forms\Components\Textarea::make('notes')
                        ->maxLength(65535)
                        ->columnSpanFull(),

                    
                ])
                ->columns(2),
        
        ];
    }

    
}
