<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Environment Admins
    |--------------------------------------------------------------------------
    |
    | Comma-separated email addresses from ADMIN_EMAILS. A user with one of
    | these addresses is granted admin access when they sign in, and their
    | admin access cannot be revoked (or their account deactivated) from the
    | admin area or the greenfield:admin command. Removing an address from the
    | list does not revoke access that was already granted.
    |
    */

    'emails' => array_values(array_filter(array_map(
        fn (string $email): string => strtolower(trim($email)),
        explode(',', (string) env('ADMIN_EMAILS', '')),
    ))),

];
