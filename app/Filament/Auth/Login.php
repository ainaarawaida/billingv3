<?php

namespace App\Filament\Auth;

use Filament\Forms\Form;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Blade;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\RichEditor;
use Filament\Pages\Auth\Login as BaseAuth;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Validation\ValidationException;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;

class Login extends BaseAuth
{
    /**
     * Get the form for the resource.
     */

    /**
     * Get the username form component.
     */


    /**
     * Get the credentials from the form data.
     */

    /**
     * Authenticate the user.
     */
    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label(__('filament-panels::pages/auth/login.form.email.label'))
            ->email()
            ->required()
            ->autocomplete()
            ->autofocus()
            ->default('luqman@test.com')
            ->extraInputAttributes(['tabindex' => 1]);
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label(__('filament-panels::pages/auth/login.form.password.label'))
            ->hint(filament()->hasPasswordReset() ? new HtmlString(Blade::render('<x-filament::link :href="filament()->getRequestPasswordResetUrl()" tabindex="3"> {{ __(\'filament-panels::pages/auth/login.actions.request_password_reset.label\') }}</x-filament::link>')) : null)
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->autocomplete('current-password')
            ->required()
            ->default('luqman1234')
            ->extraInputAttributes(['tabindex' => 2]);
    }




    public function hasLogo(): bool
    {
        return true;
    }

    
    protected function getFormActions(): array
    {
        return [
            Action::make('Back')
            ->url('/')
            ->extraAttributes(['wire:navigate' => 'true', 'style' => 'width:100%;','class' => 'bg-gray-400']),    
            $this->getAuthenticateFormAction()
            ->extraAttributes(['style' => 'width:100%;']),   
        ];
    }

    protected function hasFullWidthFormActions(): bool
    {
        return true;
    }
}
