<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static ?array getUserId(string $loghyId)
 * @method static ?array getUserInfo(string $loghyId)
 * @method static ?array putUserId(string $loghyId, string $userId)
 * @method static ?array deleteUserId(string $loghyId)
 * @method static ?array deleteUserInfo(string $loghyId)
 * @method static ?array deleteLoghyId(string $loghyId)
 * @method static void setHttpClient(\GuzzleHttp\Client $client)
 * @method static GuzzleHttp\Client httpClient()
 */
class Loghy extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Loghy\SDK\Loghy::class;
    }
}
