<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_and_register_show_continue_with_google(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Continue with Google')
            ->assertSee(route('auth.google'), false);

        $this->get(route('register'))
            ->assertOk()
            ->assertSee('Continue with Google')
            ->assertSee(route('auth.google'), false);
    }

    public function test_google_redirect_sends_user_to_provider(): void
    {
        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('redirect')
            ->once()
            ->andReturn(redirect('https://accounts.google.com/o/oauth2/auth'));

        Socialite::shouldReceive('driver')->with('google')->once()->andReturn($provider);

        $this->get(route('auth.google'))
            ->assertRedirect('https://accounts.google.com/o/oauth2/auth');
    }

    public function test_google_callback_creates_new_user_and_logs_in(): void
    {
        $this->mockGoogleUser([
            'id' => 'google-new-1',
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
        ]);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();

        $user = User::query()->where('email', 'ada@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('google-new-1', $user->google_id);
        $this->assertSame('Ada Lovelace', $user->name);
        $this->assertNull($user->password);
        $this->assertNotNull($user->email_verified_at);
        $this->assertSame(0, $user->ai_credits);
    }

    public function test_google_callback_links_existing_password_user_by_email(): void
    {
        $user = User::factory()->create([
            'email' => 'linked@example.com',
            'google_id' => null,
            'email_verified_at' => null,
            'ai_credits' => 3,
        ]);

        $this->mockGoogleUser([
            'id' => 'google-link-1',
            'name' => 'Linked User',
            'email' => 'linked@example.com',
        ]);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user->fresh());

        $user->refresh();
        $this->assertSame('google-link-1', $user->google_id);
        $this->assertNotNull($user->email_verified_at);
        $this->assertSame(3, $user->ai_credits);
        $this->assertNotNull($user->password);
    }

    public function test_google_callback_logs_in_existing_google_user(): void
    {
        $user = User::factory()->create([
            'email' => 'returning@example.com',
            'google_id' => 'google-return-1',
            'password' => null,
        ]);

        $this->mockGoogleUser([
            'id' => 'google-return-1',
            'name' => 'Returning User',
            'email' => 'returning@example.com',
        ]);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertSame(1, User::query()->count());
    }

    public function test_google_callback_failure_redirects_to_login_with_error(): void
    {
        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->once()->andThrow(new \RuntimeException('denied'));

        Socialite::shouldReceive('driver')->with('google')->once()->andReturn($provider);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('error');

        $this->assertGuest();
    }

    /**
     * @param  array{id: string, name: string, email: string}  $attributes
     */
    private function mockGoogleUser(array $attributes): void
    {
        $socialiteUser = (new SocialiteUser)->map([
            'id' => $attributes['id'],
            'nickname' => null,
            'name' => $attributes['name'],
            'email' => $attributes['email'],
            'avatar' => null,
            'avatar_original' => null,
        ]);

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->once()->andReturn($socialiteUser);

        Socialite::shouldReceive('driver')->with('google')->once()->andReturn($provider);
    }
}
