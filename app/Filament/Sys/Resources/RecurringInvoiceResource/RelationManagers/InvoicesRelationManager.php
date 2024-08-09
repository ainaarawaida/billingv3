<?php

namespace App\Filament\Sys\Resources\RecurringInvoiceResource\RelationManagers;

use Closure;
use stdClass;
use Filament\Forms;
use App\Models\Item;
use App\Models\Note;
use Filament\Tables;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use Filament\Forms\Get;
use App\Models\Customer;
use Filament\Forms\Form;
use App\Mail\InvoiceEmail;
use Filament\Tables\Table;
use App\Livewire\NoteTable;
use App\Models\TeamSetting;
use Livewire\Attributes\On;
use App\Models\PaymentMethod;
use App\Livewire\PaymentTable;
use Filament\Facades\Filament;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\Tabs;
use Filament\Tables\Filters\Filter;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\Facades\Mail;
use Filament\Forms\Components\Section;
use Filament\Support\Enums\ActionSize;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Session;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Actions\Action;
use Filament\Tables\Columns\Layout\Component;
use App\Filament\Sys\Resources\InvoiceResource;
use App\Filament\Sys\Resources\CustomerResource;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Sys\Resources\RecurringInvoiceResource;
use Filament\Resources\RelationManagers\RelationManager;

class InvoicesRelationManager extends RelationManager
{
    protected static string $relationship = 'invoices';

