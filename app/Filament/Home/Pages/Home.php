<?php

namespace App\Filament\Home\Pages;

use Filament\Pages\Page;

class Home extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $slug = '/';
    protected static string $view = 'filament.home.pages.home';
    protected ?string $heading = '';


}
