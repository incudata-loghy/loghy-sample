<?php

use App\Models\SocialIdentity;
use App\Models\User;
use Loghy\SDK\User as LoghyUser;

use function PHPUnit\Framework\assertNull;
use function PHPUnit\Framework\assertSame;

afterEach(function () {
    User::query()->delete();
});

test('findByLoghyUser() return User instance', function () {
    $user = User::factory()->create();
    $identify = SocialIdentity::factory()->for($user)->create();

    $loghyUser = (new LoghyUser())->map([
        'id' => $identify->sub,
        'type' => $identify->type,
        'loghyId' => $identify->loghy_id,
        'userId' => (string)($user->id),
        'name' => null,
        'email' => null,
    ]);

    $foundUser = User::findByLoghyUser($loghyUser);
    assertSame($user->id, $foundUser->id);
});

test('findByLoghyUser() return User instance when loghyUser has no user ID', function () {
    $user = User::factory()->create();
    $identify = SocialIdentity::factory()->for($user)->create();

    $loghyUser = (new LoghyUser())->map([
        'id' => $identify->sub,
        'type' => $identify->type,
        'loghyId' => $identify->loghy_id,
        'userId' => null,
        'name' => null,
        'email' => null,
    ]);

    $foundUser = User::findByLoghyUser($loghyUser);
    assertSame($user->id, $foundUser->id);
});

test('findByLoghyUser() return null when SocialIdentity is not created', function () {
    $user = User::factory()->create();

    $loghyUser = (new LoghyUser())->map([
        'id' => '__sub__',
        'type' => '__type__',
        'loghyId' => '__loghy_id__',
        'userId' => (string)($user->id),
        'name' => null,
        'email' => null,
    ]);

    $foundUser = User::findByLoghyUser($loghyUser);
    assertNull($foundUser);
});
