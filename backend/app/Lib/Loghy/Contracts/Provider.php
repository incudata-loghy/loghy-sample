<?php

namespace App\Lib\Loghy\Contracts;

interface Provider
{
    /**
     * Get user ID by Loghy ID.
     * LoghyID サイト別ユーザID変換.
     * 
     * @param string $loghyId
     * @return null|string
     */
    public function getUserId(string $loghyId): ?string;

    /**
     * Get user information by Loghy ID.
     * LoghyID 個人情報取得
     * 
     * @param string $loghyId
     * @return null|array
     */
    public function getUserInfo(string $loghyId): ?array;

    /**
     * Put user ID by Loghy ID.
     * LoghyID サイト別ユーザID設定
     * 
     * @param string $loghyId
     * @param string $userId
     * @return bool
     */
    public function putUserId(string $loghyId, string $userId): bool;

    /**
     * Delete user information by Loghy ID.
     * LoghyID 指定個人情報削除
     * 
     * @param string $loghyId
     * @return bool
     */
    public function deleteUserInfo(string $loghyId): bool;

    /**
     * Delete Loghy ID
     * LoghyID 指定データ削除
     * 
     * @param string $loghyId
     * @return bool
     */
    public function deleteLoghyId(string $loghyId): bool;

    /**
     * Merge users by Loghy ID.
     * LoghyID 指定データマージ
     * 
     * @param string $targetLoghyId
     * @param string $sourceLoghyId
     * @return bool
     */
    public function mergeUser(string $targetLoghyId, string $sourceLoghyId): bool;

    /**
     * Get Loghy ID and User ID by authentication code.
     * LoghyID取得
     * 
     * @param string $code
     * @return array
     */
    public function getLoghyId(string $code): array;

    /**
     * Append callback data to history.
     * 
     * @param string $type
     * @param array $input
     * @return void
     */
    public function appendCallbackHistory(string $type, array $input): void;

    /**
     * Get communication history with Loghy
     * 
     * @return array
     */
    public function history(): array;
}