<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\LoghyCallbackHandleException;
use App\Facades\Loghy;
use App\Http\Controllers\Controller;
use App\Models\SocialIdentity;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LoghyController extends Controller
{
    private ?string $loghyId;
    private ?string $userId;
    private ?string $socialLoginType;

    /**
     * Handle callback from Loghy without site_id on successful SNS login.
     *
     * @param Request $request
     * @return mixed
     */
    public function handleLoginCallback(Request $request)
    {
        try {
            $loghyId = $this->getLoghyId($request);
            $userId = $this->getUserId($request);
            $user = $this->findUser($loghyId, $userId);

            if (!Auth::check()) {
                return $this->successRedirect($user, 'Logged in 🎉');
            }
            if ($user->is(Auth::user())) {
                return $this->successRedirect(Auth::user(), 'Already logged in or connected 👍');
            }
            throw new LoghyCallbackHandleException('Invalid user is required.');
        } catch (LoghyCallbackHandleException $e) {
            return $this->failRedirect($e->getMessage());
        } finally {
            if (isset($loghyId)) {
                $this->deleteUserInfo($loghyId);
            }
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
            $loghyId = $this->getLoghyId($request);
            $socialLoginType = $this->getSocialLoginType($request);

            if (Auth::check()) {
                $user = $this->connectUser($loghyId, $socialLoginType);
                return $this->successRedirect($user, 'Connected 🎉');
            }
            $user = $this->registerUser($loghyId, $socialLoginType);
            return $this->successRedirect($user, 'Registered 🎉');
        } catch (LoghyCallbackHandleException $e) {
            return $this->failRedirect($e->getMessage());
        } finally {
            if (isset($loghyId)) {
                $this->deleteUserInfo($loghyId);
            }
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
     * @return string
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
     * @return string
     * @throws LoghyCallbackHandleException
     */
    private function getUserId(Request $request): string
    {
        return $this->userId
            ?? $this->getIdsByCode($request)['userId']
            ?? throw new LoghyCallbackHandleException('Failed to get UserID(site_id) by authentication code.');
    }

    /**
     * Get social login type from request.
     *
     * @param Request $request
     * @return string
     * @throws LoghyCallbackHandleException
     */
    private function getSocialLoginType(Request $request): string
    {
        return $this->socialLoginType
            ?? $this->getIdsByCode($request)['social_login']
            ?? throw new LoghyCallbackHandleException('Failed to get social Login type by authentication code.');
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
            $response = Loghy::getLoghyId($code);
            $data = $this->verifyLoghyResponse($response);

            $this->loghyId = $data['lgid'] ?? null;
            $this->userId = $data['site_id'] ?? null;
            $this->socialLoginType = $data['social_login'] ?? null;

            return [
                'loghyId' => $this->loghyId,
                'userId' => $this->userId,
                'social_login' => $this->socialLoginType,
            ];
        } catch (LoghyCallbackHandleException $e) {
            throw $e;
        } catch (\Exception $e) {
            var_dump($e->getMessage());
            throw new LoghyCallbackHandleException(
                'Failed to get LoghyID by authentication code. Error message: ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Connect LoghyID.
     *
     * @param string $loghyId
     * @return User
     * @throws LoghyCallbackHandleException
     */
    private function connectUser(string $loghyId, ?string $socialLoginType): User
    {
        try {
            $response = Loghy::getUserInfo($loghyId);
            $data = $this->verifyLoghyResponse($response);

            $userInfo = $data['personal_data'] ?? null;
            if (!$userInfo) {
                throw new LoghyCallbackHandleException('Failed to get personal data.');
            }

            /** @var User $user */
            $user = Auth::user();

            $this->createSocialIdentity($user, $loghyId, $socialLoginType, $userInfo);

            $response = Loghy::putUserId($loghyId, $user->id);
            $this->verifyLoghyResponse($response);
            return $user;
        } catch (LoghyCallbackHandleException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new LoghyCallbackHandleException(
                'Failed to connect User. Error message: ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Register user.
     *
     * @param string $loghyId
     * @return User
     * @throws LoghyCallbackHandleException
     */
    private function registerUser(string $loghyId, ?string $socialLoginType): User
    {
        try {
            $response = Loghy::getUserInfo($loghyId);
            $data = $this->verifyLoghyResponse($response);

            $userInfo = $data['personal_data'] ?? null;
            if (!$userInfo) {
                throw new LoghyCallbackHandleException('Failed to get personal data.');
            }

            $user = $this->createUser($userInfo, $loghyId, $socialLoginType);
            if (!$user) {
                throw new LoghyCallbackHandleException('Failed to register user.');
            }

            $this->createSocialIdentity($user, $loghyId, $socialLoginType, $userInfo);

            $response = Loghy::putUserId($loghyId, $user->id);
            $this->verifyLoghyResponse($response);

            return $user;
        } catch (LoghyCallbackHandleException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new LoghyCallbackHandleException(
                'Failed to register user. Error message: ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
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
        $user = User::find($userId);

        if (!$user || !$user->hasLoghyId($loghyId)) {
            throw new LoghyCallbackHandleException('User not found with specified UserID(site_id) and LoghyID.');
        }

        return $user;
    }

    /**
     * Create user.
     *
     * @param array $userInfo
     * @param string $loghyId
     * @return null|User
     */
    private function createUser(array $userInfo): ?User
    {
        $name = $userInfo['name'] ?? null;
        $email = $userInfo['email'] ?? null;

        return User::firstOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => md5(Str::uuid())]
        );
    }

    /**
     * Create social identity
     *
     * @param User $user
     * @param string $loghyId
     * @param string $type
     * @param array $userInfo
     * @return SocialIdentity
     * @throws LoghyCallbackHandleException
     */
    private function createSocialIdentity(User $user, string $loghyId, string $type, array $userInfo): SocialIdentity
    {
        $sub = $userInfo['sid'] ?? throw new LoghyCallbackHandleException('The sub is not found in user information.');

        return $user->socialIdentities()->firstOrCreate([
            'loghy_id' => $loghyId, 'type' => $type, 'sub' => $sub,
        ]);
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
            $response = Loghy::deleteUserInfo($loghyId);
            return $this->verifyLoghyResponse($response);
        } catch (\Exception $e) {
            Log::error("Failed to delete user information in Loghy. Its LoghyID is {$loghyId}");
            return false;
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

    /**
     * @throws \Exception
     */
    private function verifyLoghyResponse(array $response): bool|array
    {
        if ($response['result'] === false) {
            throw new \Exception($response['error_message'], $response['error_code']);
        }

        return $response['data'] ?? true;
    }
}
