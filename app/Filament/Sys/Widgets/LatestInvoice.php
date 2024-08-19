<?php

namespace App\Filament\Sys\Widgets;

use stdClass;
use Filament\Tables;
use App\Models\Invoice;
use Filament\Tables\Table;
use App\Models\TeamSetting;
use Filament\Facades\Filament;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Sys\Resources\InvoiceResource;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Filament\Sys\Resources\CustomerResource;

class LatestInvoice extends BaseWidget
{
    protected static ?int $sort = 4;
    protected static bool $isLazy = false;
    protected int | string | array $columnSpan = '4';
    protected function getColumns(): int
    {
        return 1;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Invoice::query()
            ->where('team_id', Filament::getTenant()->id)
            ->orderBy('invoice_date', 'desc')->limit(5))
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
                Tables\Columns\TextColumn::make('balance')
                    ->prefix('RM ')
                    ->label(__("Balance"))
                    ->state(function (Invoice $record): ?float {
                        return $record->balance ;
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
            ->recordUrl(
                fn (Model $record): string => InvoiceResource::getUrl('edit', ['record' => $record->id])
            )
            ->paginated(false)
            ->striped();
    }
}
