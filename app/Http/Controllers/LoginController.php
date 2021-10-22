<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function __construct()
    {
        /* Detect if user is online. If he is, then redirect according to
            redirectIfAuthenticate
        */ 
        $this->middleware('guest:user')->except('logout');
    }

    public function index()
    {
        return view('user.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|alpha_dash',
            'password' => 'required|alpha_num'
        ]);

        if(Auth::guard('user')->attempt(['username' => $request->input('username'), 'password' => $request->input('password')]))
        {
            $request->session()->regenerate();

            return redirect('/home');
        }

        return back()->withInput()->withErrors([
            'username' => 'The provided credentials do not match our records'
        ]);
    }
}
