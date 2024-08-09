<?php

namespace App\Filament\Sys\Resources;

use stdClass;
use Filament\Forms;
use Filament\Tables;
use App\Models\Product;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Livewire\NoteTable;
use App\Models\TeamSetting;
use App\Livewire\PaymentTable;
use Filament\Facades\Filament;
use Illuminate\Support\Carbon;
use App\Models\RecurringInvoice;
use Filament\Resources\Resource;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\Tabs;
use Filament\Support\Enums\MaxWidth;
use Filament\Support\Enums\ActionSize;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use Filament\Forms\Components\FileUpload;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Forms\Components\Actions\Action;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Sys\Resources\RecurringInvoiceResource\Pages;
use App\Filament\Sys\Resources\RecurringInvoiceResource\RelationManagers;

class RecurringInvoiceResource extends Resource
{
    protected static ?string $model = RecurringInvoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';
    protected static ?string $navigationGroup = 'Billing';
    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Group::make()
                        ->schema([
                            Forms\Components\Section::make()
                                ->schema([
                                    Forms\Components\Select::make('customer_id')
                                        ->relationship('customer', 'name', modifyQueryUsing: fn (Builder $query) => $query->whereBelongsTo(Filament::getTenant(), 'teams'))
                                        ->searchable()
                                        ->required()
                                        ->preload()
                                        ->live(onBlur: true)
                                    
                                        ->createOptionForm([
                                            InvoiceResource::customerForm(),
                                        ])
                                        ->createOptionAction(function (Action $action) {
                                            $action->mutateFormDataUsing(function ($data) {
                                                $data['team_id'] = Filament::getTenant()->id;
                                        
                                                return $data;
                                            });
    
                                            return $action
                                                ->modalHeading('Create customer')
                                                ->modalSubmitActionLabel('Create customer')
                                                ->modalWidth(MaxWidth::Screen)
                                                ->slideOver();
                                        })
                                        ->native(false),
    
                                    Forms\Components\ViewField::make('detail_customer')
                                        ->dehydrated(false)
                                        ->view('filament.detail_customer'),
                                    // Forms\Components\Placeholder::make('detail_customer2')
                                    // ->content(fn ($record) => new HtmlString('<b>asma</b>')),
                                
                                ])
    
                            
                        ]),
                        Forms\Components\Group::make()
                        ->schema([
                            Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\TextInput::make('numbering')
                                ->hiddenLabel()
                                ->disabled(fn (string $operation): string => $operation == 'create')
                                // ->readOnly()
                                // ->dehydrated(false)
                                ->prefix(fn (string $operation): string => TeamSetting::where('team_id', Filament::getTenant()->id )->first()->recurring_invoice_prefix_code ?? '#RI')
                                // ->visible(fn (string $operation): bool => $operation === 'edit')
                                ->formatStateUsing(function(?string $state, $operation, $record): ?string {
                                    if($operation === 'create'){
                                        $tenant_id = Filament::getTenant()->id ;
                                        $team_setting = TeamSetting::where('team_id', $tenant_id )->first();
                                        $recurring_invoice_current_no = $team_setting->recurring_invoice_current_no ?? '0' ;    

                                        // $lastid = recurring_invoice::where('team_id', $tenant_id)->count('id') + 1 ;
                                        return str_pad(($recurring_invoice_current_no + 1), 6, "0", STR_PAD_LEFT) ;

                                    }else{
                                        return $record->numbering ;
                                    }
                                }),
                            ]),
                            Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\Select::make('every')
                                ->options([
                                    'One Time' => 'One Time',
                                    'Daily' => 'Daily',
                                    'Monthly' => 'Monthly',
                                    'Yearly' => 'Yearly',
                                ])
                                ->default('One Time')
                                ->searchable()
                                ->preload()
                                ->required(),

