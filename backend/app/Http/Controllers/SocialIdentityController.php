<?php

namespace App\Http\Controllers;

use App\Facades\Loghy;
use App\Models\SocialIdentity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SocialIdentityController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     * 
     * @param Request $request
     * @param SocialIdentity $socialIdentity
     *
     * @return mixed
     */
    public function destroy(Request $request, SocialIdentity $socialIdentity)
    {
        $userId = Auth::id();
        abort_unless(
            $userId == $socialIdentity->user->id,
            403
        );

        try {
            Loghy::deleteUser($socialIdentity->loghy_id);
            $socialIdentity->delete();
        } catch (\Exception $e) {
            report($e);
            Log::error(
                "Failed to delete user in Loghy",
                ['user_id' => $userId, 'social_identity_id' => $socialIdentity->id]
            );
            return redirect()->route('home')->with('error', 'Failed to disconnect.');
        }

        return redirect()->route('home')->with('success', 'Disconnected ✅');
    }
}
