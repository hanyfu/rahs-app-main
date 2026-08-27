<?php

namespace Tests\Feature;

use App\Models\AuthUser;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    public function test_login_page_is_marked_as_public_for_client_initialization(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('<meta name="app-authenticated" content="false">', false);
    }

    public function test_login_redirects_authenticated_users_away_from_login_page(): void
    {
        $user = AuthUser::where('email', 'admin@rahs.mv')->first();

        $this->actingAs($user)
            ->get('/login')
            ->assertRedirect(route('dashboard'));
    }

    public function test_valid_credentials_redirect_to_dashboard(): void
    {
        $this->post('/auth/login', [
            'email' => 'admin@rahs.mv',
            'password' => 'password123',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        $this->post('/auth/login', [
            'email' => 'admin@rahs.mv',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_unknown_email_is_rejected(): void
    {
        $this->post('/auth/login', [
            'email' => 'nobody@example.com',
            'password' => 'password123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_login_is_rate_limited_after_five_attempts(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/auth/login', [
                'email' => 'admin@rahs.mv',
                'password' => 'wrong-password',
            ])->assertStatus(302);
        }

        $this->post('/auth/login', [
            'email' => 'admin@rahs.mv',
            'password' => 'wrong-password',
        ])->assertStatus(429);
    }

    public function test_logout_invalidates_the_session(): void
    {
        $user = AuthUser::where('email', 'admin@rahs.mv')->first();
        $this->actingAs($user);

        $this->post('/auth/logout')
            ->assertRedirect(route('auth.show'));

        $this->assertGuest();
    }

    public function test_change_password_requires_current_password(): void
    {
        $user = AuthUser::where('email', 'admin@rahs.mv')->first();
        $this->actingAs($user);

        $this->postJson('/api/change-password', [
            'current_password' => 'not-the-current-password',
            'password' => 'BrandNewPassword123!',
            'password_confirmation' => 'BrandNewPassword123!',
        ])->assertStatus(422);

        $this->assertTrue(Hash::check('password123', $user->fresh()->getAuthPassword()));
    }

    public function test_change_password_succeeds_with_correct_current_password(): void
    {
        $user = AuthUser::where('email', 'admin@rahs.mv')->first();
        $this->actingAs($user);

        $this->postJson('/api/change-password', [
            'current_password' => 'password123',
            'password' => 'BrandNewPassword123!',
            'password_confirmation' => 'BrandNewPassword123!',
        ])->assertOk();

        $this->assertTrue(Hash::check('BrandNewPassword123!', $user->fresh()->getAuthPassword()));
    }
}
