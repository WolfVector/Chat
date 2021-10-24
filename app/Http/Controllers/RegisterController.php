<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Image;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    public function index()
    {
        return view('user.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|alpha',
            'username' => 'required|alpha_dash|unique:users',
            'email' => 'required|email|unique:users',
            'password' => 'required|alpha_num|confirmed'
        ]);

        $user = new User;
        $user->name = $request->input('name');
        $user->username = $request->input('username');
        $user->email = $request->input('email');
        $user->password = Hash::make($request->input('password'));
        $user->save();

        $image = new Image;
        $image->name = 'default.png';
        $image->user_id = $user->id;
        $image->save();

        return redirect('/login');
    }
}
