<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Loghy\SDK\User as LoghyUser;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'loghy_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Get the Social Identity for the user.
     */
    public function socialIdentities()
    {
        return $this->hasMany(SocialIdentity::class);
    }

    /**
     * @param string $loghyId
     * @return bool
     */
    public function hasLoghyId(string $loghyId): bool
    {
        return $this->socialIdentities()->where('loghy_id', $loghyId)->exists();
    }

    /**
     * Find user from loghy user.
     *
     * @param \Loghy\SDK\User $loghyUser
     * @return \App\Models\User|null
     */
    public static function findByLoghyUser(LoghyUser $loghyUser): ?self
    {
        return self::when($loghyUser->getUserId(), function ($query, $userId) {
            return $query->where('id', $userId);
        })->whereRelation('socialIdentities', 'loghy_id', $loghyUser->getLoghyId())
            ->whereRelation('socialIdentities', 'type', $loghyUser->getType())
            ->whereRelation('socialIdentities', 'sub', $loghyUser->getId())
            ->first();
    }

    /**
     * Create user from loghy user.
     *
     * @param \Loghy\SDK\User $loghyUser
     * @return \App\Models\User|null
     */
    public static function createByLoghyUser(LoghyUser $loghyUser): ?self
    {
        if ($user = self::findByLoghyUser($loghyUser)) {
            return $user;
        }

        $user = self::create([
            'email' => $loghyUser->getEmail(),
            'name' => $loghyUser->getName(),
            'password' => md5(Str::uuid()),
        ]);
        $user->createSocialIdentityByLoghyUser($loghyUser);

        return $user;
    }

    public function findSocialIdentityByLogyUser(LoghyUser $loghyUser): ?SocialIdentity
    {
        return $this->socialIdentities()
            ->where('loghy_id', $loghyUser->getLoghyId())
            ->where('type', $loghyUser->getType())
            ->where('sub', $loghyUser->getId())
            ->first();
    }

    public function createSocialIdentityByLoghyUser(LoghyUser $loghyUser): SocialIdentity
    {
        return $this->socialIdentities()->create([
            'loghy_id' => $loghyUser->getLoghyId(),
            'type' => $loghyUser->getType(),
            'sub' => $loghyUser->getId(),
        ]);
    }
}
