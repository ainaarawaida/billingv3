<?php

namespace App\Filament\Sys\Resources\UserAccountResource\Pages;

use Filament\Actions;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Sys\Resources\UserAccountResource;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class CreateUserAccount extends CreateRecord
{
    protected static string $resource = UserAccountResource::class;

    protected function getRedirectUrl(): string
    {
        $resource = static::getResource();
        return $resource::getUrl('index');
    }



}
