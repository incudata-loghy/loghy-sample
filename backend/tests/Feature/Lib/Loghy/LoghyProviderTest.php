<?php

namespace Tests\Unit\Lib\Loghy;

use App\Lib\Loghy\LoghyProvider;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LoghyProviderTest extends TestCase
{
    use WithFaker;

    protected LoghyProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->provider = new LoghyProvider([
            'api_key' => 'api_key',
            'site_code' => 'site_code',
        ]);
    }

    public function testGetUserIdReturnSiteId()
    {
        Http::fake(Http::response([
            'result' => true,
            'data' => [
                'site_id' => '11',
            ],
        ]));

        $userId = $this->provider->getUserId('1');

        $this->assertSame('11', $userId);
    }

    public function testGetUserInfoReturnPersonalData()
    {
        $personal_data = [
            'name' => $this->faker()->name(),
            'email' => $this->faker()->email(),
        ];
        Http::fake(Http::response([
            'result' => true,
            'data' => [
                'personal_data' => $personal_data,
            ],
        ]));

        $userInfo = $this->provider->getUserInfo('1');
        $this->assertSame($personal_data, $userInfo);
    }

    public function testPutUserIdReturnTrue()
    {
        Http::fake(Http::response([
            'result' => true,
        ]));

        $result = $this->provider->putUserId('1', '11');
        $this->assertTrue($result);
    }

    public function testDeleteUserInfoReturnTrue()
    {
        Http::fake(Http::response([
            'result' => true,
        ]));

        $result = $this->provider->deleteUserInfo('1', '11');
        $this->assertTrue($result);
    }

    public function testDeleteLoghyIdReturnTrue()
    {
        Http::fake(Http::response([
            'result' => true,
        ]));

        $result = $this->provider->deleteLoghyId('1');
        $this->assertTrue($result);
    }

    public function testMergeUserReturnTrue()
    {
        Http::fake(Http::response([
            'result' => true,
        ]));

        $result = $this->provider->mergeUser('1', '2');
        $this->assertTrue($result);
    }

    public function testGetUserIdThrowsExceptionWhenStatusCodeIsBadRequest()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Response status code is not ok from Loghy.');
        $this->expectExceptionCode(400);

        Http::fake(Http::response([
            'result' => true,
        ], 400));

        $this->provider->getUserId('1');
    }

    public function testGetUserIdThrowsExceptionWhenResultIsFalse()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Response result is not true. ');
        $this->expectExceptionCode(1);

        Http::fake(Http::response([
            'result' => false,
            'error_code' => 1,
        ]));

        $this->provider->getUserId('1');
    }
}
