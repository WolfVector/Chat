<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class UserController extends Controller
{
    public function home()
    {
        /* Let's make it simple. Let's pull all the users */
        $users = User::select('id', 'username')
            ->where('id', '!=', Auth::guard('user')->user()->id)
            ->get();

        return view('user.home')->with('users', $users);
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
