<?php

namespace App\Http\Responses\Auth;

use App\Filament\Sys\Pages\ChooseCompany;
use App\Models\Team;
use Filament\Facades\Filament;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Session;
use Livewire\Features\SupportRedirects\Redirector;
use Filament\Http\Responses\Auth\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): RedirectResponse | Redirector
    {
        $url = explode('/', Filament::getUrl());
        if($url[3] == 'sys'){
            return redirect()->intended(Filament::getUrl().'/choose-company');
        }elseif($url[3] == 'client'){
            if(Session::get('current_company')){
                $team_slug = Team::where('id', Session::get('current_company'))->first()->slug ?? '';
                return redirect()->intended(url('client/'.$team_slug));
            }
        }

        return redirect()->intended(Filament::getUrl());
      
    }
}