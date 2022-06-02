<?php

namespace App\Http\Controllers\Auth;

use App\Facades\Loghy;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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
                return $this->failRedirect('Account not found. Please register.', 'register');
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

            if ($user = User::findByLoghyUser($loghyUser)) {
                return $this->successRedirect($user, 'Already registered. Logged in 👍');
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

        try {
            Loghy::putUserId($user->id, $loghyUser->getLoghyId());
        } catch (\Exception $e) {
            Log::error("Failed to put user ID", ['user_id' => $user->id, 'loghy_id' => $loghyUser->getLoghyId()]);
            throw $e;
        }

        return $user;
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
}
