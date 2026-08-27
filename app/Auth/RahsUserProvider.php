<?php

namespace App\Auth;

use App\Models\AuthUser;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Support\Facades\Hash;

class RahsUserProvider implements UserProvider
{
    public function retrieveById($identifier): ?Authenticatable
    {
        return AuthUser::query()->find($identifier);
    }

    public function retrieveByToken($identifier, $token): ?Authenticatable
    {
        return null;
    }

    public function updateRememberToken(Authenticatable $user, $token): void {}

    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        if (empty($credentials['email'])) {
            return null;
        }

        return AuthUser::query()->where('email', $credentials['email'])->first();
    }

    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        return isset($credentials['password'])
            && Hash::check($credentials['password'], $user->getAuthPassword());
    }

    public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false): void
    {
        if (Hash::needsRehash($user->getAuthPassword())) {
            $user->password_hash = Hash::make($credentials['password']);
            $user->save();
        }
    }
}
