<?php

namespace App\Http\Responses;

use Filament\Http\Responses\Auth\Contracts\LoginResponse as LoginResponseContract;
use Illuminate\Http\RedirectResponse;

class CustomLoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        return redirect(\App\Filament\Resources\InvoiceResource::getUrl('create'));
    }
}
