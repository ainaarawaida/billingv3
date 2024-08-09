<?php

namespace App\Filament\Sys\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\TeamSetting;
use App\Models\UserSetting;
use App\Models\PaymentMethod;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Sys\Resources\PaymentMethodResource\Pages;

class PaymentMethodResource extends Resource
{
    protected static ?string $model = PaymentMethod::class;
    protected static ?string $navigationGroup = 'Setting';
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationIcon = 'heroicon-o-cursor-arrow-rays';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                ->schema([
                    Forms\Components\Select::make('type')
                        ->live(onBlur: true)
                        ->options([
                            'manual' => 'Manual',
                            'payment_gateway' => 'Payment Gateway',
                        ])
                        ->default('manual')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->afterStateUpdated(function (Set $set, Get $get, ?string $state) {
                            if(isset($state) && $state == 'payment_gateway'){
                                $set('name', 'Payment Gateway');
                            }else{
                                $set('name', '');
                                $set('payment_gateway_id', '');
                            }
                        }),
                    Forms\Components\Select::make('payment_gateway_id')
                        ->live()
                        ->visible(fn (Get $get) => $get('type') == 'payment_gateway')
                        ->label('Payment Gateway')
                        ->options(function (Get $get, string $operation){
                                $team_setting = TeamSetting::where('team_id', Filament::getTenant()->id)->first()?->payment_gateway;
                                $temp = collect($team_setting)->where('status', true)->pluck('name', 'id');
                                return $temp ;
                            })
                        ->disableOptionWhen(function (string $value, string $operation, ?Model $record): bool {
                            $payment_method = PaymentMethod::where('team_id', Filament::getTenant()->id)
                            ->pluck('payment_gateway_id')->toArray();
                            if($operation == 'edit'){
                                $payment_method = PaymentMethod::where('team_id', Filament::getTenant()->id)
                                ->where('payment_gateway_id', '!=', $record->payment_gateway_id)
                                ->pluck('payment_gateway_id')->toArray();
                            }
                            return in_array($value, $payment_method) ;
                            
                        })
                        ->in(fn (Select $component): array => array_keys($component->getEnabledOptions()))
                        // ->formatStateUsing(function (?string $state): ?string {
                        //     $payment_method = PaymentMethod::where('status', 1)
                        //     ->where('team_id', Filament::getTenant()->id)
                        //     ->pluck('payment_gateway_id')->toArray();
                        //     if(in_array($state, $payment_method)){
                        //         return null;
                        //     }else{
                        //         return $state;
                        //     }
                        // })
                        ->required()
                        ->preload()
                        ->afterStateUpdated(function (Set $set, Get $get, ?string $state) {
                            $team_setting = TeamSetting::where('team_id', Filament::getTenant()->id)->first()->payment_gateway;
                            $temp = collect($team_setting)->where('id', $state)->first();
                            if($temp && isset($temp['name'])){
                                $set('bank_name', 'Payment Gateway:'.$temp['name']);

                            }else{
                                $set('bank_name', '');
                            }
                        }),
                    Forms\Components\TextInput::make('bank_name')
                        ->readonly(fn (Get $get) => $get('type') == 'payment_gateway')
                        ->maxLength(255)
                        ->required(),
                    Forms\Components\TextInput::make('account_name')
                        ->hidden(fn (Get $get) => $get('type') == 'payment_gateway')
                        ->maxLength(255)
                        ->required(),
                    Forms\Components\TextInput::make('bank_account')
                        ->label('No Bank Account')
                        ->hidden(fn (Get $get) => $get('type') == 'payment_gateway')
                        ->maxLength(255)
                        ->required(),
                    Forms\Components\Toggle::make('status')
                        ->onIcon('heroicon-o-check')
                        ->offIcon('heroicon-o-x-mark')
                        ->onColor('success')
                        ->offColor('danger')
                        ->default(true),


                ])
                ->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('bank_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('account_name')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('bank_account')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('type')
                        ->badge()
                        ->formatStateUsing(function(string $state, $record): string {
                            return str_replace('_', ' ', ucfirst($state));
                        } 
                    )
                    ->html()
                    ->sortable(),
                Tables\Columns\ToggleColumn::make('status')
                    ->disabled()
                    ->searchable(),
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
                //
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\ForceDeleteAction::make(),
                    Tables\Actions\ViewAction::make(),
                   
                       
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->recordUrl(
                fn (Model $record): string => PaymentMethodResource::getUrl('edit', ['record' => $record->id])
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
            'index' => Pages\ListPaymentMethods::route('/'),
            'create' => Pages\CreatePaymentMethod::route('/create'),
            'view' => Pages\ViewPaymentMethod::route('/{record}'),
            'edit' => Pages\EditPaymentMethod::route('/{record}/edit'),
        ];
    }
}
