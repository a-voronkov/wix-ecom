<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProfileController extends Controller
{
    public function show()
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }
        $user = auth()->user();
        return view('profile', ['user' => $user]);
    }

    public function update(Request $request)
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:32',
        ]);
        $user = auth()->user();
        $user->name = $request->name;
        $user->phone = $request->phone;
        $user->save();
        return back()->with('success', 'Profile updated!');
    }
}
