<?php

use App\Facades\Loghy;
use App\Models\SocialIdentity;
use App\Models\User;
use Tests\Feature\Http\Controllers\Auth\LoghyController\LoghyMockable;

use function Pest\Laravel\assertAuthenticated;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\call;

uses(LoghyMockable::class);

beforeEach(function () {
    $this->uri = route('auth.loghy.callback.register');
});

afterEach(function () {
    User::query()->delete();
});

it('create user and authenticate', function () {
    $loghyUser = $this->mockLoghy();

    call('GET', $this->uri, ['code' => 'xxx'])
        ->assertRedirect(route('home'))
        ->assertSessionHas('success', 'Registered 🎉');

    assertAuthenticated();
    assertDatabaseHas('users', [
        'email' => $loghyUser->getEmail(),
        'name' => $loghyUser->getName(),
    ]);
    assertDatabaseHas('social_identities', [
        'loghy_id' => $loghyUser->getLoghyId(),
        'type' => $loghyUser->getType(),
        'sub' => $loghyUser->getId(),
    ]);
});

it('redirect to register page when request has no code', function () {
    call('GET', $this->uri)
        ->assertRedirect(route('register'))
        ->assertSessionHas('error', 'Authentication code is not found in callback data.');
});

it('authenticate when already registered', function () {
    $loghyUser = $this->mockLoghy();
    $user = User::factory()->create(['email' => $loghyUser->getEmail(), 'name' => $loghyUser->getName()]);
    SocialIdentity::factory()->for($user)->create(['loghy_id' => $loghyUser->getLoghyId(), 'type' => $loghyUser->getType(), 'sub' => $loghyUser->getId()]);

    call('GET', $this->uri, ['code' => 'xxx'])
        ->assertRedirect(route('home'))
        ->assertSessionHas('success', 'Already registered. Logged in 👍');
});

it('redirect to register page when throw exception', function () {
    Loghy::shouldReceive('setCode')->andThrows(\Exception::class);

    call('GET', $this->uri, ['code' => 'xxx'])
        ->assertRedirect(route('register'))
        ->assertSessionHas('error', 'Something went wrong...');
});
