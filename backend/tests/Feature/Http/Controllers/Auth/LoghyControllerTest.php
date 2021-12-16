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
        $user = User::factory()->hasSocialIdentities(1, ['loghy_id' => '111'])->create();

        Loghy::shouldReceive('appendCallbackHistory')->once();
        Loghy::shouldReceive('getLoghyId')
            ->once()
            ->with('xxxxxxxxxxxxxxxxxxxx')
            ->andReturn([
                'loghyId' => '111',
                'userId' => $user->id,
            ]);
        Loghy::shouldReceive('deleteUserInfo')
            ->once()
            ->with('111')
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
        $user = User::factory()->hasSocialIdentities(1, ['loghy_id' => '111'])->create();

        Loghy::shouldReceive('appendCallbackHistory')->once();
        Loghy::shouldReceive('getLoghyId')
            ->once()
            ->with('xxxxxxxxxxxxxxxxxxxx')
            ->andReturn([
                'loghyId' => '111',
                'userId' => $user->id,
            ]);
        Loghy::shouldReceive('deleteUserInfo')
            ->once()
            ->with('111')
            ->andReturn(true);
        Loghy::shouldReceive('history')->once()->andReturn([]);


        $request_data = ['code' => 'xxxxxxxxxxxxxxxxxxxx'];
        $response = $this->actingAs($user)
            ->call('GET', route('auth.loghy.callback.login'), $request_data);

        $response
            ->assertRedirect(route('home'))
            ->assertSessionHas('success', 'Already logged in or connected 👍');
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
                'loghyId' => '111',
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
        $user = User::factory()->hasSocialIdentities(1, ['loghy_id' => '111'])->create();

        Loghy::shouldReceive('appendCallbackHistory')->once();
        Loghy::shouldReceive('getLoghyId')
            ->once()
            ->with('xxxxxxxxxxxxxxxxxxxx')
            ->andReturn([
                'loghyId' => '222',
                'userId' => $user->id,
            ]);
        Loghy::shouldReceive('deleteUserInfo')
            ->once()
            ->with('222')
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
        $user = User::factory()->hasSocialIdentities(1, ['loghy_id' => '111'])->create();

        Loghy::shouldReceive('appendCallbackHistory')->once();
        Loghy::shouldReceive('getLoghyId')
            ->once()
            ->with('xxxxxxxxxxxxxxxxxxxx')
            ->andReturn([
                'loghyId' => '111',
                'userId' => $user->id + 1,
            ]);
        Loghy::shouldReceive('deleteUserInfo')
            ->once()
            ->with('111')
            ->andReturn(true);
        Loghy::shouldReceive('history')->once()->andReturn([]);

        $request_data = [ 'code' => 'xxxxxxxxxxxxxxxxxxxx' ];
        $response = $this->call('GET', route('auth.loghy.callback.login'), $request_data);

        $response
            ->assertRedirect(route('register'))
            ->assertSessionHas('error', 'User not found with specified UserID(site_id) and LoghyID.');
    }

    public function testHandleRegisterCallbackCreateUserAndRedirectToHomeWhenNotLoggedIn()
    {
        Loghy::shouldReceive('appendCallbackHistory')->once();
        Loghy::shouldReceive('getLoghyId')
            ->once()
            ->with('xxxxxxxxxxxxxxxxxxxx')
            ->andReturn([
                'loghyId' => '111',
                'userId' => null,
                'socialLogin' => 'google',
            ]);
        Loghy::shouldReceive('getUserInfo')
            ->once()
            ->with('111')
            ->andReturn([
                'sid' => '11111111111111111111',
                'name' => $this->faker()->name(),
                'email' => $this->faker()->email(),
            ]);
        Loghy::shouldReceive('deleteUserInfo')
            ->once()
            ->with('111')
            ->andReturn(true);
        Loghy::shouldReceive('putUserId')->once()->andReturn(true);
        Loghy::shouldReceive('history')->once()->andReturn([]);

        $request_data = [ 'code' => 'xxxxxxxxxxxxxxxxxxxx' ];
        $response = $this->call('GET', route('auth.loghy.callback.register'), $request_data);

        $response
            ->assertRedirect(route('home'))
            ->assertSessionHas('success', 'Registered 🎉');
        $this
            ->assertDatabaseCount('users', 1)
            ->assertDatabaseCount('social_identities', 1);
    }

    public function testHandleRegisterCallbackCanCreateUserWithoutEmail()
    {
        Loghy::shouldReceive('appendCallbackHistory')->once();
        Loghy::shouldReceive('getLoghyId')
            ->once()
            ->with('xxxxxxxxxxxxxxxxxxxx')
            ->andReturn([
                'loghyId' => '111',
                'userId' => null,
                'socialLogin' => 'google',
            ]);
        Loghy::shouldReceive('getUserInfo')
            ->once()
            ->with('111')
            ->andReturn([
                'sid' => '11111111111111111111',
                'name' => null,
                'email' => null,
            ]);
        Loghy::shouldReceive('deleteUserInfo')
            ->once()
            ->with('111')
            ->andReturn(true);
        Loghy::shouldReceive('putUserId')->once()->andReturn(true);
        Loghy::shouldReceive('history')->once()->andReturn([]);

        $request_data = [ 'code' => 'xxxxxxxxxxxxxxxxxxxx' ];
        $response = $this->call('GET', route('auth.loghy.callback.register'), $request_data);

        $response
            ->assertRedirect(route('home'))
            ->assertSessionHas('success', 'Registered 🎉');
        $this
            ->assertDatabaseCount('users', 1)
            ->assertDatabaseCount('social_identities', 1);
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
        $user = User::factory()->hasSocialIdentities(1, ['loghy_id' => '111'])->create();

        $ids = [
            'loghyId' => '222',
            'userId' => null,
            'socialLogin' => 'google',
        ];
        $userInfo = [
            'sid' => '11111111111111111111',
            'name' => $this->faker()->name(),
            'email' => $this->faker()->email(),
        ];
        Loghy::shouldReceive('appendCallbackHistory')->once();
        Loghy::shouldReceive('getLoghyId')->once()->with('xxxxxxxxxxxxxxxxxxxx')->andReturn($ids);
        Loghy::shouldReceive('putUserId')->once()->andReturn(true);
        Loghy::shouldReceive('getUserInfo')->once()->with('222')->andReturn($userInfo);
        Loghy::shouldReceive('deleteUserInfo')->once()->with('222')->andReturn(true);
        Loghy::shouldReceive('history')->once()->andReturn([]);

        $request_data = [ 'code' => 'xxxxxxxxxxxxxxxxxxxx' ];
        $response = $this->actingAs($user)
            ->call('GET', route('auth.loghy.callback.register'), $request_data);

        $response
            ->assertRedirect(route('home'))
            ->assertSessionHas('success', 'Connected 🎉');

        $this->assertDatabaseHas('social_identities', [
            'user_id' => $user->id, 'loghy_id' => '222', 'type' => 'google', 'sub' => '11111111111111111111',
        ]);
    }
}
