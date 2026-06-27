<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;

class CustomLogin extends BaseLogin
{
    /**
     * @var view-string
     */
    protected string $view = 'filament.pages.auth.login';
}
