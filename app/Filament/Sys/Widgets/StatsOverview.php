<?php

namespace App\Filament\Sys\Widgets;

use App\Models\Invoice;
use App\Models\Payment;
use Livewire\Attributes\On;
use Filament\Facades\Filament;
use Filament\Widgets\AccountWidget;
use App\Filament\Sys\Pages\Dashboard;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;
    protected int | string | array $columnSpan = '4';
    protected static bool $isLazy = false;
    public ?string $filter = null;
    
    public function mount(){
        $this->filter = date('Y');
    }

    #[On('updateWidgetFilter')] 
    public function updateWidgetFilter($data){
        $this->filter = $data;
    }
    
    protected function getColumns(): int
    {
        return 4;
    }
    
    protected function getStats(): array
    {
        $invoicePaid = Invoice::where('team_id', Filament::getTenant()->id)
        ->where('invoice_status', 'done')->whereYear('invoice_date', $this->filter)->sum('balance') ;
        $invoiceWaiting = Invoice::where('team_id', Filament::getTenant()->id)
        ->whereIn('invoice_status', ['new','process'])->whereYear('invoice_date', $this->filter)->sum('balance') ;
        

        $received =Payment::where('team_id', Filament::getTenant()->id)
        ->where('status', 'completed')->whereYear('payment_date', $this->filter)->sum('total') ;
        $waiting = Payment::where('team_id', Filament::getTenant()->id)
        ->whereIn('status', ['pending_payment','on_hold','processing'])->whereYear('payment_date', $this->filter)->sum('total');
        return [
            Stat::make(__('Invoice Paid (RM)'), number_format($invoicePaid, 2) )
            ->description($this->filter)
            ->color('success'),
            Stat::make(__('Invoice New/Process (RM)'), number_format($invoiceWaiting, 2))
            ->description($this->filter)
            ->color('success'),
            Stat::make(__('Payment Completed (RM)'), number_format($received, 2) )
            ->description($this->filter)
            ->color('success'),
            Stat::make(__('Payment Pending/On Hold/Processing (RM)'), number_format($waiting, 2))
            ->description($this->filter)
            ->color('success'),
           
        ];
    }


}
