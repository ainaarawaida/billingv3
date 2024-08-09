<?php

namespace App\Filament\Sys\Widgets;

use App\Models\Payment;
use Filament\Forms\Get;
use Flowframe\Trend\Trend;
use Filament\Facades\Filament;
use Illuminate\Support\Carbon;
use Flowframe\Trend\TrendValue;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\DatePicker;
use Illuminate\Contracts\Support\Htmlable;

class PaymentChart extends ChartWidget
{
    protected static ?int $sort = 2;
    protected static ?string $heading = 'Payment Report';
    protected int | string | array $columnSpan = '4';
    protected static bool $isLazy = false;
    public ?string $filter = null;
    protected static string $color = 'info';
    protected static ?string $maxHeight = '300px';
    protected function getColumns(): int
    {
        return '1';
    }
    public function getHeading(): string | Htmlable | null
    {
        return static::$heading . ' ' . $this->getFilters()[$this->filter] ;
    }

    public function mount(): void
    {
        parent::mount();
        $this->filter = date('Y');
    }

    public function updating($property, $value)
    {
        if($property == 'filter'){
            $this->dispatch('updateWidgetFilter', $value); 
        }
    }

    
    protected function getFilters(): ?array
    {
        
        $currentYear = date('Y'); // Get the current year as a four-digit integer
        $pastYears = array_reverse(range($currentYear - 3, $currentYear)); 
        $yearRange = array_merge($pastYears); 
        $yearRange = array_combine($yearRange, $yearRange);
        return $yearRange;
    }

    protected function getData(): array
    {
        $activeFilter = $this->filter;
        $data1 = Trend::query(Payment::where('team_id', Filament::getTenant()->id)
        ->where('status', 'completed')->whereYear('payment_date', $activeFilter))
        ->between(
            start: Carbon::parse("$activeFilter-01-01"),
            end: Carbon::parse("$activeFilter-12-31") ,
        )
        ->dateColumn('payment_date')
        ->perMonth()
        ->sum('total');

        $data2 = Trend::query(Payment::where('team_id', Filament::getTenant()->id)
        ->whereIn('status', ['pending_payment','on_hold','processing'])->whereYear('payment_date', $activeFilter))
        ->between(
            start: Carbon::parse("$activeFilter-01-01"),
            end: Carbon::parse("$activeFilter-12-31") ,
        )
        ->dateColumn('payment_date')
        ->perMonth()
        ->sum('total');

        return [
            'datasets' => [
                [
                    'label' => __('Payment Completed (RM)'),
                    'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
                    'borderColor' => 'rgba(255, 99, 132, 1)',
                    'data' => $data1->map(fn (TrendValue $value) => $value->aggregate),
                ],
                [
                    'label' => __('Payment Pending/On Hold/Processing (RM)'),
                    'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                    'borderColor' => 'rgba(54, 162, 235, 1)',
                    'data' => $data2->map(fn (TrendValue $value) => $value->aggregate),
                ],
            ],
            'labels' => $data1->map(fn (TrendValue $value) => Carbon::parse($value->date)->format('M')),
        ];
    }





    protected function getType(): string
    {
        return 'line';
    }
}
