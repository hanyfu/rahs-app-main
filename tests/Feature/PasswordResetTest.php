<?php

namespace Tests\Feature;

use App\Models\AuthUser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    public function test_forgot_password_does_not_enumerate_accounts(): void
    {
        $message = 'If an account exists for that email address, we have emailed you a password reset link.';

        $this->post('/forgot-password', ['email' => 'admin@rahs.mv'])
            ->assertRedirect()
            ->assertSessionHas('status', $message);

        $this->post('/forgot-password', ['email' => 'does-not-exist@example.com'])
            ->assertRedirect()
            ->assertSessionHas('status', $message);
    }

    public function test_reset_form_renders(): void
    {
        $this->get('/reset-password/'.str_repeat('a', 40).'?email=admin%40rahs.mv')
            ->assertOk()
            ->assertSee('Choose a new password');
    }

    public function test_full_password_reset_flow(): void
    {
        $user = AuthUser::where('email', 'admin@rahs.mv')->first();
        $token = Password::broker()->createToken($user);

        $this->post('/reset-password', [
            'token' => $token,
            'email' => 'admin@rahs.mv',
            'password' => 'NewlyResetPassword123!',
            'password_confirmation' => 'NewlyResetPassword123!',
        ])->assertRedirect(route('auth.show'));

        $fresh = $user->fresh();
        $this->assertTrue(Hash::check('NewlyResetPassword123!', $fresh->getAuthPassword()));
        $this->assertFalse(Hash::check('password123', $fresh->getAuthPassword()));

        $this->assertGuest();
    }

    public function test_reset_with_invalid_token_is_rejected(): void
    {
        $this->post('/reset-password', [
            'token' => 'invalid-token',
            'email' => 'admin@rahs.mv',
            'password' => 'NewlyResetPassword123!',
            'password_confirmation' => 'NewlyResetPassword123!',
        ])->assertSessionHasErrors('email');

        $user = AuthUser::where('email', 'admin@rahs.mv')->first();
        $this->assertTrue(Hash::check('password123', $user->fresh()->getAuthPassword()));
    }

    public function test_new_password_requires_confirmation(): void
    {
        $user = AuthUser::where('email', 'admin@rahs.mv')->first();
        $token = Password::broker()->createToken($user);

        $this->post('/reset-password', [
            'token' => $token,
            'email' => 'admin@rahs.mv',
            'password' => 'NewlyResetPassword123!',
            'password_confirmation' => 'different-confirmation',
        ])->assertSessionHasErrors('password');
    }
}
