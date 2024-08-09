<?php

namespace App\Filament\Sys\Resources\UserAccountResource\Pages;

use App\Filament\Sys\Resources\UserAccountResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUserAccounts extends ListRecords
{
    protected static string $resource = UserAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
