<?php

namespace App\Actions;

use App\Models\User;

class UpdateLocale
{
    public function handle(User $user, string $locale): void
    {
        $user->update(['locale' => $locale]);
    }
}
