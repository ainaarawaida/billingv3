<?php

namespace App\Filament\Sys\Resources;

use Closure;
use stdClass;
use Filament\Forms;
use App\Models\Item;
use App\Models\Note;
use Filament\Tables;
use Livewire\Livewire;
use App\Models\Invoice;
use App\Models\Product;
use Livewire\Component;
use App\Models\Customer;
use Filament\Forms\Form;
use App\Models\Quotation;
use Filament\Tables\Table;
use App\Livewire\NoteTable;
use App\Models\TeamSetting;
use App\Mail\QuotationEmail;
use Filament\Facades\Filament;

use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Resources\Resource;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\Tabs;
use Filament\Tables\Filters\Filter;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\Facades\Mail;
use Filament\Forms\Components\Select;
use Illuminate\Support\Facades\Blade;
use Filament\Support\Enums\ActionSize;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Support\Facades\Redirect;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Actions\Action;
use App\Filament\Sys\Resources\InvoiceResource;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Sys\Resources\QuotationResource\Pages;
use App\Filament\Sys\Resources\QuotationResource\RelationManagers;

class QuotationResource extends Resource
{
    protected static ?string $model = Quotation::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard';

    protected static ?string $navigationGroup = 'Billing';
    protected static ?int $navigationSort = 4;
    protected static bool $shouldRegisterNavigation = true;


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make()
                            ->schema([

                                Forms\Components\Select::make('customer_id')
                                    ->relationship('customer', 'name', modifyQueryUsing: fn (Builder $query) => $query->whereBelongsTo(Filament::getTenant(), 'team'))
                                    ->searchable()
                                    ->required()
                                    ->preload()
                                    ->live(onBlur: true)

                                    ->createOptionForm([
                                        self::customerForm(),
                                    ])
                                    ->createOptionAction(function (Action $action) {
                                        $action->mutateFormDataUsing(function ($data) {
                                            $data['team_id'] = Filament::getTenant()->id;

                                            return $data;
                                        });

                                        return $action
                                            ->modalHeading('Create customer')
                                            ->modalSubmitActionLabel('Create customer')
                                            ->modalWidth(MaxWidth::SevenExtraLarge)
                                            ->slideOver();
                                    })
                                    ->native(false),

                                Forms\Components\Placeholder::make('detail_customer')
                                    ->hiddenLabel(true)
                                    ->content(function($record, $get){
                                        if($get('customer_id')){
                                            $cust = Customer::where('id', $get('customer_id'))->first();
                                            return new HtmlString('
                                            <b>'.$cust->name.'<br></b>
                                             <b>'.$cust->email.'<br></b>
                                              <b>'.$cust->phone.'<br></b>
                                            
                                            ');
                                        }
                                        return new HtmlString('<b>No Customer Selected </b>');
                                    }),

                            ])


                    ]),
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make()
                            ->schema([
                                Forms\Components\Group::make()
                                    ->schema([
                                        Forms\Components\DatePicker::make('quotation_date')
                                            // ->format('d/m/Y')
                                            ->native(false)
                                            ->displayFormat('d/m/Y')
                                            ->default(now())
                                            ->required(),
                                        Forms\Components\TextInput::make('valid_days')
                                            ->numeric()
                                            ->default(1)
                                            ->minValue(0)
                                            ->required(),
                                    ])
                                    ->columns(2),
                                Forms\Components\Select::make('quote_status')
                                    ->options([
                                        'draft' => 'Draft',
                                        'new' => 'New',
                                        'processing' => 'Processing',
                                        'done' => 'Done',
                                        'expired' => 'Expired',
                                        'cancelled' => 'Cancelled',

                                    ])
                                    ->default('new')
                                    ->searchable()
                                    ->preload()
                                    ->required(),

                                Forms\Components\TextInput::make('numbering')
                                    ->hiddenLabel()
                                    ->disabled(fn (string $operation): string => $operation == 'create')
                                    // ->readOnly()
                                    // ->dehydrated(false)
                                    ->prefix(fn (string $operation): string => TeamSetting::where('team_id', Filament::getTenant()->id)->first()->quotation_prefix_code ?? '#Q')
                                    // ->visible(fn (string $operation): bool => $operation === 'edit')
                                    ->formatStateUsing(function (?string $state, $operation, $record): ?string {
                                        if ($operation === 'create') {
                                            $tenant_id = Filament::getTenant()->id;
                                            $quotation_current_no = TeamSetting::where('team_id', Filament::getTenant()->id)->first()->quotation_current_no ?? '0';
                                            // $lastid = Quotation::where('team_id', $tenant_id)->count('id') + 1 ;
                                            return str_pad(($quotation_current_no + 1), 6, "0", STR_PAD_LEFT);
                                        } else {
                                            return $record->numbering;
                                        }
                                    }),


                            ])->columns(1)

                    ]),

                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Textarea::make('summary'),
                    ]),

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
                                            ->relationship('product', 'title', modifyQueryUsing: fn (Builder $query) => $query->whereBelongsTo(Filament::getTenant(), 'team'))
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
                                            ->afterStateUpdated(function ($state, $set, $get) {

                                                $product = Product::find($state);
                                                $set('price', number_format((float)$product?->price, 2));
                                                $set('tax', (bool)$product?->tax);
                                                $set('quantity', (int)$product?->quantity);

                                                // dd((float)$product?->price,number_format((float)str_replace(",", "", $product?->price), 2), $product?->quantity, $get('price'), (float)$get('price'));
                                                $set('total', number_format((int)$product?->quantity * (float)str_replace(",", "", $get('price')), 2));
                                            }),

                                    ])
                                    ->columns(2),
                                Forms\Components\Group::make()
                                    ->schema([

                                        Forms\Components\TextInput::make('price')
                                            ->regex('/^[0-9]*(?:\.[0-9]*)?(?:,[0-9]*(?:\.[0-9]*)?)*$/')
                                            ->required()
                                            ->prefix('RM')
                                            ->formatStateUsing(fn (string $state): string => number_format($state, 2))
                                            ->dehydrateStateUsing(fn (string $state): string => (float)str_replace(",", "", $state))

                                            // ->live(onBlur: true)
                                            ->afterStateUpdated(function ($state, $set, $get) {
                                                $set('total', number_format((float)str_replace(",", "", $state) * (int)$get('quantity'), 2));
                                                // $total = 0 ; 
                                                // if(!$repeaters = $get('../../items')){
                                                //     return $total ;
                                                // }
                                                // foreach($repeaters AS $key => $val){
                                                //     $total += (float)$get("../../items.{$key}.total");
                                                // }
                                                // $set('../../sub_total', number_format($total, 2) );
                                                // $set('../../final_amount', number_format($total, 2));
                                            })
                                            ->default(0.00),
                                        Forms\Components\Checkbox::make('tax')
                                            ->label('Tax')
                                            ->inline(false),
                                        Forms\Components\TextInput::make('quantity')
                                            ->required()
                                            ->numeric()
                                            // ->live(onBlur: true)
                                            ->afterStateUpdated(function ($state, $set, $get) {
                                                $set('total', number_format($state * (float)str_replace(",", "", $get('price')), 2));
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
                            })->columns(1),

                    ]),
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\TextInput::make('sub_total')
                                    ->formatStateUsing(fn ($state)  => number_format($state, 2))
                                    ->dehydrateStateUsing(fn (string $state): string => (float)str_replace(",", "", $state))
                                    ->prefix('RM')
                                    ->readonly()
                                    ->default(0),
                                Forms\Components\TextInput::make('taxes')
                                    ->formatStateUsing(fn ($state)  => number_format($state, 2))
                                    ->dehydrateStateUsing(fn (string $state): string => (float)str_replace(",", "", $state))
                                    ->prefix('RM')
                                    ->readonly()
                                    ->default(0),
                                Forms\Components\TextInput::make('percentage_tax')
                                    ->prefix('%')
                                    ->live(onBlur: true)
                                    ->formatStateUsing(fn ($state)  => (int)$state)
                                    ->integer()
                                    ->default(0),
                                Forms\Components\TextInput::make('delivery')
                                    ->regex('/^[0-9]*(?:\.[0-9]*)?(?:,[0-9]*(?:\.[0-9]*)?)*$/')
                                    ->formatStateUsing(fn ($state)  => number_format($state, 2))
                                    ->dehydrateStateUsing(fn (string $state): string => (float)str_replace(",", "", $state))
                                    ->prefix('RM')
                                    ->live(onBlur: true)
                                    ->numeric()
                                    ->default(0.00),
                                Forms\Components\TextInput::make('final_amount')
                                    ->formatStateUsing(fn ($state)  => number_format($state, 2))
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
                            ->content(function ($get, $set) {
                                $sub_total = 0;
                                $taxes = 0;

                                if (!$repeaters = $get('items')) {
                                    return $sub_total;
                                }
                                foreach ($repeaters as $key => $val) {
                                    $sub_total += (float)str_replace(",", "", $get("items.{$key}.total"));

                                    if ($get("items.{$key}.tax") == true) {
                                        $taxes = $taxes + ((int)$get('percentage_tax') / 100 * (float)str_replace(",", "", $get("items.{$key}.total")));
                                    } else {
                                    }
                                }

                                $set('sub_total', number_format($sub_total, 2));
                                $set('taxes', number_format($taxes, 2));
                                $set('final_amount', number_format($sub_total + (float)str_replace(",", "", $get("taxes")) + (float)str_replace(",", "", $get("delivery")), 2));

                                return;
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
                                        // Forms\Components\Actions::make([
                                        //     Forms\Components\Actions\Action::make('Add Note')
                                        //         ->visible(fn (string $operation): string => $operation != 'create')
                                        //         ->form([
                                        //             Textarea::make('content')
                                        //                 ->label('Content')
                                        //                 ->required(),
                                        //         ])
                                        //         ->action(function (Forms\Get $get, Forms\Set $set, array $data, $record): void {
                                        //             $data['user_id'] = auth()->user()->id ;
                                        //             $data['team_id'] = Filament::getTenant()->id ;
                                        //             $data['type_id'] = $record->id ;
                                        //             $data['type'] = 'quotation' ;
                                        //             $note = Note::create($data);
                                        //             Notification::make() 
                                        //                 ->success()
                                        //                 ->title(__('filament-panels::resources/pages/edit-record.notifications.saved.title'))
                                        //                 ->send(); 

                                        //                 $set('count', 0);   
                                        //         })
                                        //     ]),

                                        Forms\Components\Livewire::make(NoteTable::class, ['type' => 'quotation'])
                                            ->key('NoteTable')
                                            ->hidden(fn (?Model $record): bool => $record === null),
                                    ]),

                                // Tabs\Tab::make('Notes')
                                //     ->schema([
                                //         Textarea::make('content')
                                //             ->visible(fn (string $operation): string => $operation == 'create')
                                //             ->label('Content'),
                                //         Forms\Components\Actions::make([
                                //             Forms\Components\Actions\Action::make('Add Note')
                                //                 ->visible(fn (string $operation): string => $operation != 'create')
                                //                 ->form([
                                //                     Textarea::make('content')
                                //                         ->label('Content')
                                //                         ->required(),
                                //                 ])
                                //                 ->action(function (Forms\Get $get, Forms\Set $set, array $data, $record): void {
                                //                     $data['user_id'] = auth()->user()->id ;
                                //                     $data['team_id'] = Filament::getTenant()->id ;
                                //                     $data['type_id'] = $record->id ;
                                //                     $data['type'] = 'quotation' ;
                                //                     $note = Note::create($data);
                                //                     Notification::make() 
                                //                         ->success()
                                //                         ->title(__('filament-panels::resources/pages/edit-record.notifications.saved.title'))
                                //                         ->send(); 

                                //                      $set('count', 0);   
                                //                 })
                                //         ]),
                                //         Forms\Components\Placeholder::make('notes')
                                //             ->visible(fn (string $operation): string => $operation != 'create')
                                //             ->hiddenLabel()
                                //             ->content(function ($get, $set, $record){
                                //                 $notes = Note::where('team_id', Filament::getTenant()->id)
                                //                 ->where('type', 'quotation')
                                //                 ->where('type_id', $record->id)
                                //                 ->get();
                                //                 $dataString = "";
                                //                 foreach($notes AS $key => $val){
                                //                     $dataString .= " <p><b> {$val->user->name} </b> {$val->created_at} <br> {$val->content} </p><br>
                                //                     <button class='btn btn-danger bg-danger-100' wire:click='increment({$val->id})'>delete</button>
                                //                     ";
                                //                 }
                                //                 return new HtmlString($dataString);
                                //             })

                                //     ]),
                                Tabs\Tab::make('l_attachments')
                                    ->label(__('Attachments'))
                                    ->schema([
                                        FileUpload::make('attachments')
                                            ->directory('quotation-attachments')
                                            ->multiple()
                                            ->downloadable()
                                    ]),
                            ])
                    ]),







            ]);
    }



    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('index')
                    ->label('#')
                    ->state(
                        static function (HasTable $livewire, stdClass $rowLoop): string {
                            return (string) (
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
                    ->formatStateUsing(
                        function (string $state, $record): string {
                            $newDate = date("d M, Y", strtotime($record->quotation_date));
                            $prefix = TeamSetting::where('team_id', Filament::getTenant()->id)->first()->quotation_prefix_code ?? '#Q';
                            return __("<b class=''>{$prefix}{$state}</b><br>{$newDate}");
                        }
                    )
                    ->html()
                    ->color('primary')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('valid_days')
                    ->label(__('Valid Days'))
                    ->wrapHeader()
                    ->width('1%')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('summary')
                    ->label(__('Summary'))
                    ->wrap()
                    ->sortable()
                    ->searchable()
                    ->formatStateUsing(
                        function (string $state, $record): string {
                            return "{$state}<br><i>({$record->items()->count()} " . __('items') . ")</i>";
                        }
                    )
                    ->html(),
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
                Tables\Columns\TextColumn::make('quotation_date')
                    ->date('j F, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),


                Tables\Columns\SelectColumn::make('quote_status')
                    ->disabled(true)
                    ->label('Status')
                    ->extraHeaderAttributes([
                        'style' => 'padding-right:100px'
                    ])
                    ->options([
                        'draft' => 'Draft',
                        'new' => 'New',
                        'processing' => 'Processing',
                        'done' => 'Done',
                        'expired' => 'Expired',
                        'cancelled' => 'Cancelled',

                    ])
                    ->selectablePlaceholder(false)
                    ->searchable(),

                Tables\Columns\TextColumn::make('sub_total')
                    ->prefix('RM ')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('taxes')
                    ->prefix('RM ')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('percentage_tax')
                    ->suffix('% ')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('delivery')
                    ->prefix('RM ')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('final_amount')
                    ->prefix('RM ')
                    ->label(__("Amount"))
                    ->numeric()
                    ->sortable(),
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
                SelectFilter::make('quote_status')
                    ->label('Status')
                    ->multiple()
                    ->options([
                        'draft' => 'Draft',
                        'new' => 'New',
                        'processing' => 'Processing',
                        'done' => 'Done',
                        'expired' => 'Expired',
                        'cancelled' => 'Cancelled',
                    ])
                    ->indicator('Status'),
                Filter::make('numbering_f')
                    ->form([
                        TextInput::make('numbering')
                            ->label('No')
                            ->prefix('#Q'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['numbering'],
                                fn (Builder $query, $data): Builder => $query->where('numbering', 'LIKE', '%' . $data . '%'),
                            );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (!$data['numbering']) {
                            return null;
                        }

                        return 'No %' . $data['numbering'] . '%';
                    }),
                Filter::make('customer_name_f')
                    ->form([
                        TextInput::make('customer_name')
                            ->label('Customer Name'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['customer_name'],
                                fn (Builder $query, $data): Builder =>  $query->whereHas('customer', function (Builder $query) use ($data) {
                                    $query->where('customers.name', 'LIKE', '%' . $data . '%');
                                }),
                            );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (!$data['customer_name']) {
                            return null;
                        }

                        return 'Customer Name %' . $data['customer_name'] . '%';
                    }),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\ForceDeleteAction::make(),
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\Action::make('replicate')
                        ->label(__('Replicate'))
                        ->icon('heroicon-m-square-2-stack')
                        ->color('info')
                        ->action(function (Model $record, Component $livewire) {
                            $team_setting = TeamSetting::where('team_id', $record->team_id)->first();
                            $quotation_current_no = $team_setting->quotation_current_no ?? '0';

                            $team_setting->quotation_current_no = $quotation_current_no + 1;
                            $team_setting->save();

                            // $lastid = Quotation::where('team_id', $record->team_id)->count('id') + 1 ;
                            $quotation =  Quotation::create([
                                'customer_id' => $record->customer_id,
                                'team_id' => $record->team_id,
                                'numbering' => str_pad(($quotation_current_no + 1), 6, "0", STR_PAD_LEFT),
                                'quotation_date' => $record->quotation_date,
                                'valid_days' => $record->valid_days, // Valid days between 7 and 30
                                'quote_status' => $record->quote_status,
                                'title' => $record->title,
                                'notes' => $record->notes,
                                'sub_total' => $record->sub_total, // Subtotal between 1000 and 10000
                                'taxes' => $record->taxes, // Can be calculated based on percentage_tax and sub_total later
                                'percentage_tax' => $record->percentage_tax, // Tax percentage between 0 and 20
                                'delivery' => $record->delivery, // Delivery cost between 0 and 100
                                'final_amount' => $record->final_amount, //
                            ]);
                            $item = Item::where('quotation_id', $record->id)->get();
                            foreach ($item as $key => $value) {
                                Item::create([
                                    'quotation_id' => $quotation->id,
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
                                ->title('Replicate Quotation successfully')
                                ->success()
                                ->send();

                            $livewire->redirect(QuotationResource::getUrl('edit', ['record' => $quotation->id]), navigate: true);
                        }),
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
                        ->modalDescription(fn (Model $record) => new HtmlString('<button type="button" class="fi-btn" style="padding:10px;background:grey;color:white;border-radius: 10px;"><a target="_blank" href="' . url('quotation-pdf') . "/" . base64_encode("luqmanahmadnordin" . $record->id) . '">Redirect to Public URL</a></button>'))
                        ->modalSubmitActionLabel('Copy public URL')
                        ->extraAttributes(function (Model $record) {
                            return [
                                'class' => 'copy-public_url',
                                'myurl' => url('quotation-pdf') . "/" . base64_encode("luqmanahmadnordin" . $record->id),
                            ];
                        }),
                    Tables\Actions\Action::make('gen_invoice')
                        ->label(__('Gen Invoice'))
                        ->icon('heroicon-o-clipboard-document-list')
                        ->color('info')
                        ->action(function (Model $record, Component $livewire) {
                            $team_setting = TeamSetting::where('team_id', $record->team_id)->first();
                            $invoice_current_no = $team_setting->invoice_current_no ?? '0';

                            $team_setting->invoice_current_no = $invoice_current_no + 1;
                            $team_setting->save();

                            // $lastid = Invoice::where('team_id', $record->team_id)->count('id') + 1 ;
                            $invoice =  Invoice::create([
                                'customer_id' => $record->customer_id,
                                'team_id' => $record->team_id,
                                'numbering' => str_pad(($invoice_current_no + 1), 6, "0", STR_PAD_LEFT),
                                'invoice_date' => now()->format('Y-m-d'),
                                'pay_before' => now()->format('Y-m-d'), // Valid days between 7 and 30
                                'invoice_status' => 'draft',
                                'summary' => $record->summary,
                                'sub_total' => $record->sub_total, // Subtotal between 1000 and 10000
                                'taxes' => $record->taxes, // Can be calculated based on percentage_tax and sub_total later
                                'percentage_tax' => $record->percentage_tax, // Tax percentage between 0 and 20
                                'delivery' => $record->delivery, // Delivery cost between 0 and 100
                                'final_amount' => $record->final_amount, //
                                'balance' => $record->final_amount, //
                                'terms_conditions' => $record->terms_conditions, //
                                'footer' => $record->footer, //
                            ]);
                            $item = Item::where('quotation_id', $record->id)->get();
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

                            $livewire->redirect(InvoiceResource::getUrl('edit', ['record' => $invoice->id]), navigate: true);
                        }),
                    Tables\Actions\Action::make('pdf')
                        ->label('PDF')
                        ->color('success')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->url(fn ($record): ?string => url('quotation-pdf') . "/" . base64_encode("luqmanahmadnordin" . $record->id))
                        ->openUrlInNewTab(),
                    // ->action(function (Model $record) {
                    //     return response()->streamDownload(function () use ($record) {
                    //         echo Pdf::loadHtml(
                    //             Blade::render('pdf', ['record' => $record])
                    //         )
                    //         ->setBasePath(public_path())
                    //         ->stream();
                    //     }, str_pad($record->id, 6, "0", STR_PAD_LEFT)  . '.pdf');
                    // }), 
                    Tables\Actions\Action::make('sendEmail')
                        ->label('Send Email')
                        ->color('warning')
                        ->icon('heroicon-o-envelope')
                        // ->form([
                        //     TextInput::make('subject')->required(),
                        //     RichEditor::make('body')->required(),
                        // ])
                        ->action(function (Model $record) {
                            $customer = Customer::where('id', $record->customer_id)->first();
                            Mail::to($customer->email)
                                ->send(new QuotationEmail($record, $customer));


                            Notification::make()
                                ->title('Email Send successfully')
                                ->success()
                                ->send()
                                ->sendToDatabase(auth()->user());
                        }),




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
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('updated_at', 'desc')
            ->recordUrl(
                fn (Model $record): string => QuotationResource::getUrl('edit', ['record' => $record->id])
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
            'index' => Pages\ListQuotations::route('/'),
            'create' => Pages\CreateQuotation::route('/create'),
            'edit' => Pages\EditQuotation::route('/{record}/edit'),
            'view' => Pages\ViewQuotation::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereBelongsTo(Filament::getTenant(), 'team')->where('quote_status', 'new')->count();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }


    public static function customerForm()
    {
        return  Forms\Components\Group::make()
            ->schema([
                Forms\Components\Section::make('Info')
                    ->schema([
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),

                            ])
                            ->columns(1),
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\TextInput::make('email')
                                    ->email()
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('phone')
                                    ->tel()
                                    ->required()
                                    ->maxLength(255),


                            ])
                            ->columns(2),


                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\TextInput::make('company')
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('ssm')
                                    ->label('SSM No.')
                                    ->maxLength(255),

                            ])
                            ->columns(3),
                    ]),
                Forms\Components\Section::make('Address')
                    ->schema([
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\TextInput::make('address')
                                    ->maxLength(255),
                            ]),
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\TextInput::make('poscode')
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('city')
                                    ->maxLength(255),
                                Forms\Components\Select::make('state')
                                    ->options([
                                        'JHR' => 'Johor',
                                        'KDH' => 'Kedah',
                                        'KTN' => 'Kelantan',
                                        'MLK' => 'Melaka',
                                        'NSN' => 'Negeri Sembilan',
                                        'PHG' => 'Pahang',
                                        'PRK' => 'Perak',
                                        'PLS' => 'Perlis',
                                        'PNG' => 'Pulau Pinang',
                                        'SBH' => 'Sabah',
                                        'SWK' => 'Sarawak',
                                        'SGR' => 'Selangor',
                                        'TRG' => 'Terengganu',
                                        'KUL' => 'W.P. Kuala Lumpur',
                                        'LBN' => 'W.P. Labuan',
                                        'PJY' => 'W.P. Putrajaya'
                                    ])
                                    ->searchable()
                                    ->preload()

                            ])->columns(3),





                    ])



            ]);
    }
}
