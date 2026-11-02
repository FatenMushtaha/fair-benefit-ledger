<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Revokes a user's server-side sessions after a membership is revoked.
 *
 * This intentionally logs the user out everywhere. Sessions are not tied to a
 * single organization, and the safer default is to force a fresh sign-in.
 */
class SessionInvalidator
{
    public function forUser(User $user): void
    {
        if (! Schema::hasTable('sessions')) {
            return;
        }

        DB::table('sessions')->where('user_id', $user->getKey())->delete();
    }
}
