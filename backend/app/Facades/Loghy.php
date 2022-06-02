<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Loghy\SDK\Contract\LoghyInterface setHttpClient(\GuzzleHttp\Client $client)
 * @method static \GuzzleHttp\Client httpClient()
 * @method static \Loghy\SDK\Contract\LoghyInterface setCode(string $code)
 * @method static ?string getCode()
 * @method static \Loghy\SDK\Contract\ContractUser user()
 * @method static bool putUserId(string $userId, string $loghyId = null)
 * @method static bool deleteUser(string $loghyId)
 */
class Loghy extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Loghy\SDK\Loghy::class;
    }
}
