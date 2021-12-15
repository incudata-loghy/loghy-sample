<?php

namespace App\Lib\Loghy\Facades;

use App\Lib\Loghy\LoghyProvider;
use Illuminate\Support\Facades\Facade;

/**
 * @method static ?string getUserId(string $loghyId)
 * @method static ?array getUserInfo(string $loghyId)
 * @method static bool putUserId(string $loghyId, string $userId)
 * @method static bool deleteUserInfo(string $loghyId)
 * @method static bool deleteLoghyId(string $loghyId)
 * @method static bool mergeUser(string $targetLoghyId, string $sourceLoghyId)
 * @method static array getLoghyId(string $code)
 * @method static ?array requestData()
 * @method static ?array responseData()
 * @method static void appendCallbackHistory(string $type, array $input)
 * @method static array history()
 * @see \App\Lib\Loghy\LoghyProvider;
 */
class Loghy extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return LoghyProvider::class;
    }
}
