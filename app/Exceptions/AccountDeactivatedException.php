<?php

namespace App\Exceptions;

use Illuminate\Http\RedirectResponse;
use RuntimeException;

/**
 * Thrown when a deactivated user tries to sign in.
 */
class AccountDeactivatedException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('This account has been deactivated.');
    }

    /**
     * Render the exception into an HTTP response.
     */
    public function render(): RedirectResponse
    {
        return to_route('account.deactivated');
    }
}
