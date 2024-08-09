<?php

namespace App\Filament\Imports;

use Carbon\Carbon;
use App\Models\Quotation;
use Filament\Facades\Filament;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Models\Import;

class QuotationImporter extends Importer
{
    protected static ?string $model = Quotation::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('id'),
            ImportColumn::make('customer')
                ->relationship(resolveUsing: ['email', 'name']),
            ImportColumn::make('numbering')
                ->rules(['max:255']),
            ImportColumn::make('quotation_date')
                ->fillRecordUsing(function ($record, string $state) {
                    $dateTime = new \DateTime($state);
                    $record->quotation_date = $dateTime->format('Y-m-d') ;
                    $record->team_id = Filament::getTenant()->id ;
                }),
            ImportColumn::make('valid_days')
                ->numeric()
                ->rules(['integer']),
            ImportColumn::make('quote_status')
                ->rules(['max:255']),
            ImportColumn::make('summary')
                ->rules(['max:65535']),
            ImportColumn::make('sub_total'),
            ImportColumn::make('taxes'),
            ImportColumn::make('percentage_tax'),
            ImportColumn::make('delivery'),
            ImportColumn::make('final_amount'),
            ImportColumn::make('terms_conditions')
                ->rules(['max:65535']),
            ImportColumn::make('footer')
                ->rules(['max:65535']),
            ImportColumn::make('attachments'),
        ];
    }

    public function resolveRecord(): ?Quotation
    {
        // $this->data['team_id'] = Filament::getTenant()->id ;
        // $dateTime = new \DateTime($this->data['quotation_date']);
        // $this->data['quotation_date'] =  $dateTime->format('Y-m-d') ;
        // dd($this->data);
        // return Quotation::create(
        //     $this->data
        // );;


        return Quotation::firstOrNew([
            // Update existing records, matching them by `$this->data['column_name']`
            'id' => $this->data['id'] ?? null,
        ]);

        // return new Quotation();
    }

    protected function beforeSave()
    {
        $this->record->team_id = Filament::getTenant()->id ;
        // Similar to `beforeSave()`, but only runs when creating a new record.
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your quotation import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
