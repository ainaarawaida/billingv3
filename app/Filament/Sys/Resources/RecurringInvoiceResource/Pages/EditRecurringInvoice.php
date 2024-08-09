<?php

namespace App\Filament\Sys\Resources\RecurringInvoiceResource\Pages;

use Filament\Actions;
use Livewire\Attributes\On;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Sys\Resources\RecurringInvoiceResource;

class EditRecurringInvoice extends EditRecord
{
    protected static string $resource = RecurringInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    protected function getSaveFormAction(): Action
    {
        return Action::make('save')
            ->label(__('filament-panels::resources/pages/edit-record.form.actions.save.label'))
            ->keyBindings(['mod+s'])
            ->action(function () {
                $this->save();
            });
    }

    protected function getRedirectUrl(): string
    {
        $resource = static::getResource();
        return $resource::getUrl('index');
    }

    
}
