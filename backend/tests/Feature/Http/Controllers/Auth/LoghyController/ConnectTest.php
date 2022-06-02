<?php

use App\Facades\Loghy;
use App\Models\SocialIdentity;
use App\Models\User;
use Tests\Feature\Http\Controllers\Auth\LoghyController\LoghyMockable;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertAuthenticatedAs;
use function Pest\Laravel\call;

uses(LoghyMockable::class);

beforeEach(function () {
    $this->uri = route('auth.loghy.callback.connect');

    $this->user = User::factory()->create();
});

afterEach(function () {
    User::query()->delete();
});

it('connect another social identity', function () {
    $this->mockLoghy();

    actingAs($this->user)
        ->call('GET', $this->uri, ['code' => 'xxx'])
        ->assertRedirect(route('home'))
        ->assertSessionHas('success', 'Connected 👍');
});

it('has error when request has no code', function () {
    actingAs($this->user)->call('GET', $this->uri)
        ->assertRedirect(route('home'))
        ->assertSessionHas('error', 'Authentication code is not found in callback data.');
});

it('has error when not authenticated', function () {
    $this->mockLoghy();

    call('GET', $this->uri, ['code' => 'xxx'])
        ->assertRedirect(route('login'))
        ->assertSessionHas('error', 'Failed to connect without authenticated.');
});

it('has error when connecting another user', function () {
    $this->mockLoghy(['userId' => 'wrong-user_id']);

    actingAs($this->user)
        ->call('GET', $this->uri, ['code' => 'xxx'])
        ->assertRedirect(route('home'))
        ->assertSessionHas('error', 'Failed for invalid connection.');
});

it('has error when already connected', function () {
    $identity = SocialIdentity::factory()->for($this->user)->create();
    $this->mockLoghy(identity: $identity, user: $this->user);

    actingAs($this->user)
        ->call('GET', $this->uri, ['code' => 'xxx'])
        ->assertRedirect(route('home'))
        ->assertSessionHas('error', 'Already connected ✅');
});

it('has error when throw exception', function () {
    Loghy::shouldReceive('setCode')->andThrows(\Exception::class);

    call('GET', $this->uri, ['code' => 'xxx'])
        ->assertRedirect(route('login'))
        ->assertSessionHas('error', 'Something went wrong...');
});
