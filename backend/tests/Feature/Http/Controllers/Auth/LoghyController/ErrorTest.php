<?php

use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\call;

beforeEach(function () {
    $this->uri = route('auth.loghy.callback.error');
});

afterEach(function () {
    User::query()->delete();
});

it('redirect to login page when not authenticated', function () {
    call('GET', $this->uri)
        ->assertRedirect(route('login'))
        ->assertSessionHas('error', 'Social Login failed.');
});

it('redirect to home page when authenticated', function () {
    /** @var \App\Models\User $user */
    $user = User::factory()->create();

    actingAs($user)->call('GET', $this->uri)
        ->assertRedirect(route('home'))
        ->assertSessionHas('error', 'Social Login failed.');
});
