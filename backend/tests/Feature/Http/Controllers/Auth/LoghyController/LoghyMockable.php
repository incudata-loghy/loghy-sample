<?php

namespace Tests\Feature\Http\Controllers\Auth\LoghyController;

use App\Facades\Loghy;
use App\Models\SocialIdentity;
use App\Models\User;
use Loghy\SDK\User as LoghyUser;

trait LoghyMockable
{
    public function mockLoghy(
        array $attributes = [],
        SocialIdentity $identity = null,
        User $user = null
    ): LoghyUser {
        $raw = [
            'userId' => is_null($user) ? null : (string)($user->id),
            'name' => '__name__',
            'email' => 'email@example.com'
        ];
        $loghyUser = (new LoghyUser())->map($attributes + $raw + [
            'id'      => $identity?->sub  ?? '__sub__',
            'type'    => $identity?->type ?? '__type__',
            'loghyId' => $identity?->loghy_id ?? '__loghy_id__',
        ])->setRaw($raw);

        Loghy::shouldReceive('setCode');
        Loghy::shouldReceive('user')->andReturn($loghyUser);
        Loghy::shouldReceive('putUserId');

        return $loghyUser;
    }
}
