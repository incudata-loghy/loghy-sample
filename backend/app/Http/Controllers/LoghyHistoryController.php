<?php

namespace App\Http\Controllers;

use App\Models\LoghyHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoghyHistoryController extends Controller
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
    public function destroy(Request $request)
    {
        $userId = Auth::id();
        LoghyHistory::where('user_id', $userId)->delete();

        return redirect()
            ->route('home')
            ->with('success', 'Deleted loghy_history.');
    }
}
