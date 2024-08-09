<?php

namespace App\Filament\Sys\Widgets;

use stdClass;
use Filament\Tables;
use App\Models\Quotation;
use Filament\Tables\Table;
use App\Models\TeamSetting;
use Filament\Facades\Filament;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Model;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Filament\Sys\Resources\CustomerResource;
use App\Filament\Sys\Resources\QuotationResource;

class LatestQuotation extends BaseWidget
{
    protected static ?int $sort = 3;
    protected static bool $isLazy = false;
    protected int | string | array $columnSpan = '4';
    protected function getColumns(): int
    {
        return 1;
    }

    public function table(Table $table): Table
    {
        return $table
        ->query(Quotation::query()
        ->where('team_id', Filament::getTenant()->id)
        ->orderBy('quotation_date', 'desc')->limit(5))
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
                ->formatStateUsing(function(string $state, $record): string {
                        $newDate = date("d M, Y", strtotime($record->quotation_date));
                        $prefix = TeamSetting::where('team_id', Filament::getTenant()->id )->first()->quotation_prefix_code ?? '#Q' ;
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
                ->formatStateUsing(function(string $state, $record): string {
                        return "{$state}<br><i>({$record->items()->count()} ". __('items') .")</i>";

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
          
        ])
        ->actions([
            Action::make('select')
            ->label('Select')
            ->url(function($record) {
                    return QuotationResource::getUrl('edit', ['record' => $record->id]);
                }
            ),
        ])
        ->bulkActions([
            // ...
        ])
        ->recordUrl(
            fn (Model $record): string => QuotationResource::getUrl('edit', ['record' => $record->id])
        )
        ->paginated(false)
        ->striped();
    }
}
