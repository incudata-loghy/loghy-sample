<?php

namespace App\Http\Controllers;

use App\Facades\Loghy;
use App\Models\SocialIdentity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
     * @return mixed
     */
    public function destroy(Request $request, SocialIdentity $socialIdentity)
    {
        $userId = Auth::id();
        abort_unless(
            $userId == $socialIdentity->user->id,
            403
        );

        $response = Loghy::deleteUserId($socialIdentity->loghy_id);
        if ($response['result'] === false) {
            return redirect()
                ->route('home')
                ->with('error', 'Failed to delete user ID. Error message: ' . $response['error_message']);
        }

        $socialIdentity->delete();

        return redirect()
            ->route('home')
            ->with('success', 'Disconnected ✅');
    }
}
