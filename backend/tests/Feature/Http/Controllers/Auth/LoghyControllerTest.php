<?php

namespace Tests\Feature\Http\Controllers\Auth;

use App\Facades\Loghy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class LoghyControllerTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    public function testHandleLoginCallbackLogInAndRedirectToHomeWhenNotLoggedIn()
    {
        $user = User::factory()->hasSocialIdentities(1, ['loghy_id' => '111'])->create();

        Loghy::shouldReceive('getLoghyId')->once()->with('xxxxxxxxxxxxxxxxxxxx')->andReturn([
            'result' => true,
            'data' => [
                'lgid' => '111',
                'site_id' => $user->id,
            ]
        ]);
        Loghy::shouldReceive('deleteUserInfo')->once()->with('111')->andReturn(['result' => true]);

        $request_data = [ 'code' => 'xxxxxxxxxxxxxxxxxxxx' ];
        $response = $this->call('GET', route('auth.loghy.callback.login'), $request_data);

        $response
            ->assertRedirect(route('home'))
            ->assertSessionHas('success', 'Logged in 🎉');
    }

    public function testHandleLoginCallbackRedirectToHomeWhenLoggedIn()
    {
        $user = User::factory()->hasSocialIdentities(1, ['loghy_id' => '111'])->create();

        $ids = [
            'loghyId' => '111',
            'userId' => $user->id,
        ];
        Loghy::shouldReceive('getLoghyId')->once()->with('xxxxxxxxxxxxxxxxxxxx')->andReturn([
            'result' => true,
            'data' => [
                'lgid' => '111',
                'site_id' => $user->id,
            ]
        ]);
        Loghy::shouldReceive('deleteUserInfo')->once()->with('111')->andReturn(['result' => true]);

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
        Loghy::shouldReceive('getLoghyId')->once()->with('xxxxxxxxxxxxxxxxxxxx')
            ->andThrows(new \Exception('@@@'));

        $request_data = ['code' => 'xxxxxxxxxxxxxxxxxxxx'];
        $response = $this->call('GET', route('auth.loghy.callback.login'), $request_data);

        $response
            ->assertRedirect(route('register'))
            ->assertSessionHas('error', 'Failed to get LoghyID by authentication code. Error message: @@@');
    }

    public function testHandleLoginCallbackRedirectToRegisterWhenNoLoghyId()
    {
        Loghy::shouldReceive('getLoghyId')->once()->with('xxxxxxxxxxxxxxxxxxxx')->andReturn([
            'result' => true,
            'data' => [
                'lgid' => null,
                'site_id' => '1',
            ]
        ]);

        $request_data = ['code' => 'xxxxxxxxxxxxxxxxxxxx'];
        $response = $this->call('GET', route('auth.loghy.callback.login'), $request_data);

        $response
            ->assertRedirect(route('register'))
            ->assertSessionHas('error', 'Failed to get LoghyID by authentication code.');
    }

    public function testHandleLoginCallbackRedirectToRegisterWhenNoSiteId()
    {
        Loghy::shouldReceive('getLoghyId')->twice()->with('xxxxxxxxxxxxxxxxxxxx')->andReturn([
            'result' => true,
            'data' => [
                'lgid' => '111',
                'site_id' => null,
            ]
        ]);

        $request_data = ['code' => 'xxxxxxxxxxxxxxxxxxxx'];
        $response = $this->call('GET', route('auth.loghy.callback.login'), $request_data);

        $response
            ->assertRedirect(route('register'))
            ->assertSessionHas('error', 'Failed to get UserID(site_id) by authentication code.');
    }

    public function testHandleLoginCallbackRedirectToRegisterWhenInvalidLoghyId()
    {
        $user = User::factory()->hasSocialIdentities(1, ['loghy_id' => '111'])->create();

        Loghy::shouldReceive('getLoghyId')->once()->with('xxxxxxxxxxxxxxxxxxxx')->andReturn([
            'result' => true,
            'data' => [
                'lgid' => '222',
                'site_id' => $user->id,
            ]
        ]);
        Loghy::shouldReceive('deleteUserInfo')->once()->with('222')->andReturn(['result' => false]);

        $request_data = [ 'code' => 'xxxxxxxxxxxxxxxxxxxx' ];
        $response = $this->call('GET', route('auth.loghy.callback.login'), $request_data);

        $response
            ->assertRedirect(route('register'))
            ->assertSessionHas('error', 'User not found with specified UserID(site_id) and LoghyID.');
    }

    public function testHandleLoginCallbackRedirectToRegisterWhenInvalidSiteId()
    {
        $user = User::factory()->hasSocialIdentities(1, ['loghy_id' => '111'])->create();

        Loghy::shouldReceive('getLoghyId')->once()->with('xxxxxxxxxxxxxxxxxxxx')->andReturn([
            'result' => true,
            'data' => [
                'lgid' => '111',
                'site_id' => $user->id + 1,
            ]
        ]);
        Loghy::shouldReceive('deleteUserInfo')->once()->with('111')->andReturn(['result' => true]);

        $request_data = [ 'code' => 'xxxxxxxxxxxxxxxxxxxx' ];
        $response = $this->call('GET', route('auth.loghy.callback.login'), $request_data);

        $response
            ->assertRedirect(route('register'))
            ->assertSessionHas('error', 'User not found with specified UserID(site_id) and LoghyID.');
    }

    public function testHandleRegisterCallbackCreateUserAndRedirectToHomeWhenNotLoggedIn()
    {
        Loghy::shouldReceive('getLoghyId')->once()->with('xxxxxxxxxxxxxxxxxxxx')->andReturn([
            'result' => true,
            'data' => [
                'lgid' => '111',
                'site_id' => null,
                'social_login' => 'google',
            ]
        ]);
        Loghy::shouldReceive('getUserInfo')->once()->with('111')->andReturn([
            'result' => true,
            'data' => [
                'personal_data' => [
                    'sid' => '11111111111111111111',
                    'name' => $this->faker()->name(),
                    'email' => $this->faker()->email(),
                ]
            ]
        ]);
        Loghy::shouldReceive('deleteUserInfo')->once()->with('111')->andReturn(['result' => true]);
        Loghy::shouldReceive('putUserId')->once()->andReturn(['result' => true]);

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
        Loghy::shouldReceive('getLoghyId')->once()->with('xxxxxxxxxxxxxxxxxxxx')->andReturn([
            'result' => true,
            'data' => [
                'lgid' => '111',
                'site_id' => null,
                'social_login' => 'google',
            ]
        ]);
        Loghy::shouldReceive('getUserInfo')->once()->with('111')->andReturn([
            'result' => true,
            'data' => [
                'personal_data' => [
                    'sid' => '11111111111111111111',
                    'name' => null,
                    'email' => null,
                ]
            ]
        ]);
        Loghy::shouldReceive('deleteUserInfo')->once()->with('111')->andReturn(['result' => true]);
        Loghy::shouldReceive('putUserId')->once()->andReturn(['result' => true]);

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
        Loghy::shouldReceive('getLoghyId')->once()->with('xxxxxxxxxxxxxxxxxxxx')->andReturn([
            'result' => true,
            'data' => [
                'lgid' => null,
                'site_id' => null,
            ]
        ]);

        $request_data = [ 'code' => 'xxxxxxxxxxxxxxxxxxxx' ];
        $response = $this->call('GET', route('auth.loghy.callback.register'), $request_data);

        $response
            ->assertRedirect(route('register'))
            ->assertSessionHas('error', 'Failed to get LoghyID by authentication code.');
    }

    public function testHandleRegisterCallbackConnectUserAndRedirectToHomeWhenLoggedIn()
    {
        $user = User::factory()->hasSocialIdentities(1, ['loghy_id' => '111'])->create();

        Loghy::shouldReceive('getLoghyId')->once()->with('xxxxxxxxxxxxxxxxxxxx')->andReturn([
            'result' => true,
            'data' => [
                'lgid' => '222',
                'site_id' => null,
                'social_login' => 'google',
            ]
        ]);
        Loghy::shouldReceive('getUserInfo')->once()->with('222')->andReturn([
            'result' => true,
            'data' => [
                'personal_data' => [
                    'sid' => '11111111111111111111',
                    'name' => $this->faker()->name(),
                    'email' => $this->faker()->email(),
                ]
            ]
        ]);
        Loghy::shouldReceive('putUserId')->once()->andReturn(['result' => true]);
        Loghy::shouldReceive('deleteUserInfo')->once()->with('222')->andReturn(['result' => true]);

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
