<?php

namespace App\Filament\Sys\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Invoice;
use App\Models\Payment;
use Filament\Forms\Get;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\TeamSetting;
use App\Models\PaymentMethod;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Illuminate\Support\HtmlString;
use Filament\Support\Enums\ActionSize;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Sys\Resources\PaymentResource\Pages;
use App\Filament\Sys\Resources\PaymentResource\RelationManagers;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;
    protected static ?string $navigationGroup = 'Billing';
    protected static ?int $navigationSort = 7;
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static bool $shouldRegisterNavigation = true;

    public static function form(Form $form): Form
    {
        $prefix = TeamSetting::where('team_id', Filament::getTenant()->id )->first()->invoice_prefix_code ?? '#I' ;
        
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Select::make('invoice_id')
                            ->prefix($prefix)
                            ->relationship('invoice', 'numbering', modifyQueryUsing: fn (Builder $query) => $query->whereBelongsTo(Filament::getTenant(), 'team'))
                            ->searchable()
                            ->preload(),
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
                            ->formatStateUsing(fn (string $state): string => number_format($state, 2))
                            ->dehydrateStateUsing(fn (string $state): string => (float)str_replace(",", "", $state))
                            ->default(0.00),
                        Forms\Components\Select::make('status')
                                ->options([
                                    'draft' => 'Draft',
                                    'pending_payment' => 'Pending payment',
                                    'on_hold' => 'On hold',
                                    'processing ' => 'Processing ',
                                    'completed' => 'Completed',
                                    'failed' => 'Failed',
                                    'cancelled' => 'Cancelled',
                                    'refunded' => 'Refunded',
                                ])
                                ->default('draft')
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
            ]);
    }

    public static function table(Table $table): Table
    {
        $prefix = TeamSetting::where('team_id', Filament::getTenant()->id )->first()->invoice_prefix_code ?? '#I' ;
       
        return $table
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
                    ->url(fn($record) => isset($record->invoice_id) ? InvoiceResource::getUrl('edit', ['record' => $record->invoice_id]) : false),
                Tables\Columns\TextColumn::make('reference')
                    ->badge()
                    ->searchable()
                    ->sortable(),
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
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make()
                        ->after(function (array $data, Model $record) {
                            //update balance on invoice
                            if($record->invoice_id){
                                $invoice = Invoice::find($record->invoice_id);
                                $totalPayment = Payment::where('team_id', Filament::getTenant()->id)
                                ->where('invoice_id', $invoice->id)
                                ->where('status', 'completed')->sum('total');
                                $totalRefunded = Payment::where('team_id', Filament::getTenant()->id)
                                ->where('invoice_id', $record->id)
                                ->where('status', 'refunded')->sum('total');

                                $invoice->balance = $invoice->final_amount - $totalPayment + $totalRefunded ; 
                                if($invoice->balance == 0){
                                    $invoice->invoice_status = 'done'; 
                                }elseif($invoice->invoice_status == 'done'){
                                    $invoice->invoice_status = 'new' ;
                                }
                                $invoice->update();
                                
                            }
                        }),
                    Tables\Actions\ForceDeleteAction::make(),
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\Action::make('public_url') 
                        ->label('Public Url')
                        ->color('success')
                        ->icon('heroicon-o-globe-alt')
                        ->action(function (Model $record) {
                            Notification::make()
                            ->title('Copy Public Url Successfully')
                            ->success()
                            ->send();
                            
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Public Url')
                        ->modalDescription( fn (Model $record) => new HtmlString('<button type="button" class="fi-btn" style="padding:10px;background:grey;color:white;border-radius: 10px;"><a target="_blank" href="'.url('paymentpdf')."/".base64_encode("luqmanahmadnordin".$record->id).'">Redirect to Public URL</a></button>'))
                        ->modalSubmitActionLabel('Copy public URL')
                        ->extraAttributes(function (Model $record) {
                           return [
                                'class' => 'copy-public_url',
                                'myurl' => url('paymentpdf')."/".base64_encode("luqmanahmadnordin".$record->id),
                            ] ;
                            
                        }),
                    Tables\Actions\Action::make('pdf') 
                        ->label('PDF')
                        ->color('success')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->url(fn ($record): ?string => url('paymentpdf')."/".base64_encode("luqmanahmadnordin".$record->id))
                        ->openUrlInNewTab(),
                       
                    
                ])
                ->label('More actions')
                ->icon('heroicon-m-ellipsis-vertical')
                ->size(ActionSize::Small)
                ->color('primary')
                ->button()
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('updated_at', 'desc')
            ->recordUrl(
                fn (Model $record): string => PaymentResource::getUrl('edit', ['record' => $record->id])
            );
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayment::route('/create'),
            'view' => Pages\ViewPayment::route('/{record}'),
            'edit' => Pages\EditPayment::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereBelongsTo(Filament::getTenant(), 'team')
        ->where('status', 'pending_payment')->count();
        
    }
}
