<?php
 
namespace App\Filament\Sys\Pages;

use Livewire\Attributes\Reactive;
 
class Dashboard extends \Filament\Pages\Dashboard
{
    
    public function getColumns(): int | string | array
    {
        return 4;
    }
}