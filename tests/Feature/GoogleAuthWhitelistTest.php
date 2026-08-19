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

        $provider = Mockery::mock('Laravel\Socialite\Two\GoogleProvider');
        $provider->shouldReceive('user')->andReturn($abstractUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        // 3. Trigger callback
        $response = $this->get(route('auth.google.callback'));

        // 4. Assert user is authenticated and redirected to dashboard
        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/dashboard');
    }

    public function test_unauthorized_google_email_is_rejected_and_cannot_log_in(): void
    {
        // 1. Mock unauthorized Google user response
        $abstractUser = Mockery::mock('Laravel\Socialite\Two\User');
        $abstractUser->shouldReceive('getEmail')->andReturn('hacker@unknown.com');

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
}
