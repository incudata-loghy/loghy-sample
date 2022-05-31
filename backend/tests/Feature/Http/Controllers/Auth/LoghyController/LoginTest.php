<?php

use App\Facades\Loghy;
use App\Models\SocialIdentity;
use App\Models\User;
use Tests\Feature\Http\Controllers\Auth\LoghyController\LoghyMockable;

use function Pest\Laravel\assertAuthenticatedAs;
use function Pest\Laravel\call;

uses(LoghyMockable::class);

beforeEach(function () {
    $this->uri = route('auth.loghy.callback.login');
});

afterEach(function () {
    User::query()->delete();
});

it('authenticate', function () {
    /** @var \App\Models\User $user */
    $user = User::factory()->create();
    $identity = SocialIdentity::factory()->for($user)->create();

    $loghyUser = $this->mockLoghy([
        'id' => $identity->sub,
        'type' => $identity->type,
        'loghyId' => $identity->loghy_id,
        'userId' => (string)$user->id,
    ]);

    call('GET', $this->uri, ['code' => 'xxx'])
        ->assertRedirect(route('home'))
        ->assertSessionHas('success', 'Logged in 🎉');

    assertAuthenticatedAs($user);
});

it('redirect to login page when request has no code', function () {
    call('GET', $this->uri)
        ->assertRedirect(route('login'))
        ->assertSessionHas('error', 'Authentication code is not found in callback data.');
});

it('redirect to login page when invalid user is requested', function () {
    /** @var \App\Models\User $user */
    $user = User::factory()->create();
    $identity = SocialIdentity::factory()->for($user)->create();

    $loghyUser = $this->mockLoghy([
        'id' => $identity->sub,
        'type' => $identity->type,
        'loghyId' => $identity->loghy_id,
        'userId' => 'wrong-user_id',
    ]);

    call('GET', $this->uri, ['code' => 'xxx'])
        ->assertRedirect(route('login'))
        ->assertSessionHas('error', 'User not found.');
});

it('redirect to login page when throw exception', function () {
    Loghy::shouldReceive('setCode')->andThrows(\Exception::class);

    call('GET', $this->uri, ['code' => 'xxx'])
        ->assertRedirect(route('login'))
        ->assertSessionHas('error', 'Something went wrong...');
});
