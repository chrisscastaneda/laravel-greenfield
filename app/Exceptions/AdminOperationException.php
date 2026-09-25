<?php

namespace App\Exceptions;

use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use RuntimeException;

/**
 * Thrown when an admin operation would break one of the admin safeguards.
 */
class AdminOperationException extends RuntimeException
{
    public static function alreadyAdmin(): self
    {
        return new self('This user is already an admin.');
    }

    public static function notAdmin(): self
    {
        return new self('This user is not an admin.');
    }

    public static function deactivated(): self
    {
        return new self('Reactivate this user before granting admin access.');
    }

    public static function alreadyDeactivated(): self
    {
        return new self('This user is already deactivated.');
    }

    public static function notDeactivated(): self
    {
        return new self('This user is not deactivated.');
    }

    public static function environmentAdmin(): self
    {
        return new self('This user is listed in ADMIN_EMAILS. Remove them from ADMIN_EMAILS first.');
    }

    public static function lastAdmin(): self
    {
        return new self('This user is the only active admin. Grant admin access to someone else first.');
    }

    /**
     * Render the exception into an HTTP response.
     */
    public function render(): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'error', 'message' => $this->getMessage()]);

        return back();
    }
}
