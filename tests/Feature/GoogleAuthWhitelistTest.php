<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class GoogleAuthWhitelistTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_redirect_returns_redirect_response(): void
    {
        $response = $this->get(route('auth.google'));

        $response->assertRedirect();

        parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);
        $this->assertSame('select_account', $query['prompt'] ?? null);
    }

    public function test_preregistered_google_email_logs_in_successfully(): void
    {
        // 1. Create a pre-registered user in DB
        $user = User::factory()->create([
            'email' => 'admin@droyal.com',
            'role' => 'admin',
        ]);

        // 2. Mock Google Socialite user response
        $abstractUser = Mockery::mock('Laravel\Socialite\Two\User');
        $abstractUser->shouldReceive('getEmail')->andReturn('admin@droyal.com');
        $abstractUser->shouldReceive('getId')->andReturn('google-user-123');
        $abstractUser->shouldReceive('getRaw')->andReturn(['email_verified' => true]);

        $provider = Mockery::mock('Laravel\Socialite\Two\GoogleProvider');
        $provider->shouldReceive('user')->andReturn($abstractUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        // 3. Trigger callback
        $response = $this->get(route('auth.google.callback'));

        // 4. Assert user is authenticated and redirected to dashboard
        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/dashboard');
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'google_id' => 'google-user-123',
        ]);
    }

    public function test_unauthorized_google_email_is_rejected_and_cannot_log_in(): void
    {
        // 1. Mock unauthorized Google user response
        $abstractUser = Mockery::mock('Laravel\Socialite\Two\User');
        $abstractUser->shouldReceive('getEmail')->andReturn('hacker@unknown.com');
        $abstractUser->shouldReceive('getId')->andReturn('google-hacker-456');
        $abstractUser->shouldReceive('getRaw')->andReturn(['email_verified' => true]);

        $provider = Mockery::mock('Laravel\Socialite\Two\GoogleProvider');
        $provider->shouldReceive('user')->andReturn($abstractUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        // 2. Trigger callback
        $response = $this->get(route('auth.google.callback'));

        // 3. Assert user is NOT authenticated and redirected back to login with error
        $this->assertGuest();
        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors(['email']);
        $this->assertDatabaseMissing('users', ['email' => 'hacker@unknown.com']);
    }

    public function test_linked_account_rejects_a_different_google_identity(): void
    {
        User::factory()->create([
            'email' => 'staff@droyal.com',
            'google_id' => 'original-google-id',
        ]);

        $abstractUser = Mockery::mock('Laravel\Socialite\Two\User');
        $abstractUser->shouldReceive('getEmail')->andReturn('staff@droyal.com');
        $abstractUser->shouldReceive('getId')->andReturn('different-google-id');
        $abstractUser->shouldReceive('getRaw')->andReturn(['email_verified' => true]);

        $provider = Mockery::mock('Laravel\Socialite\Two\GoogleProvider');
        $provider->shouldReceive('user')->andReturn($abstractUser);
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email']);

        $this->assertGuest();
    }

    public function test_unverified_google_email_is_rejected(): void
    {
        User::factory()->create(['email' => 'staff@droyal.com']);

        $abstractUser = Mockery::mock('Laravel\Socialite\Two\User');
        $abstractUser->shouldReceive('getEmail')->andReturn('staff@droyal.com');
        $abstractUser->shouldReceive('getId')->andReturn('google-user-789');
        $abstractUser->shouldReceive('getRaw')->andReturn(['email_verified' => false]);

        $provider = Mockery::mock('Laravel\Socialite\Two\GoogleProvider');
        $provider->shouldReceive('user')->andReturn($abstractUser);
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email']);

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['google_id' => 'google-user-789']);
    }

    public function test_google_login_still_requires_enabled_two_factor_challenge(): void
    {
        $user = User::factory()->create([
            'email' => 'secure@droyal.com',
            'two_factor_secret' => encrypt('two-factor-secret'),
            'two_factor_confirmed_at' => now(),
        ]);

        $abstractUser = Mockery::mock('Laravel\Socialite\Two\User');
        $abstractUser->shouldReceive('getEmail')->andReturn('secure@droyal.com');
        $abstractUser->shouldReceive('getId')->andReturn('secure-google-id');
        $abstractUser->shouldReceive('getRaw')->andReturn(['email_verified' => true]);

        $provider = Mockery::mock('Laravel\Socialite\Two\GoogleProvider');
        $provider->shouldReceive('user')->andReturn($abstractUser);
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('two-factor.login'));

        $this->assertGuest();
        $this->assertSame($user->id, session('login.id'));
    }
}
