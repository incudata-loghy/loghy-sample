<?php

namespace Tests\Feature\Http\Controllers\Auth;

use App\Lib\Loghy\Facades\Loghy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Log;
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

        Loghy::shouldReceive('appendCallbackHistory')->once();
        Loghy::shouldReceive('getLoghyId')
            ->once()
            ->with('xxxxxxxxxxxxxxxxxxxx')
            ->andReturn([
                'loghyId' => $user->loghy_id,
                'userId' => $user->id,
            ]);
        Loghy::shouldReceive('deleteUserInfo')
            ->once()
            ->with($user->loghy_id)
            ->andReturn(true);
        Loghy::shouldReceive('history')->once()->andReturn([]);

        $request_data = [ 'code' => 'xxxxxxxxxxxxxxxxxxxx' ];
        $response = $this->call('GET', route('auth.loghy.callback.login'), $request_data);

        $response
            ->assertRedirect(route('home'))
            ->assertSessionHas('success', 'Logged in 🎉');
    }

    public function testHandleLoginCallbackRedirectToHomeWhenLoggedIn()
    {
        Loghy::shouldReceive('appendCallbackHistory')->once();
        Loghy::shouldReceive('getLoghyId')
            ->once()
            ->with('xxxxxxxxxxxxxxxxxxxx')
            ->andReturn([
                'loghyId' => '11',
                'userId' => '1',
            ]);
        Loghy::shouldReceive('deleteUserInfo')
            ->once()
            ->with('11')
            ->andReturn(true);
        Loghy::shouldReceive('history')->once()->andReturn([]);

        /** @var User $user */
        $user = User::factory()->create();
        $request_data = ['code' => 'xxxxxxxxxxxxxxxxxxxx'];
        $response = $this->actingAs($user)
            ->call('GET', route('auth.loghy.callback.login'), $request_data);

        $response
            ->assertRedirect(route('home'))
            ->assertSessionHas('success', 'Already connected 👍');
    }

    public function testHandleLoginRedirectToRegisterWhenNoCode()
    {
        $response = $this->call('GET', route('auth.loghy.callback.login'), []);

        $response
            ->assertRedirect(route('register'))
            ->assertSessionHas('error', 'Authentication code is not found in callback data.');
    }

    public function testHandleLoginCallbackRedirectToRegisterWhenFailedToGetLoghyIdByCode()
    {
        Loghy::shouldReceive('appendCallbackHistory')->once();
        Loghy::shouldReceive('getLoghyId')
            ->once()
            ->with('xxxxxxxxxxxxxxxxxxxx')
            ->andThrows(new \Exception());
        Loghy::shouldReceive('history')->once()->andReturn([]);

        $request_data = ['code' => 'xxxxxxxxxxxxxxxxxxxx'];
        $response = $this->call('GET', route('auth.loghy.callback.login'), $request_data);

        $response
            ->assertRedirect(route('register'))
            ->assertSessionHas('error', 'Failed to get LoghyID by authentication code.');
    }

    public function testHandleLoginCallbackRedirectToRegisterWhenNoLoghyId()
    {
        Loghy::shouldReceive('appendCallbackHistory')->once();
        Loghy::shouldReceive('getLoghyId')
            ->once()
            ->with('xxxxxxxxxxxxxxxxxxxx')
            ->andReturn([
                'loghyId' => null,
                'userId' => '1'
            ]);
        Loghy::shouldReceive('history')->once()->andReturn([]);

        $request_data = ['code' => 'xxxxxxxxxxxxxxxxxxxx'];
        $response = $this->call('GET', route('auth.loghy.callback.login'), $request_data);

        $response
            ->assertRedirect(route('register'))
            ->assertSessionHas('error', 'Failed to get LoghyID by authentication code.');
    }

    public function testHandleLoginCallbackRedirectToRegisterWhenNoSiteId()
    {
        Loghy::shouldReceive('appendCallbackHistory')->once();
        Loghy::shouldReceive('getLoghyId')
            ->twice()
            ->with('xxxxxxxxxxxxxxxxxxxx')
            ->andReturn([
                'loghyId' => '11',
                'userId' => null,
            ]);
        Loghy::shouldReceive('history')->once()->andReturn([]);

        $request_data = ['code' => 'xxxxxxxxxxxxxxxxxxxx'];
        $response = $this->call('GET', route('auth.loghy.callback.login'), $request_data);

        $response
            ->assertRedirect(route('register'))
            ->assertSessionHas('error', 'Failed to get UserID(site_id) by authentication code.');
    }

    public function testHandleLoginCallbackRedirectToRegisterWhenInvalidLoghyId()
    {
        $user = User::factory()->create([
            'loghy_id' => $this->faker->randomDigitNot(0)
        ]);

        Loghy::shouldReceive('appendCallbackHistory')->once();
        Loghy::shouldReceive('getLoghyId')
            ->once()
            ->with('xxxxxxxxxxxxxxxxxxxx')
            ->andReturn([
                'loghyId' => $user->loghy_id + 1,
                'userId' => $user->id,
            ]);
        Loghy::shouldReceive('deleteUserInfo')
            ->once()
            ->with($user->loghy_id + 1)
            ->andReturn(true);
        Loghy::shouldReceive('history')->once()->andReturn([]);

        $request_data = [ 'code' => 'xxxxxxxxxxxxxxxxxxxx' ];
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

        Loghy::shouldReceive('appendCallbackHistory')->once();
        Loghy::shouldReceive('getLoghyId')
            ->once()
            ->with('xxxxxxxxxxxxxxxxxxxx')
            ->andReturn([
                'loghyId' => $user->loghy_id,
                'userId' => $user->id + 1,
            ]);
        Loghy::shouldReceive('deleteUserInfo')
            ->once()
            ->with($user->loghy_id)
            ->andReturn(true);
        Loghy::shouldReceive('history')->once()->andReturn([]);

        $request_data = [ 'code' => 'xxxxxxxxxxxxxxxxxxxx' ];
        $response = $this->call('GET', route('auth.loghy.callback.login'), $request_data);

        $response
            ->assertRedirect(route('register'))
            ->assertSessionHas('error', 'User not found with specified UserID(site_id) and LoghyID.');
    }

    public function testHandleLoginCallbackRedirectToHomeAndErrorLogIsOutputWhenThrowsException()
    {
        $user = User::factory()->create([
            'loghy_id' => $this->faker->randomDigitNot(0)
        ]);

        Loghy::shouldReceive('appendCallbackHistory')->once();
        Loghy::shouldReceive('getLoghyId')
            ->once()
            ->with('xxxxxxxxxxxxxxxxxxxx')
            ->andReturn([
                'loghyId' => $user->loghy_id,
                'userId' => $user->id,
            ]);
        Loghy::shouldReceive('deleteUserInfo')
            ->once()
            ->with($user->loghy_id)
            ->andThrow(new \Exception());
        Loghy::shouldReceive('history')->once()->andReturn([]);

        Log::shouldReceive('error')
            ->once()
            ->with("Failed to delete user information in Loghy. Its LoghyID is {$user->loghy_id}");

        $request_data = [ 'code' => 'xxxxxxxxxxxxxxxxxxxx' ];
        $response = $this->call('GET', route('auth.loghy.callback.login'), $request_data);

        $response
            ->assertRedirect(route('home'))
            ->assertSessionHas('success', 'Logged in 🎉');
    }

    public function testHandleRegisterCallbackCreateUserAndRedirectToHomeWhenNotLoggedIn()
    {
        Loghy::shouldReceive('appendCallbackHistory')->once();
        Loghy::shouldReceive('getLoghyId')
            ->once()
            ->with('xxxxxxxxxxxxxxxxxxxx')
            ->andReturn([
                'loghyId' => '11',
                'userId' => null,
            ]);
        Loghy::shouldReceive('getUserInfo')
            ->once()
            ->with('11')
            ->andReturn([
                'name' => $this->faker()->name(),
                'email' => $this->faker()->email(),
            ]);
        Loghy::shouldReceive('deleteUserInfo')
            ->once()
            ->with('11')
            ->andReturn(true);
        Loghy::shouldReceive('putUserId')->once()->andReturn(true);
        Loghy::shouldReceive('history')->once()->andReturn([]);

        $request_data = [ 'code' => 'xxxxxxxxxxxxxxxxxxxx' ];
        $response = $this->call('GET', route('auth.loghy.callback.register'), $request_data);

        $response
            ->assertRedirect(route('home'))
            ->assertSessionHas('success', 'Registered 🎉');
        $this->assertDatabaseCount('users', 1);
    }

    public function testHandleRegisterRedirectToRegisterWhenNoCode()
    {
        $response = $this->call('GET', route('auth.loghy.callback.register'), []);

        $response
            ->assertRedirect(route('register'))
            ->assertSessionHas('error', 'Authentication code is not found in callback data.');
    }

    public function testHandleRegisterCallbackRedirectToRegisterWhenNoLoghyId()
    {
        Loghy::shouldReceive('appendCallbackHistory')->once();
        Loghy::shouldReceive('getLoghyId')
            ->once()
            ->with('xxxxxxxxxxxxxxxxxxxx')
            ->andReturn([
                'loghyId' => null,
                'userId' => null,
            ]);
        Loghy::shouldReceive('history')->once()->andReturn([]);

        $request_data = [ 'code' => 'xxxxxxxxxxxxxxxxxxxx' ];
        $response = $this->call('GET', route('auth.loghy.callback.register'), $request_data);

        $response
            ->assertRedirect(route('register'))
            ->assertSessionHas('error', 'Failed to get LoghyID by authentication code.');
    }

    public function testHandleRegisterCallbackConnectUserAndRedirectToHomeWhenLoggedIn()
    {
        $user = User::factory()->create(['loghy_id' => '11']);

        Loghy::shouldReceive('appendCallbackHistory')->once();
        Loghy::shouldReceive('getLoghyId')
            ->once()
            ->with('xxxxxxxxxxxxxxxxxxxx')
            ->andReturn([
                'loghyId' => '12',
                'userId' => null,
            ]);
        Loghy::shouldReceive('mergeUser')
            ->once()
            ->with('11', '12')
            ->andReturn(true);
        Loghy::shouldReceive('deleteUserInfo')
            ->once()
            ->with('12')
            ->andReturn(true);
        Loghy::shouldReceive('history')->once()->andReturn([]);

        $request_data = [ 'code' => 'xxxxxxxxxxxxxxxxxxxx' ];
        $response = $this->actingAs($user)
            ->call('GET', route('auth.loghy.callback.register'), $request_data);

        $response
            ->assertRedirect(route('home'))
            ->assertSessionHas('success', 'Connected 🎉');
    }
}
