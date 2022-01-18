<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\SocialIdentity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
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
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $user = Auth::user();
        $user = new UserResource($user);
        $snsList = SocialIdentity::status($user);

        return view('home', compact('user', 'snsList'));
    }
}
