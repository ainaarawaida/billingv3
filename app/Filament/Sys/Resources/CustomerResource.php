<?php

namespace App\Filament\Sys\Resources;

use Filament\Forms;
use App\Models\User;
use Filament\Tables;
use Livewire\Component;
use App\Models\Customer;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Illuminate\Support\HtmlString;
use Spatie\Permission\Models\Role;
use Filament\Forms\Components\Group;
use Illuminate\Support\Facades\Hash;
use Filament\Forms\Components\Section;
use Filament\Support\Enums\ActionSize;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Password;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Sys\Resources\CustomerResource\Pages;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;
    protected static ?string $navigationIcon = 'heroicon-o-user';
    protected static ?string $navigationGroup = 'Resources';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Info')
                ->schema([
                    Group::make()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        
                    ])
                    ->columns(1),
                    Group::make()
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
             
                    
                    Group::make()
                        ->schema([
                            Forms\Components\TextInput::make('company')
                                ->maxLength(255),
                            Forms\Components\TextInput::make('ssm')
                                ->label('SSM No.')
                                ->maxLength(255),
                            
                        ])
                        ->columns(3),
                ]),
                Section::make('Address')
                    ->schema([
                        Forms\Components\TextInput::make('address')
                            ->maxLength(255),
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

                            
                ])
                ->columns(3)
               
                
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('company')
                    ->searchable(),
                Tables\Columns\TextColumn::make('ssm')
                    ->label("SSM")
                    ->searchable(),
                Tables\Columns\TextColumn::make('address')
                    ->searchable(),
                Tables\Columns\TextColumn::make('poscode')
                    ->searchable(),
                Tables\Columns\TextColumn::make('city')
                    ->searchable(),
                Tables\Columns\SelectColumn::make('state')
                ->disabled(true)
                ->label(new HtmlString('<span style="">State</span>'))
                ->extraHeaderAttributes([
                    'style' => 'padding-right:150px'
                ])
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
                    Tables\Actions\Action::make('upgrate_as_user')
                        ->label(__('Upgrade as User'))
                        ->icon('heroicon-m-square-2-stack')
                        ->color('info')
                        ->fillForm(fn ($record): array => [
                            'roles' => Role::where('name', 'customer')->pluck('id')->toArray(),
                            'name' => $record->name,
                            'email' => $record->email,

                        ])
                        ->form([
                                Forms\Components\Section::make()
                                    ->model(User::class)
                                    ->schema([
                                        Forms\Components\Select::make('roles')
                                            ->relationship('roles', 'name')
                                            ->saveRelationshipsUsing(function (Model $record, $state) {
                                                $record->teams()->sync(Filament::getTenant()->id);
                                                $record->assignRole(Role::whereIn('id', $state)->get());
                                           })
                                            ->dehydrated(true)
                                            ->multiple()
                                            ->preload()
                                            ->searchable(),
                                        Forms\Components\TextInput::make('name')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('email')
                                            ->email()
                                            ->required()
                                            ->unique(ignoreRecord: true)
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('password')
                                            ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                                            ->required(true)
                                            ->password()
                                            ->confirmed()
                                            ->revealable()
                                            ->maxLength(255)
                                            ->rule(Password::default()),
                                        Forms\Components\TextInput::make('password_confirmation')
                                            ->label('Confirm password')
                                            ->dehydrated(false)
                                            ->password()
                                            ->revealable()
                                            ->required(fn (string $operation): bool => $operation === 'create')
                                    ])->columns(2)
                          
                       
                        ])
                        ->action(function (Model $record, $data, Component $livewire) {
                            $user = User::create($data);
                            $user->teams()->sync(Filament::getTenant()->id);
                            $user->assignRole(Role::whereIn('id', $data['roles'])->get());

                            Notification::make()
                            ->title('Create as User successfully!')
                            ->success()
                            ->send();
                        })
                        ->visible(fn($record) => $record->email != User::where('email', $record->email)->first()?->email),
                    Tables\Actions\Action::make('view_user')
                        ->label(__('View User'))
                        ->icon('heroicon-m-pencil')
                        ->color('info')
                        ->action(function (Model $record, $data, Component $livewire) {
                            $livewire->redirect(UserAccountResource::getUrl('edit', ['record' => User::where('email', $record->email)->first()?->id]), navigate:true);
                        })
                        ->visible(fn($record) => $record->email == User::where('email', $record->email)->first()?->email),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\ViewAction::make(),
                  
                ])
                ->label('More actions')
                ->icon('heroicon-m-ellipsis-vertical')
                ->size(ActionSize::Small)
                ->color('primary')
                ->button(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->recordUrl(
                fn (Model $record): string => CustomerResource::getUrl('edit', ['record' => $record->id])
            )
            ->defaultSort('updated_at', 'desc');
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
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'view' => Pages\ViewCustomer::route('/{record}'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
