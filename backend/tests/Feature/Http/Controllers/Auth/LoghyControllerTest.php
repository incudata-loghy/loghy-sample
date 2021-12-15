<?php

namespace Tests\Feature\Http\Controllers\Auth;

use App\Lib\Loghy\Facades\Loghy;
use App\Models\User;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class LoghyControllerTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    public function testHandleLoginCallbackLogInAndRedirectToHomeWhenNotLoggedIn()
    {
        $user = User::factory()->create([
            'loghy_id' => $this->faker->randomDigitNot(0)
        ]);
        $request_data = [
            'site_id' => $user->id,
            'lgid' => $user->loghy_id,
        ];

        Loghy::shouldReceive('appendCallbackHistory')->once();
        Loghy::shouldReceive('deleteUserInfo')
            ->once()
            ->with($request_data['lgid'])
            ->andReturn(true);
        Loghy::shouldReceive('history')->once()->andReturn([]);

        $response = $this->call('GET', route('auth.loghy.callback.login'), $request_data);

        $response
            ->assertRedirect(route('home'))
            ->assertSessionHas('success', 'Logged in 🎉');
    }

    public function testHandleLoginCallbackRedirectToHomeWhenLoggedIn()
    {
        $request_data = [
            'site_id' => $this->faker->randomDigitNot(0),
            'lgid' => $this->faker->randomDigitNot(0),
        ];

        Loghy::shouldReceive('appendCallbackHistory')->once();
        Loghy::shouldReceive('deleteUserInfo')
            ->once()
            ->with($request_data['lgid'])
            ->andReturn(true);
        Loghy::shouldReceive('history')->once()->andReturn([]);

        /** @var User $user */
        $user = User::factory()->create();
        $response = $this->actingAs($user)
            ->call('GET', route('auth.loghy.callback.login'), $request_data);

        $response
            ->assertRedirect(route('home'))
            ->assertSessionHas('success', 'Already connected 👍');
    }

    public function testHandleLoginCallbackRedirectToRegisterWhenNoLoghyId()
    {
        $request_data = [
            'site_id' => $this->faker->randomDigitNot(0)
        ];
        $response = $this->call('GET', route('auth.loghy.callback.login'), $request_data);

        $response
            ->assertRedirect(route('register'))
            ->assertSessionHas('error', 'LoghyID is not found in callback data.');
    }

    public function testHandleLoginCallbackRedirectToRegisterWhenNoSiteId()
    {
        $request_data = [
            'lgid' => $this->faker->randomDigitNot(0)
        ];
        $response = $this->call('GET', route('auth.loghy.callback.login'), $request_data);

        $response
            ->assertRedirect(route('register'))
            ->assertSessionHas('error', 'UserID(site_id) is not found in callback data.');
    }

    public function testHandleLoginCallbackRedirectToRegisterWhenInvalidLoghyId()
    {
        $user = User::factory()->create([
            'loghy_id' => $this->faker->randomDigitNot(0)
        ]);
        $request_data = [
            'site_id' => $user->id,
            'lgid' => $user->loghy_id + 1,
        ];

        Loghy::shouldReceive('appendCallbackHistory')->once();
        Loghy::shouldReceive('deleteUserInfo')
            ->once()
            ->with($request_data['lgid'])
            ->andReturn(true);
        Loghy::shouldReceive('history')->once()->andReturn([]);


        $response = $this->call('GET', route('auth.loghy.callback.login'), $request_data);

        $response
            ->assertRedirect(route('register'))
            ->assertSessionHas('error', 'User not found with specified UserID(site_id) and LoghyID.');
    }

    public function testHandleLoginCallbackRedirectToRegisterWhenInvalidSiteId()
    {
        $user = User::factory()->create([
            'loghy_id' => $this->faker->randomDigitNot(0)
        ]);
        $request_data = [
            'site_id' => $user->id + 1,
            'lgid' => $user->loghy_id,
        ];

        Loghy::shouldReceive('appendCallbackHistory')->once();
        Loghy::shouldReceive('deleteUserInfo')
            ->once()
            ->with($request_data['lgid'])
            ->andReturn(true);
        Loghy::shouldReceive('history')->once()->andReturn([]);


        $response = $this->call('GET', route('auth.loghy.callback.login'), $request_data);

        $response
            ->assertRedirect(route('register'))
            ->assertSessionHas('error', 'User not found with specified UserID(site_id) and LoghyID.');
    }

    // TODO: fix 500
    public function testHandleLoginCallback500WhenThrowsException()
    {
        $user = User::factory()->create([
            'loghy_id' => $this->faker->randomDigitNot(0)
        ]);
        $request_data = [
            'site_id' => $user->id,
            'lgid' => $user->loghy_id,
        ];

        Loghy::shouldReceive('appendCallbackHistory')->once();
        Loghy::shouldReceive('deleteUserInfo')
            ->once()
            ->with($request_data['lgid'])
            ->andThrow(new Exception());

        $response = $this->call('GET', route('auth.loghy.callback.login'), $request_data);

        $response->assertStatus(500);
    }

    public function testHandleRegisterCallbackCreateUserAndRedirectToHomeWhenNotLoggedIn()
    {
        $request_data = [
            'lgid' => $this->faker->randomDigitNot(0)
        ];

        Loghy::shouldReceive('appendCallbackHistory')->once();
        Loghy::shouldReceive('getUserInfo')
            ->once()
            ->with($request_data['lgid'])
            ->andReturn([
                'name' => $this->faker()->name(),
                'email' => $this->faker()->email(),
            ]);
        Loghy::shouldReceive('deleteUserInfo')
            ->once()
            ->with($request_data['lgid'])
            ->andReturn(true);
        Loghy::shouldReceive('putUserId')->once()->andReturn(true);
        Loghy::shouldReceive('history')->once()->andReturn([]);

        $response = $this->call('GET', route('auth.loghy.callback.register'), $request_data);

        $response
            ->assertRedirect(route('home'))
            ->assertSessionHas('success', 'Registered 🎉');
        $this->assertDatabaseCount('users', 1);
    }

    public function testHandleRegisterCallbackRedirectToRegisterWhenNoLoghyId()
    {
        $request_data = [];
        $response = $this->call('GET', route('auth.loghy.callback.register'), $request_data);

        $response
            ->assertRedirect(route('register'))
            ->assertSessionHas('error', 'LoghyID is not found in callback data.');
    }

    public function testHandleRegisterCallbackConnectUserAndRedirectToHomeWhenLoggedIn()
    {
        $user = User::factory()->create([
            'loghy_id' => $this->faker->randomDigitNot(0),
        ]);
        $request_data = [
            'lgid' => $user->loghy_id + 1
        ];

        Loghy::shouldReceive('appendCallbackHistory')->once();
        Loghy::shouldReceive('mergeUser')
            ->once()
            ->with($user->loghy_id, $request_data['lgid'])
            ->andReturn(true);
        Loghy::shouldReceive('deleteUserInfo')
            ->once()
            ->with($request_data['lgid'])
            ->andReturn(true);
        Loghy::shouldReceive('history')->once()->andReturn([]);

        $response = $this->actingAs($user)
            ->call('GET', route('auth.loghy.callback.register'), $request_data);

        $response
            ->assertRedirect(route('home'))
            ->assertSessionHas('success', 'Connected 🎉');
    }
}
