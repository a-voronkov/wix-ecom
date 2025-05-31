<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AddressController extends Controller
{
    public function index()
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }
        $addresses = DB::table('addresses')->where('user_id', auth()->id())->get();
        return view('addresses', ['addresses' => $addresses]);
    }

    public function store(Request $request)
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }
        $request->validate([
            'label' => 'required|string|max:255',
            'address' => 'required|string|max:255',
        ]);
        DB::table('addresses')->insert([
            'user_id' => auth()->id(),
            'label' => $request->label,
            'address' => $request->address,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return redirect()->route('addresses');
    }

    public function destroy($id)
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }
        DB::table('addresses')->where('id', $id)->where('user_id', auth()->id())->delete();
        return redirect()->route('addresses');
    }
}
