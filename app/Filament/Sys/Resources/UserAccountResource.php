<?php

namespace App\Filament\Sys\Resources;

use stdClass;
use Filament\Forms;
use App\Models\User;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\UserAccount;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Filament\Support\Enums\ActionSize;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Password;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Sys\Resources\UserAccountResource\Pages;


class UserAccountResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Setting';
    protected static ?string $modelLabel = 'User Accounts';
    protected static bool $isScopedToTenant = false;


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                    Forms\Components\Section::make()
                        ->schema([
                            Forms\Components\Select::make('roles')
                                ->relationship('roles', 'name')
                                ->saveRelationshipsUsing(function (Model $record, $state) {
                                    $record->teams()->sync(Filament::getTenant()->id);
                                    // return $record ;
                                    $record->assignRole(Role::whereIn('id', $state)->get());
                               })
                                // ->options(Role::get()->pluck('name', 'id'))
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
                            Forms\Components\DatePicker::make('email_verified_at'),
                            Forms\Components\TextInput::make('password')
                                ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                                ->dehydrated(fn (?string $state): bool => filled($state))
                                ->required(fn (string $operation): bool => $operation === 'create')
                                ->password()
                                ->confirmed()
                                ->revealable()
                                ->maxLength(255)
                                ->rule(Password::default()),
                            Forms\Components\TextInput::make('password_confirmation')
                                ->label('Confirm password')
                                ->password()
                                ->revealable()
                                ->required(fn (string $operation): bool => $operation === 'create')
                        ])->columns(2)
              
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            // ->query(User::query()->whereHas('roles', function ($query) {
            //     // $query->where('roles.team_id',Filament::getTenant()->id);
            // }))
            ->query(User::query()->whereHas('teams', function ($query) {
                $query->where('teams.id',Filament::getTenant()->id);
            }))
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
                Tables\Columns\TextColumn::make('name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->badge()
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('email_verified_at')
                    ->dateTime('d/m/Y H:i:s'),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->sortable()
                    ->searchable(),
                
            ])
            ->filters([
                //
                // Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make()
                    ->visible(fn($record) => auth()->user()->id !== $record->id),
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
                ]),
            ])
            ->recordUrl(
                fn (Model $record): string => UserAccountResource::getUrl('edit', ['record' => $record->id])
            )
            ->defaultSort('updated_at', 'desc');;
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
            'index' => Pages\ListUserAccounts::route('/'),
            'create' => Pages\CreateUserAccount::route('/create'),
            'edit' => Pages\EditUserAccount::route('/{record}/edit'),
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
