<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:make-admin {email : Email of an existing (registered) user} {--role=admin : admin or editor}')]
#[Description('Give an existing user the admin or editor role (for the first admin after deploy)')]
class MakeAdmin extends Command
{
    public function handle(): int
    {
        $role = UserRole::tryFrom((string) $this->option('role'));
        if (! $role || $role === UserRole::Player) {
            $this->error('Role must be "admin" or "editor".');

            return self::FAILURE;
        }

        $user = User::where('email', (string) $this->argument('email'))->first();
        if (! $user) {
            $this->error('No user with that email. Register on the site first, then run this again.');

            return self::FAILURE;
        }

        $user->forceFill(['role' => $role])->save();

        $this->info("{$user->email} is now {$role->label()}.");

        return self::SUCCESS;
    }
}
