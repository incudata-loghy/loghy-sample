<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\LoghyCallbackHandleException;
use App\Facades\Loghy;
use App\Http\Controllers\Controller;
use App\Models\SocialIdentity;
use App\Models\User;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Loghy\SDK\User as LoghyUser;

use function PHPUnit\Framework\assertInstanceOf;

class LoghyController extends Controller
{
    /**
     * Handle callback from Loghy without site_id on successful SNS login.
     *
     * @param Request $request
     * @return mixed
     */
    public function handleLoginCallback(Request $request)
    {
        try {
            $code = $request->input('code');
            if (!$code) {
                return $this->failRedirect('Authentication code is not found in callback data.', 'login');
            }

            $loghyUser = Loghy::setCode($code)->user();
            $user = User::findByLoghyUser($loghyUser);
            if (!$user) {
                Log::info("User not found", ['loghy_user' => $loghyUser]);
                return $this->failRedirect('User not found.', 'login');
            }
            return $this->successRedirect($user, 'Logged in 🎉');

        } catch (\Exception $e) {
            report($e);
            return $this->failRedirect('Something went wrong...', 'login');
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
            $code = $request->input('code');
            if (!$code) {
                return $this->failRedirect('Authentication code is not found in callback data.', 'register');
            }

            $loghyUser = Loghy::setCode($code)->user();

            if (User::findByLoghyUser($loghyUser)) {
                return $this->failRedirect('Already registered. Please login.', 'login');
            }
            $user = $this->registerUser($loghyUser);

            return $this->successRedirect($user, 'Registered 🎉');

        } catch (\Exception $e) {
            report($e);
            return $this->failRedirect('Something went wrong...', 'register');
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
        return $this->failRedirect('Social Login failed.');
    }

    /**
     * Handle callback from Loghy for connect another SNS.
     * 
     * @param Request $request
     * @return mixed
     */
    public function handleConnectCallback(Request $request)
    {
        try {
            $code = $request->input('code');
            if (!$code) {
                return $this->failRedirect('Authentication code is not found in callback data.');
            }
            $loghyUser = Loghy::setCode($code)->user();

            $user = Auth::user();
            if (!$user) {
                Log::info("Not authenticated for connecting", ['loghy_user' => $loghyUser]);
                return $this->failRedirect('Failed to connect without authenticated.');
            }
            assertInstanceOf(User::class, $user); /** @var \App\Models\User $user */

            if ($loghyUser->getUserId() && $loghyUser->getUserId() !== (string)($user->id)) {
                Log::warning("Connecting another user", ['user_id' => $user->id, 'loghy_user' => $loghyUser]);
                return $this->failRedirect('Failed for invalid connection.');
            }

            if ($user->findSocialIdentityByLogyUser($loghyUser)) {
                return $this->failRedirect('Already connected ✅');
            }
            $user->createSocialIdentityByLoghyUser($loghyUser);
            return $this->successRedirect($user, 'Connected 👍');

        } catch (\Exception $e) {
            report($e);
            return $this->failRedirect('Something went wrong...');
        }
    }

    // TODO: handleConnectCallback
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
     * @param \Loghy\SDK\User $loghyUser
     * @return \App\Models\User
     * 
     * @throws \RuntimeException
     */
    private function registerUser(LoghyUser $loghyUser): User
    {
        $user = User::createByLoghyUser($loghyUser);

        if (! Loghy::putUserId($user->id, $loghyUser->getLoghyId())) {
            Log::error("Failed to pu user ID", ['user_id' => $user->id, 'loghy_id' => $loghyUser->getLoghyId()]);
        }

        return $user;
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
    private function failRedirect(string $message, string $route = null)
    {
        if (is_null($route)) {
            $route = Auth::check() ? 'home' : 'login';
        }
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
