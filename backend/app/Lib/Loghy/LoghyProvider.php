<?php

namespace App\Lib\Loghy;

use App\Lib\Loghy\Contracts\Provider as ProviderContract;
use Exception;
use Illuminate\Support\Facades\Http;

class LoghyProvider implements ProviderContract
{
    /**
     * Loghy API Key
     */
    protected ?string $apiKey;

    /**
     * Loghy site code
     */
    protected ?string $siteCode;

    /**
     * Request data to Loghy
     */
    protected ?array $requestData;

    /**
     * Response data from Loghy
     */
    protected ?array $responseData;

    /** @var null|array */
    /**
     * Communication history with Loghy
     */
    protected ?array $history;

    /**
     * Loghy Constructor.
     *
     * @param array $configuration
     */
    public function __construct(
        $configuration
    ) {
        $this->apiKey = $configuration['apiKey'] ?? $configuration['api_key'] ?? null;
        $this->siteCode = $configuration['siteCode'] ?? $configuration['site_code'] ?? null;

        $this->history = [];
    }

    /**
     * Get user ID by Loghy ID.
     * LoghyID サイト別ユーザID変換.
     *
     * @param string $loghyId
     * @return null|string
     */
    public function getUserId(string $loghyId): ?string
    {
        $command = 'lgid2siteid';

        $response = $this->request($command, $loghyId);

        return $response->json('data.site_id');
    }

    /**
     * Get user information by Loghy ID.
     * LoghyID 個人情報取得
     *
     * @param string $loghyId
     * @return null|array
     */
    public function getUserInfo(string $loghyId): ?array
    {
        $command = 'lgid2get';

        $response = $this->request($command, $loghyId);

        return $response->json('data.personal_data');
    }

    /**
     * Delete user information by Loghy ID.
     * LoghyID 指定個人情報削除
     *
     * @param string $loghyId
     * @return bool
     */
    public function putUserId(string $loghyId, string $userId): bool
    {
        $command = 'lgid2set';

        $response = $this->request($command, $loghyId, $userId);

        return $response->json('result', false);
    }

    /**
     * Delete user information by Loghy ID.
     * LoghyID 指定個人情報削除
     *
     * @param string $loghyId
     * @return bool
     */
    public function deleteUserInfo(string $loghyId): bool
    {
        $command = 'lgid2pdel';

        $response = $this->request($command, $loghyId);

        return $response->json('result', false);
    }

    /**
     * Delete Loghy ID
     * LoghyID 指定データ削除
     *
     * @param string $loghyId
     * @return bool
     */
    public function deleteLoghyId(string $loghyId): bool
    {
        $command = 'lgid2del';

        $response = $this->request($command, $loghyId);

        return $response->json('result', false);
    }

    /**
     * Merge users by Loghy ID.
     * LoghyID 指定データマージ
     *
     * @param string $targetLoghyId
     * @param string $sourceLoghyId
     * @return bool
     */
    public function mergeUser(string $targetLoghyId, string $sourceLoghyId): bool
    {
        $command = 'lgid2merge';

        $response = $this->request($command, $targetLoghyId, $sourceLoghyId);

        return $response->json('result', false);
    }

    /**
     * Get request data to Loghy
     *
     * @return null|array
     */
    public function requestData(): ?array
    {
        return $this->requestData;
    }

    /**
     * Get response data from Loghy
     *
     * @return null|array
     */
    public function responseData(): ?array
    {
        return $this->responseData;
    }

    /**
     * Request to Loghy
     *
     * @param string $command
     * @param string $id
     * @param null|string $mid
     * @return \Illuminate\Http\Client\Response
     */
    private function request(string $command, string $id, ?string $mid = null)
    {
        $atype = 'site';
        $time = now()->getTimestamp();

        $joined = $command . $atype . $this->siteCode . $id . $mid . $time . $this->apiKey;
        $skey = hash('sha256', $joined);

        $this->requestData = [
            'cmd' => $command,
            'atype' => $atype,
            'sid' => $this->siteCode,
            'id' => $id,
            'mid' => $mid,
            'time' => $time,
            'skey' => $skey,
        ];

        $url = $this->getUrl($command);
        $response = Http::get($url, $this->requestData);

        $this->responseData = $response->json();
        $this->appendHistory($command);

        if (!$response->ok()) {
            throw new Exception('Response status code is not ok from Loghy.', $response->status());
        }
        if ($response->json('result') !== true) {
            throw new Exception('Response result is not true. ', $response->json('error_code'));
        }

        return $response;
    }

    /**
     * Get url to request Loghy
     *
     * @return string
     */
    private function getUrl($command): string
    {
        return 'https://api001.sns-loghy.jp/api/' . $command;
    }

    /**
     * Get communication history with Loghy
     *
     * @return array
     */
    public function history(): array
    {
        return $this->history;
    }

    /**
     * Append callback data to history.
     *
     * @param string $type
     * @param array $input
     * @return void
     */
    public function appendCallbackHistory(string $type, array $input): void
    {
        $this->history[] = [
            'type' => $type,
            'request_data' => null,
            'response_data' => $input,
        ];
    }

    /**
     * Append request & response data to history
     *
     * @param string $command
     * @return void
     */
    private function appendHistory($command)
    {
        $this->history[] = [
            'type' => $this->convertCmdToType($command),
            'request_data' => $this->requestData(),
            'response_data' => $this->responseData(),
        ];
    }

    /**
     * Convert command to type that describe communication with Loghy.
     *
     * @param string $command
     * @return string
     */
    private function convertCmdToType($command)
    {
        $types = [
            'lgid2siteid' => 'Get user ID by Loghy ID',
            'lgid2get' => 'Get user information by Loghy ID',
            'lgid2pdel' => 'Delete user information by Loghy ID',
            'lgid2set' => 'Put user ID by Loghy ID',
            'lgid2del' => 'Delete Loghy ID',
            'lgid2merge' => 'Merge user by Loghy ID',
        ];

        return $types[$command] ?? '';
    }
}