    public function form(Form $form): Form
    {
        return $form
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

                           
                            Forms\Components\Placeholder::make('detail_customer')
                                ->content(function ($record, $get){
                                    $paparan = '<div class="text-xl">
                                    <span class="text-primary-500 font-medium">
                                    No Customer Selected</span>
                                    </div>' ;
                                    if($get('customer_id')){
                                        $customer = Customer::where('id', $get('customer_id'))?->first();
                                        $paparan = '<div class="text-xl">
                                        <span class="text-primary-500 font-medium">
                                        <b>'.$customer->name.'</b><br>
                                        Email: '.$customer->email .'<br>
                                        Phone: '.$customer->phone .'<br></span>
                                        </div>' ;
                                        
                                    }

                                    return new HtmlString($paparan);

                                } ),
                        
                        ])

                    
                ]),
            Forms\Components\Group::make()
                ->schema([
                    Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\DatePicker::make('invoice_date')
                                // ->format('d/m/Y')
                                ->native(false)
                                ->displayFormat('d/m/Y')
                                ->default(now())
                                ->required(),
                            Forms\Components\DatePicker::make('pay_before')
                                ->native(false)
                                ->displayFormat('d/m/Y')
                                ->default(now()->addDays(1))
                                ->required(),

                            
                            ])
                            ->columns(2),
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\Select::make('invoice_status')
                                    ->options([
                                        'draft' => 'Draft',
                                        'new' => 'New',
                                        'process' => 'Process',
                                        'done' => 'Done',
                                        'expired' => 'Expired',
                                        'cancelled' => 'Cancelled',
    
                                    ])
                                    ->default('draft')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->rules([
                                        fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                            if ( $value == 'done' && $get('balance') != 0 ) {
                                                $fail("The :attribute is invalid. The balance is not zero for status Done.");
                                            }
                                         
                                        },
                                    ]),
                              

                            ])
                            ->columns(1),
                       

                        Forms\Components\TextInput::make('numbering')
                            ->hiddenLabel()
                            ->disabled(fn (string $operation): string => $operation == 'create')
                            // ->readOnly()
                            // ->dehydrated(false)
                            ->prefix(fn (string $operation): string => TeamSetting::where('team_id', Filament::getTenant()->id )->first()->invoice_prefix_code ?? '#I')
                            // ->visible(fn (string $operation): bool => $operation === 'edit')
                            ->formatStateUsing(function(?string $state, $operation, $record): ?string {
                                if($operation === 'create'){
                                    $tenant_id = Filament::getTenant()->id ;
                                    $team_setting = TeamSetting::where('team_id', $tenant_id )->first();
                                    $invoice_current_no = $team_setting->invoice_current_no ?? '0' ;    

                                    // $lastid = Invoice::where('team_id', $tenant_id)->count('id') + 1 ;
                                    return str_pad(($invoice_current_no + 1), 6, "0", STR_PAD_LEFT) ;

                                }else{
                                    return $record->numbering ;
                                }
                            }),


                    ])
                    
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
                        Forms\Components\TextInput::make('balance')
                            ->formatStateUsing(fn ( $state)  => number_format($state, 2))
                            ->dehydrateStateUsing(fn (string $state): string => (float)str_replace(",", "", $state))
                            ->prefix('RM')
                            ->readonly()
                            ->helperText( fn(?Model $record, string $operation) => $operation == 'edit' ? 'On changes balance will be updated after save. Original balance: ' . $record->balance : '')
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

                                $before_final_amount = (float)str_replace(",", "", $get("final_amount"));
                                $final_amount = $sub_total + (float)str_replace(",", "", $get("taxes")) + (float)str_replace(",", "", $get("delivery"));
                                $additional_amount = $final_amount - $before_final_amount;
                                $current_balance = (float)str_replace(",", "", $get("balance"));
                                $set('sub_total', number_format($sub_total, 2));
                                $set('taxes', number_format($taxes, 2));
                                $set('balance', number_format($current_balance + $additional_amount, 2));
                                $set('final_amount', number_format($final_amount, 2));
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
                               
                                Tabs\Tab::make('payment')
                                    ->label(__('Payment'))
                                    ->hidden(fn (?Model $record): bool => $record === null)
                                    ->schema([
                                        Forms\Components\Livewire::make(PaymentTable::class,[])
                                        ->key('PaymentTable')
                                    ])

                            ])
                    ]),

               

            

                     
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
        ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([
            SoftDeletingScope::class,
        ]))
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
                        $newDate = date("d M, Y", strtotime($record->invoice_date));
                        $prefix = TeamSetting::where('team_id', Filament::getTenant()->id )->first()->invoice_prefix_code ?? '#I' ;
                        return __("<b class=''>{$prefix}{$state}</b><br>{$newDate}");

                    } 
                )
                ->html()
                ->color('primary')
                ->sortable()
                ->searchable(),
            Tables\Columns\TextColumn::make('summary')
                ->label(__('Summary'))
                ->wrap()
                ->sortable()
                ->searchable()
                ->formatStateUsing(function(string $state, $record): string {
                        return "{$state}<br><i>({$record->items()->count()} ". __('items') .")</i>";
                    } 
                )
                ->toggleable(isToggledHiddenByDefault: false)
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
            Tables\Columns\TextColumn::make('invoice_date')
                ->date('j F, Y')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('pay_before')
                ->date('j F, Y')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\SelectColumn::make('invoice_status')
                ->disabled(true)
                ->label('Status')
                ->extraHeaderAttributes([
                    'style' => 'padding-right:100px'
                ])
                ->options([
                    'draft' => 'Draft',
                    'new' => 'New',
                    'process' => 'Process',
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
            Tables\Columns\TextColumn::make('balance')
                ->prefix('RM ')
                ->label(__("Balance"))
                ->state(function (Invoice $record): ?float {
                    return $record?->balance ;
                })
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
            SelectFilter::make('invoice_status')
                ->label('Status')
                ->multiple()
                ->options([
                    'draft' => 'Draft',
                    'new' => 'New',
                    'process' => 'Process',
                    'done' => 'Done',
                    'expired' => 'Expired',
                    'cancelled' => 'Cancelled',
                ])
                ->indicator('Status'),
            Filter::make('numbering_f')
                ->form([
                    TextInput::make('numbering')
                    ->label('No' )
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
                    if (! $data['numbering']) {
                        return null;
                    }
             
                    return 'No %'.$data['numbering'] . '%';
                }),
            Filter::make('customer_name_f')
                ->form([
                    TextInput::make('customer_name')
                    ->label('Customer Name' ),
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
                    if (! $data['customer_name']) {
                        return null;
                    }
             
                    return 'Customer Name %'.$data['customer_name'] . '%';
                }),
        ], layout: FiltersLayout::AboveContentCollapsible)
        ->headerActions([
            Tables\Actions\Action::make('create')
                ->label('New Invoice')
                ->action(function (array $data, RelationManager $livewire) {
                        $livewire->redirect(InvoiceResource::getUrl('create'), navigate:true);
                    }),
            // Tables\Actions\CreateAction::make()
            //     ->mutateFormDataUsing(function (array $data, RelationManager $livewire): array {
            //         $data['team_id'] = Filament::getTenant()->id;
            //         $data['recurring_invoice_id'] = $livewire->getOwnerRecord()->id;
            //         $data['balance'] = $data['final_amount'];
            //         return $data;
            //     })
            //     ->using(function (array $data, string $model): Model {
            //         $tenant_id = Filament::getTenant()->id ;
            //         $team_setting = TeamSetting::where('team_id', $tenant_id )->first();
            //         $invoice_current_no = $team_setting->invoice_current_no ?? '0' ;    
            //         if($team_setting){
            //             $team_setting->invoice_current_no = $invoice_current_no + 1 ;
            //             $team_setting->save();
            //         }else{
            //             $team_setting = TeamSetting::create([
            //                 'team_id' => $tenant_id,
            //                 'invoice_current_no' => Invoice::where('team_id', $tenant_id)->count('id') + 1 ,
            //             ]);
            //         }   
            //         $data['numbering'] = str_pad(($invoice_current_no + 1), 6, "0", STR_PAD_LEFT) ;
            //         $data['balance'] = $data['final_amount'] ;

                 
            //         $invoice = $model::create($data);
            //          //save note
            //         if($data['content'] != ''){
            //             Note::create([
            //                 'user_id' => auth()->user()->id,
            //                 'team_id' => Filament::getTenant()->id,
            //                 'type' => 'invoice',
            //                 'type_id' => $invoice->id,
            //                 'content' =>  $data['content'],
            //             ]);

            //         }
            //         return $invoice; 

            //     })
            //     ->modalWidth(MaxWidth::Screen)
            //     ->slideOver(), 
        ])
        ->actions([
            Tables\Actions\ActionGroup::make([
                Tables\Actions\Action::make('edit')
                ->label('Edit')
                ->icon('heroicon-m-pencil-square')
                ->color('grey')
                ->action(function (Model $record, RelationManager $livewire) {
                        $livewire->redirect(InvoiceResource::getUrl('edit', ['record' => $record->id]), navigate:true);
                    }),
                // Tables\Actions\EditAction::make()
                //     ->mutateFormDataUsing(function (array $data): array {
                //         return $data;
                //     })
                //     ->using(function (Model $record, array $data): Model {
                //          //update balance 
                //         $totalPayment = Payment::where('team_id', Filament::getTenant()->id)
                //         ->where('invoice_id', $record->id)
                //         ->where('status', 'completed')->sum('total');
                //         $totalRefunded = Payment::where('team_id', Filament::getTenant()->id)
                //         ->where('invoice_id', $record->id)
                //         ->where('status', 'refunded')->sum('total');
            
                //         $data['balance'] = $data['final_amount'] - $totalPayment + $totalRefunded ;
                //         if($data['balance'] == 0){
                //             $data['invoice_status'] = 'done';
                //         }
                //         $record->update($data);
                //         return $record;
                //     })
                //     ->modalWidth(MaxWidth::Screen)
                //     ->slideOver(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('payment')
                        ->label(__('Payment'))
                        ->icon('heroicon-m-credit-card')
                        ->color('info')
                        ->form(self::paymentForm())
                        ->mutateFormDataUsing(function (array $data, $record) {
                            $data['invoice_id'] = $record->id;
                            $data['team_id'] = Filament::getTenant()->id;
                            return $data;
                        })
                        ->action(function (array $data, Model $record) {
                            $payment = Payment::create($data);
                            //update balance on invoice
                            $totalPayment = Payment::where('team_id', Filament::getTenant()->id)
                            ->where('invoice_id', $record->id)
                            ->where('status', 'completed')->sum('total');
                            $totalRefunded = Payment::where('team_id', Filament::getTenant()->id)
                            ->where('invoice_id', $record->id)
                            ->where('status', 'refunded')->sum('total');

                            $record->balance = $record->final_amount - $totalPayment + $totalRefunded; 
                            if($record->balance == 0){
                                $record->invoice_status = 'done'; 
                            }elseif($record->invoice_status == 'done'){
                                $record->invoice_status = 'new' ;
                            }
                            $record->update();
                        }), // Add the custom action button
                    
                Tables\Actions\Action::make('replicate')
                    ->label(__('Replicate'))
                    ->icon('heroicon-m-square-2-stack')
                    ->color('info')
                    ->action(function (Model $record, Component $livewire) {
                        $team_setting = TeamSetting::where('team_id', $record->team_id )->first();
                        $invoice_current_no = $team_setting->invoice_current_no ?? '0' ;    

                        $team_setting->invoice_current_no = $invoice_current_no + 1 ;
                        $team_setting->save();

                        // $lastid = Invoice::where('team_id', $record->team_id)->count('id') + 1 ;
                        $invoice =  Invoice::create([
                            'customer_id' => $record->customer_id ,
                            'team_id' => $record->team_id ,
                            'numbering' => str_pad(($invoice_current_no + 1), 6, "0", STR_PAD_LEFT),
                            'invoice_date' => $record->invoice_date,
                            'pay_before' => $record->pay_before, // Valid days between 7 and 30
                            'invoice_status' => $record->invoice_status,
                            'title' => $record->title,
                            'notes' => $record->notes,
                            'sub_total' => $record->sub_total, // Subtotal between 1000 and 10000
                            'taxes' => $record->taxes, // Can be calculated based on percentage_tax and sub_total later
                            'percentage_tax' => $record->percentage_tax, // Tax percentage between 0 and 20
                            'delivery' => $record->delivery, // Delivery cost between 0 and 100
                            'final_amount' => $record->final_amount, //
                        ]);
                        $item = Item::where('invoice_id', $record->id)->get();
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
                        ->title('Replicate Invoice successfully')
                        ->success()
                        ->send();

                        $livewire->redirect(InvoiceResource::getUrl('edit', ['record' => $invoice->id]), navigate:true);
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
                    ->modalDescription( fn (Model $record) => new HtmlString('<button type="button" class="fi-btn" style="padding:10px;background:grey;color:white;border-radius: 10px;"><a target="_blank" href="'.url('invoicepdf')."/".base64_encode("luqmanahmadnordin".$record->id).'">Redirect to Public URL</a></button>'))
                    ->modalSubmitActionLabel('Copy public URL')
                    ->extraAttributes(function (Model $record) {
                       return [
                            'class' => 'copy-public_url',
                            'myurl' => url('invoicepdf')."/".base64_encode("luqmanahmadnordin".$record->id),
                        ] ;
                        
                    }),
                Tables\Actions\Action::make('pdf') 
                    ->label('PDF')
                    ->color('success')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn ($record): ?string => url('invoicepdf')."/".base64_encode("luqmanahmadnordin".$record->id))
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
                        ->send(new InvoiceEmail($record, $customer));


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
        ->recordUrl(null)
        ->recordAction(Tables\Actions\EditAction::class) 
        ->defaultSort('updated_at', 'desc');
    }

    #[On('invoiceUpdateStatus')] 
    public function invoiceUpdateStatus($invoice)
    {
        foreach ($this->getCachedForms() as $key => $form) {
            if($key == 'mountedTableActionForm'){
                $livewire = $form->getLivewire();
                $statePath = $form->getStatePath();
                // $currentData = $form->getState();
                $final_amount = data_get($livewire, 'mountedTableActionsData.0.final_amount');
                $totalPayment = Payment::where('team_id', Filament::getTenant()->id)
                ->where('invoice_id', $invoice['id'])
                ->where('status', 'completed')->sum('total');
                $totalRefunded = Payment::where('team_id', Filament::getTenant()->id)
                ->where('invoice_id', $invoice['id'])
                ->where('status', 'refunded')->sum('total');

                $invoice['balance'] = $final_amount - $totalPayment + $totalRefunded; 
                if($invoice['balance'] == 0){
                    $invoice['invoice_status'] = 'done'; 
                }elseif($invoice['invoice_status'] == 'done'){
                    $invoice['invoice_status'] = 'new' ;
                }

                data_set($livewire, 'mountedTableActionsData.0.invoice_status', $invoice['invoice_status']);
                data_set($livewire, 'mountedTableActionsData.0.balance', $invoice['balance']);
            }
        }
    }


    public static function paymentForm(){
        $prefix = TeamSetting::where('team_id', Filament::getTenant()->id )->first()->invoice_prefix_code ?? '#I' ;
        
        return [
            Section::make()
                ->schema([
                    Forms\Components\TextInput::make('invoice_id')
                        ->label(__('Invoice Number'))
                        ->prefix($prefix)
                        ->default(fn(Model $record) => $record->numbering)
                        ->readonly(),
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
                        ->default(fn(Model $record) => abs($record->balance)),
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
                            ->default(fn(Model $record) => $record->balance < 0 ? 'refunded' : 'completed')
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
