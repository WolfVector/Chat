<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Image;
use Illuminate\Support\Facades\Hash;

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

    public function profile()
    {
        $id = Auth::guard('user')->user()->id;

        $user = User::select('users.name', 'users.email', 'users.username', 'images.name AS image_name')
            ->join('images', 'images.user_id', '=', 'users.id')
            ->where('users.id', '=', $id)
            ->first();

        return view('user.profile')->with('user', $user);
    }

    public function basicUpdate(Request $request)
    {
        $request->validate([
            'name' => 'required|alpha',
            'username' => 'required|alpha_dash',
        ]);

        $new_username = ($request->user()->username == $request->input('username')) ? false : true;

        if($new_username)
        {
            $temp = User::select('username')
                ->where('username', '=', $request->input('username'))
                ->first();

            if($temp)
            {
                error_log('wwwwww');
                return back()->withErrors(['msg' => 'The username '.$request->input('username').' already exists']); 
            }
        }

        $user = User::find($request->user()->id);
        $user->name = $request->input('name');
        if($new_username) $user->username = $request->input('username');

        $user->save();

        return redirect('/profile');
    }

    public function changeImage(Request $request)
    {
        $request->validate([
            'image' => 'image|required|max:1999'
        ]);

        $image_name = $request->user()->username.'.'.$request->image->extension();
        $file = $request->image;
        $path = public_path().'/storage';
        $file->move($path, $image_name);

        $image = Image::where('user_id', '=', $request->user()->id)
            ->update(['name' => $image_name]);

        return redirect('/profile');
    }

    public function passwordUpdate(Request $request)
    {
        $request->validate([
            'password' => 'required|alpha_num',
            'newPassword' => 'required|alpha_num|confirmed'
        ]);

        $user = User::find($request->user()->id);
        if(Hash::check($request->input('password'), $user->password))
        {
            $user->password = Hash::make($request->input('newPassword'));
            $user->save();
        }

        return redirect('/profile');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
