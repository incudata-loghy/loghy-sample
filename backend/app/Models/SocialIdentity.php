<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SocialIdentity extends Model
{
    use HasFactory;

    public const TYPES = [
        'line' => 'LINE',
        'google' => 'Google',
        'facebook' => 'Facebook',
        // 'twitter' => 'Twitter',
        'apple' => 'Apple',
        // 'yahoo' => 'Yahoo! JAPAN',
    ];

    /**
     * The attributes that aren't mass assignable.
     *
     * @var string[]
     */
    protected $guarded = [];

    /**
     * Get the user that owns the Loghy history.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function status($user): array
    {
        $result = [];
        foreach (self::TYPES as $key => $name) {
            $identity = $user->socialIdentities()->where('type', $key)->first();

            $result[] = (object)[
                'type' => $key,
                'name' => $name,
                'isLinked' => !is_null($identity),
                'id' => is_null($identity) ? null : $identity->id,
                'loginUrl' => "https://api001.sns-loghy.jp/login/type/{$key}/loghySample"
                    . '?beforeURL=' . urlencode(route('auth.loghy.callback.login'))
                    . '&registURL=' . urlencode(route('auth.loghy.callback.register'))
                    . '&errorURL=' . urlencode(route('auth.loghy.callback.error')),
            ];
        }
        return $result;
    }
}
