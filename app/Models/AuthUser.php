<?php

namespace App\Models;

use Illuminate\Auth\Passwords\CanResetPassword as CanResetPasswordTrait;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AuthUser extends Model implements Authenticatable, CanResetPasswordContract
{
    use CanResetPasswordTrait;
    use HasUuids;

    protected $table = 'auth_users';

    protected $fillable = ['email', 'password_hash'];

    protected $hidden = ['password_hash'];

    protected $keyType = 'string';

    public $incrementing = false;

    public const UPDATED_AT = null;

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class, 'id', 'id');
    }

    public function roleRow(): HasOne
    {
        return $this->hasOne(UserRole::class, 'user_id', 'id');
    }

    public function getRoleAttribute(): string
    {
        return $this->roleRow?->role ?? 'user';
    }

    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    public function getAuthIdentifier(): string
    {
        return $this->getKey();
    }

    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    public function getAuthPasswordName(): string
    {
        return 'password_hash';
    }

    public function getRememberToken(): string
    {
        return '';
    }

    public function setRememberToken($value): void {}

    public function getRememberTokenName(): string
    {
        return 'remember_token';
    }

    public function setPasswordAttribute($value): void
    {
        $this->attributes['password_hash'] = Hash::make($value);
    }

    public static function findByEmail(string $email): ?self
    {
        return static::where('email', $email)->first();
    }

    public function getEmailForPasswordReset(): string
    {
        return $this->email;
    }

    public function sendPasswordResetNotification($token): void
    {
        Mail::raw(
            "You are receiving this email because we received a password reset request for your RAHS account.\n\n".
            'Reset your password: '.url('/reset-password/'.$token.'?email='.urlencode($this->email))."\n\n".
            'This password reset link will expire in '.config('auth.passwords.users.expire').' minutes.',
            function ($message) {
                $message->to($this->email)->subject('RAHS Password Reset Notification');
            }
        );
    }
}
