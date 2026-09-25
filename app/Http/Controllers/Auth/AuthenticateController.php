<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\SyncWorkOSUser;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Laravel\WorkOS\Http\Requests\AuthKitAuthenticationRequest;

class AuthenticateController extends Controller
{
    /**
     * Handle the redirect back from WorkOS AuthKit and sign the user in.
     */
    public function __invoke(AuthKitAuthenticationRequest $request, SyncWorkOSUser $users): RedirectResponse
    {
        $request->authenticate(
            findUsing: $users->find(...),
            createUsing: $users->create(...),
            updateUsing: $users->update(...),
        );

        return redirect()->intended(route('dashboard'));
    }
}
