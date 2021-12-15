<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\LoghyCallbackHandleException;
use App\Http\Controllers\Controller;
use App\Lib\Loghy\Facades\Loghy;
use App\Models\LoghyHistory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LoghyController extends Controller
{
    private ?string $loghyId, $userId;

    /**
     * Handle callback from Loghy without site_id on successful SNS login.
     *
     * @param Request $request
     * @return mixed
     */
    public function handleLoginCallback(Request $request)
    {
        try {
            Loghy::appendCallbackHistory('login_callback', $request->input());

            // Set $loghyId to call deleteUserInfo
            $loghyId = $this->getLoghyId($request); 
            if (Auth::check()) {
                // There is no case that LoghyID,UserID in request and LoghyID,UserID user logged in is same
                return $this->successRedirect(Auth::user(), 'Already connected 👍');
            }

            $userId = $this->getUserId($request);
            $user = $this->findUser($loghyId, $userId);
            return $this->successRedirect($user, 'Logged in 🎉');
        } catch (LoghyCallbackHandleException $e) {
            return $this->failRedirect($e->getMessage());
        } finally {
            if (isset($loghyId)) {
                $this->deleteUserInfo($loghyId);
            }
            $this->saveLoghyHistory();
        }
    }

    /**
     * Handle callback from Loghy with site_id on successful SNS login.
     *
     * @param Request $request
     * @return mixed
     */
    public function handleRegisterCallback(Request $request)
    {
        try {
            Loghy::appendCallbackHistory('register_callback', $request->input());

            $loghyId = $this->getLoghyId($request);

            if (Auth::check()) {
                $user = $this->connectUser($loghyId);
                return $this->successRedirect($user, 'Connected 🎉');
            }
            $user = $this->registerUser($loghyId);
            return $this->successRedirect($user, 'Registered 🎉');
        } catch (LoghyCallbackHandleException $e) {
            return $this->failRedirect($e->getMessage());
        } finally {
            if (isset($loghyId)) {
                $this->deleteUserInfo($loghyId);
            }
            $this->saveLoghyHistory();
        }
    }

    /**
     * Handle callback from Loghy on failed SNS login.
     *
     * @param Request $request
     * @return mixed
     */
    public function handleErrorCallback(Request $request)
    {
        $this->failRedirect('Social Login failed.');
    }

    /**
     * Get LoghyID from request.
     *
     * @param Request $request
     * @return string $loghyId
     * @throws LoghyCallbackHandleException
     */
    private function getLoghyId(Request $request): string
    {
        return $this->loghyId
            ?? $this->getIdsByCode($request)['loghyId']
            ?? throw new LoghyCallbackHandleException('Failed to get LoghyID by authentication code.');
    }

    /**
     * Get UserID (site_id) from request.
     *
     * @param Request $request
     * @return string $loghyId
     * @throws LoghyCallbackHandleException
     */
    private function getUserId(Request $request): string
    {
        return $this->userId
            ?? $this->getIdsByCode($request)['userId']
            ?? throw new LoghyCallbackHandleException('Failed to get UserID(site_id) by authentication code.');
    }

    /**
     * Get LoghyID and UserID from authentication code in request.
     * 
     * @param Request $request
     * @return array ['loghyId' => $loghyId, 'userId' => $userId]
     * @throws LoghyCallbackHandleException
     */
    private function getIdsByCode(Request $request): array
    {
        $code = $request->input('code') 
            ?? throw new LoghyCallbackHandleException('Authentication code is not found in callback data.');

        try {
            $ids = Loghy::getLoghyId($code);

            $this->loghyId = $ids['loghyId'] ?? null;
            $this->userId = $ids['userId'] ?? null;

            return $ids;

        } catch (\Exception $e) {
            throw new LoghyCallbackHandleException('Failed to get LoghyID by authentication code.', 0, $e);
        }
    }

    /**
     * Connect LoghyID.
     *
     * @param string $loghyId
     * @return User
     * @throws LoghyCallbackHandleException
     */
    private function connectUser($loghyId): User
    {
        /** @var User $user */
        $user = Auth::user();

        Loghy::mergeUser($user->loghy_id, $loghyId);

        return $user;
    }

    /**
     * Register user.
     *
     * @param string $loghyId
     * @return User
     * @throws LoghyCallbackHandleException
     */
    private function registerUser($loghyId): User
    {
        $userInfo = Loghy::getUserInfo($loghyId);
        if (!$userInfo) {
            throw new LoghyCallbackHandleException('Failed to get personal data.');
        }

        $user = $this->createUser($userInfo, $loghyId);
        if (!$user) {
            throw new LoghyCallbackHandleException('Failed to register user.');
        }

        Loghy::putUserId($loghyId, $user->id);

        return $user;
    }

    /**
     * Find user.
     *
     * @param string $loghyId
     * @return User
     * @throws LoghyCallbackHandleException
     */
    private function findUser(string $loghyId, string $userId): User
    {
        $user = User::where([
            'id' => $userId, 'loghy_id' => $loghyId,
        ])->first();

        if (!$user) {
            throw new LoghyCallbackHandleException('User not found with specified UserID(site_id) and LoghyID.');
        }

        return $user;
    }

    /**
     * Create user.
     *
     * @param array $userInfo
     * @param string $loghyId
     * @return User
     */
    private function createUser(array $userInfo, string $loghyId): User
    {
        $name = $userInfo['name'] ?? null;
        $email = $userInfo['email'] ?? null;

        if (!$email) {
            return null;
        }

        return User::firstOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => md5(Str::uuid()), 'loghy_id' => $loghyId]
        );
    }

    /**
     * Delete user information in Loghy
     *
     * @param string $loghyId
     * @return bool
     */
    private function deleteUserInfo(string $loghyId): bool
    {
        try {
            return Loghy::deleteUserInfo($loghyId);
        } catch (\Exception $e) {
            Log::error("Failed to delete user information in Loghy. Its LoghyID is {$loghyId}");
            return false;
        }
    }

    /**
     * Save Loghy history.
     *
     * @return void
     */
    private function saveLoghyHistory()
    {
        $history = array_map(
            function ($h) {
                return [
                    'type' => $h['type'],
                    'request_data' => $h['request_data'] === null
                        ? null
                        : json_encode($h['request_data']),
                    'response_data' => $h['response_data'] === null
                        ? null
                        : json_encode($h['response_data']),
                ];
            },
            Loghy::history()
        );

        if ($user = Auth::user()) {
            /** @var User $user */
            $user->loghyHistory()->createMany($history);
        } else {
            foreach ($history as $h) {
                LoghyHistory::create($h);
            }
        }
    }

    /**
     * Redirect home with login and success message.
     *
     * @param User $user
     * @param string $message
     * @return mixed
     */
    private function successRedirect(User $user, string $message)
    {
        if (!Auth::check()) {
            Auth::login($user);
        }
        return redirect()->route('home')->with('success', $message);
    }

    /**
     * Redirect with error message.
     *
     * @param string $message
     * @return mixed
     */
    private function failRedirect(string $message)
    {
        $route = Auth::check() ? 'home' : 'register';
        return redirect()->route($route)->with('error', $message);
    }
}
