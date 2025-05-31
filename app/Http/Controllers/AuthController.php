<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);
        $user = DB::table('users')->where('email', $request->email)->first();
        if ($user && \Illuminate\Support\Facades\Hash::check($request->password, $user->password)) {
            // Get Eloquent user model by ID
            $userModel = \App\Models\User::find($user->id);
            if ($userModel) {
                \Illuminate\Support\Facades\Auth::login($userModel);
                return redirect('/')->with('success', 'Logged in!');
            }
        }
        return back()->withErrors(['email' => 'Invalid credentials'])->withInput();
    }

    public function logout()
    {
        \Illuminate\Support\Facades\Auth::logout();
        return redirect('/login');
    }
}