                            Forms\Components\TextInput::make('generate_before')
                                ->label(__('Generate Before Days'))
                                ->default(0)
                                ->numeric()
                                ->regex('/^[0-9]+$/')
                                ->minValue(0)
                                ->required(),

                            ])
                            ->columns(2),

                         
                       
                        ]),
                        Forms\Components\Textarea::make('summary')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                        Forms\Components\DatePicker::make('start_date')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->live(onBlur: true)
                            // ->minDate(now()->subYears(150))
                            ->default(now())
                            ->required(),
                        Forms\Components\DatePicker::make('stop_date')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->default(now())
                            ->minDate(fn($get)=> Carbon::parse($get('start_date')))
                            ->required(),
                        Forms\Components\Toggle::make('status')
                            ->onIcon('heroicon-o-check')
                            ->offIcon('heroicon-o-x-mark')
                            ->onColor('success')
                            ->offColor('danger')
                            ->default(true),
                            
                    ])->columns(2),

                Forms\Components\Section::make('Reference Invoice')
                ->visible(fn(string $operation) => $operation == 'create')
                ->schema([
                    Forms\Components\Section::make()
                        ->schema([
                            Forms\Components\Repeater::make('items')
                                ->live(onBlur: true)
                                ->minItems(1)
                                ->collapsible()
                                ->relationship('items')
                                ->schema([
                                    Forms\Components\Group::make()
                                    ->schema([
                                        Forms\Components\Textarea::make('title')
                                            ->required(),
                                        Forms\Components\Select::make('product_id')
                                            ->relationship('product', 'title', modifyQueryUsing: fn (Builder $query) => $query->whereBelongsTo(Filament::getTenant(), 'teams'))
                                            ->searchable()
                                            ->preload()
                                            ->distinct()
                                            ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                            ->createOptionForm([
                                                Forms\Components\Textarea::make('title')
                                                    ->maxLength(65535)
                                                    ->columnSpanFull(),
                                                Forms\Components\Checkbox::make('tax')
                                                    // ->live(onBlur: true)
                                                    ->inline(false),

                                                Forms\Components\TextInput::make('quantity')
                                                    ->required()
                                                    ->numeric()
                                                    ->default(0),
                                                Forms\Components\TextInput::make('price')
                                                    ->required()
                                                    ->regex('/^[0-9]*(?:\.[0-9]*)?(?:,[0-9]*(?:\.[0-9]*)?)*$/')
                                                    ->prefix('RM')
                                                    ->formatStateUsing(fn (?string $state): ?string => number_format($state, 2))
                                                    ->dehydrateStateUsing(fn (string $state): string => (float)str_replace(",", "", $state))
                                            ])
                                            ->createOptionAction(function (Action $action) {
                                                $action->mutateFormDataUsing(function ($data) {
                                                    $data['team_id'] = Filament::getTenant()->id;
                                            
                                                    return $data;
                                                });
                                                
                                                return $action
                                                    // ->modalHeading('Create customer')
                                                    // ->modalSubmitActionLabel('Create customer')
                                                    ->modalWidth(MaxWidth::Screen)
                                                    ->slideOver();
                                            })
                                            ->afterStateUpdated(function ($state, $set, $get ){
                                                
                                                $product = Product::find($state);
                                                $set('price', number_format((float)$product?->price, 2));
                                                $set('tax', (bool)$product?->tax);
                                                $set('quantity', (int)$product?->quantity);

                                                // dd((float)$product?->price,number_format((float)str_replace(",", "", $product?->price), 2), $product?->quantity, $get('price'), (float)$get('price'));
                                                $set('total', number_format((int)$product?->quantity*(float)str_replace(",", "", $get('price')), 2)  );
                                            
                                            }),
                                    ])
                                    ->columns(2),
                                    Forms\Components\Group::make()
                                        ->schema([
                                            Forms\Components\TextInput::make('price')
                                                ->required()
                                                ->prefix('RM')
                                                ->regex('/^[0-9]*(?:\.[0-9]*)?(?:,[0-9]*(?:\.[0-9]*)?)*$/')
                                                ->formatStateUsing(fn (string $state): string => number_format($state, 2))
                                                ->dehydrateStateUsing(fn (string $state): string => (float)str_replace(",", "", $state))

                                                ->afterStateUpdated(function ($state, $set, $get ){
                                                    $set('total', number_format((float)str_replace(",", "", $state)*(int)$get('quantity'), 2)  );
                                                
                                                })
                                                ->default(0.00),
                                            Forms\Components\Checkbox::make('tax')
                                                ->inline(false),
                                            Forms\Components\TextInput::make('quantity')
                                                ->required()
                                                ->numeric()
                                                ->afterStateUpdated(function ($state, $set, $get ){
                                                    $set('total', number_format($state*(float)str_replace(",", "", $get('price')), 2)  );
                                                })
                                                ->default(1),
                                            Forms\Components\Select::make('unit')
                                                ->options([
                                                    'Unit' => 'Unit',
                                                    'Kg' => 'Kg',
                                                    'Gram' => 'Gram',
                                                    'Box' => 'Box',
                                                    'Pack' => 'Pack',
                                                    'Day' => 'Day',
                                                    'Month' => 'Month',
                                                    'Year' => 'Year',
                                                    'People' => 'People',

                                                ])
                                                ->default('Unit')
                                                ->searchable()
                                                ->preload()
                                                ->required(),
                                            Forms\Components\TextInput::make('total')
                                                ->prefix('RM')
                                                ->readonly()
                                                ->formatStateUsing(fn (string $state): string => number_format($state, 2))
                                                ->dehydrateStateUsing(fn (string $state): string => (float)str_replace(",", "", $state))
                                                ->default(0.00),
                                        ])
                                        ->columns(5),

                                
                                    
                                ])
                                ->mutateRelationshipDataBeforeSaveUsing(function (array $data): array {
                                return $data;
                                }),

                        ]),

                    Forms\Components\Section::make()
                        ->schema([
                            Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\TextInput::make('sub_total')
                                    ->formatStateUsing(fn ( $state)  => number_format($state, 2))
                                    ->dehydrateStateUsing(fn (string $state): string => (float)str_replace(",", "", $state))
                                    ->prefix('RM')
                                    ->readonly()
                                    ->default(0),
                                Forms\Components\TextInput::make('taxes')
                                    ->formatStateUsing(fn ( $state)  => number_format($state, 2))
                                    ->dehydrateStateUsing(fn (string $state): string => (float)str_replace(",", "", $state))
                                    ->prefix('RM')
                                    ->readonly()
                                    ->default(0),
                                Forms\Components\TextInput::make('percentage_tax')
                                    ->prefix('%')
                                    ->live(onBlur: true)
                                    ->formatStateUsing(fn ( $state)  => (int)$state)
                                    ->integer()
                                    ->default(0),
                                Forms\Components\TextInput::make('delivery')
                                    ->regex('/^[0-9]*(?:\.[0-9]*)?(?:,[0-9]*(?:\.[0-9]*)?)*$/')
                                    ->formatStateUsing(fn ( $state)  => number_format($state, 2))
                                    ->dehydrateStateUsing(fn (string $state): string => (float)str_replace(",", "", $state))
                                    ->prefix('RM')
                                    ->live(onBlur: true)
                                    ->default(0.00),
                                Forms\Components\TextInput::make('final_amount')
                                    ->formatStateUsing(fn ( $state)  => number_format($state, 2))
                                    ->dehydrateStateUsing(fn (string $state): string => (float)str_replace(",", "", $state))
                                    ->prefix('RM')
                                    ->readonly()
                                    ->live(onBlur: true)
                                    ->default(0.00),
                                    
    
                            ])
                            ->inlineLabel()
                            ->columns(2),
    
                            Forms\Components\Placeholder::make('calculation')
                                ->hiddenLabel()
                                ->content(function ($get, $set){
                                    $sub_total = 0 ; 
                                    $taxes = 0 ;
                                    
                                    if(!$repeaters = $get('items')){
                                        return $sub_total ;
                                    }
                                    foreach($repeaters AS $key => $val){
                                        $sub_total += (float)str_replace(",", "", $get("items.{$key}.total"));
                                        
                                        if($get("items.{$key}.tax") == true){
                                            $taxes = $taxes + ((int)$get('percentage_tax') / 100 * (float)str_replace(",", "", $get("items.{$key}.total"))) ;
                                        }else{
    
                                        }
                                        
                                    }
    
                                    $set('sub_total', number_format($sub_total, 2));
                                    $set('taxes', number_format($taxes, 2));
                                    $set('final_amount', number_format($sub_total + (float)str_replace(",", "", $get("taxes")) + (float)str_replace(",", "", $get("delivery")), 2));
    
                                    return ;
                                    // return $sub_total." ".(float)$get("taxes"). " ". (float)$get("delivery")." ".$sub_total + (float)$get("taxes") + (float)$get("delivery")  ;
                                }),
    
                        ]),

                    Forms\Components\Section::make()
                        ->schema([
                            Tabs::make('Tabs')
                                ->tabs([
                                    Tabs\Tab::make('additional')
                                        ->label(__('Additional'))
                                        ->schema([
                                            Forms\Components\Textarea::make('terms_conditions'),
                                            Forms\Components\Textarea::make('footer'),
                                            
                                        ])->columns(2),

                                    Tabs\Tab::make('Notes')
                                        ->label(__('Notes'))
                                        ->schema([
                                            Textarea::make('content')
                                                ->visible(fn (string $operation): string => $operation == 'create')
                                                ->label('Content'),

                                            Forms\Components\Livewire::make(NoteTable::class,['type' => 'invoice'])
                                                ->key('NoteTable')
                                                ->hidden(fn (?Model $record): bool => $record === null),
                                        ]),
                                    Tabs\Tab::make('l_attachments')
                                        ->label(__('Attachments'))
                                        ->schema([
                                            FileUpload::make('attachments')
                                                ->directory('invoice-attachments')
                                                ->multiple()
                                                ->downloadable()
                                        ]),
                                   
                                  

                                ])
                        ]),
                ])


            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('index')
                    ->label('#')
                    ->state(
                        static function (HasTable $livewire, stdClass $rowLoop): string {                            return (string) (
                                $rowLoop->iteration +
                                ($livewire->getTableRecordsPerPage() * (
                                    $livewire->getTablePage() - 1
                                ))
                            );
                        }
                    )
                    ->sortable(),
                Tables\Columns\TextColumn::make('numbering')
                    ->label('No.')
                    ->formatStateUsing(function(string $state, $record): string {
                            $prefix = TeamSetting::where('team_id', Filament::getTenant()->id )->first()->recurring_invoice_prefix_code ?? '#RI' ;
                            return __("<b class=''>{$prefix}{$state}</b>");

                        } 
                    )
                    ->html()
                    ->color('primary')
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label(__('Customer'))
                    ->formatStateUsing(fn (string $state): string => __("<b>{$state}</b>"))
                    ->html()
                    ->searchable()
                    ->url(function ($record) {
                        return $record->customer
                            ? CustomerResource::getUrl('edit', ['record' => $record->customer_id])
                            : null;
                    })
                    ->wrap(),
                Tables\Columns\TextColumn::make('start_date')
                    ->date('j F, Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('stop_date')
                    ->date('j F, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('every')
                    ->searchable(),
                Tables\Columns\TextColumn::make('invoices_sum_final_amount')
                    ->wrapHeader()
                    ->sum('invoices', 'final_amount'),
                Tables\Columns\TextColumn::make('invoices_sum_balance')
                    ->wrapHeader()
                    ->sum('invoices', 'balance')
                    ->summarize(Sum::make()->label('Total')),
                Tables\Columns\ToggleColumn::make('status')
                    ->disabled()
                    ->searchable(),
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
                    Tables\Actions\DeleteAction::make(),
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
                        ->modalDescription( fn (Model $record) => new HtmlString('<button type="button" class="fi-btn" style="padding:10px;background:grey;color:white;border-radius: 10px;"><a target="_blank" href="'.url('recurringInvoicepdf')."/".base64_encode("luqmanahmadnordin".$record->id).'/new">Redirect to Public URL</a></button>'))
                        ->modalSubmitActionLabel('Copy public URL')
                        ->extraAttributes(function (Model $record) {
                        return [
                                'class' => 'copy-public_url',
                                'myurl' => url('recurringInvoicepdf')."/".base64_encode("luqmanahmadnordin".$record->id)."/new",
                            ] ;
                            
                        }),
                    Tables\Actions\Action::make('pdf') 
                        ->label('PDF')
                        ->color('success')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->url(fn ($record): ?string => url('recurringInvoicepdf')."/".base64_encode("luqmanahmadnordin".$record->id)."/new")
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
            ->recordUrl(
                fn (Model $record): string => RecurringInvoiceResource::getUrl('edit', ['record' => $record->id])
            )
            ->defaultSort('updated_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
            RelationManagers\InvoicesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRecurringInvoices::route('/'),
            'create' => Pages\CreateRecurringInvoice::route('/create'),
            'view' => Pages\ViewRecurringInvoice::route('/{record}'),
            'edit' => Pages\EditRecurringInvoice::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    // public static function getNavigationBadge(): ?string
    // {
    //     return static::getModel()::whereBelongsTo(Filament::getTenant(), 'teams')->count();
        
    // }
}
