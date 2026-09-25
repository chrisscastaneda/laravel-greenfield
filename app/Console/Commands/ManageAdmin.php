<?php

namespace App\Console\Commands;

use App\Actions\Admin\GrantAdmin;
use App\Actions\Admin\RevokeAdmin;
use App\Exceptions\AdminOperationException;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('greenfield:admin {email : The email address of an existing user} {--revoke : Revoke admin access instead of granting it}')]
#[Description('Grant or revoke admin access for a user')]
class ManageAdmin extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(GrantAdmin $grantAdmin, RevokeAdmin $revokeAdmin): int
    {
        $email = strtolower(trim((string) $this->argument('email')));

        $user = User::query()->whereRaw('lower(email) = ?', [$email])->first();

        if ($user === null) {
            $this->components->error("No user found with email [{$email}]. The user must sign in at least once first.");

            return self::FAILURE;
        }

        try {
            if ($this->option('revoke')) {
                $revokeAdmin->handle($user);
                $this->components->info("Revoked admin access from [{$user->email}].");
            } else {
                $grantAdmin->handle($user);
                $this->components->info("Granted admin access to [{$user->email}].");
            }
        } catch (AdminOperationException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
