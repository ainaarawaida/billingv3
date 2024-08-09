<?php

use Illuminate\Http\Request;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Route;
use App\Http\Responses\LogoutResponse;



Route::get('/login', function () {
    return redirect(route('filament.admin.auth.login'));
})->name('login');

Route::get('/logout', function (Request $request) {
    Filament::auth()->logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return app(LogoutResponse::class);
})->name('logout');