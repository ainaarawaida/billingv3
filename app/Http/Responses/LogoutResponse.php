<?php

namespace App\Http\Responses;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Session;
use Filament\Http\Responses\Auth\Contracts\LogoutResponse as Responsable;


class LogoutResponse implements Responsable
{

 
    public function toResponse($request): RedirectResponse
    {
        $previousUrl = url()->previous();
        $url = explode('/', $previousUrl);
        if($url[3] == 'client'){
            return redirect()->intended(url('/'));
        }
    
        // change this to your desired route
        return redirect()->route('login');
    }
}