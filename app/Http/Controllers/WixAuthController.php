<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WixAuthController extends Controller
{
    public function redirectToWix()
    {
        $clientId = config('services.wix.app_id');
        $redirectUri = route('wix.callback');
        $scope = 'stores.products:read stores.collections:read ecom.carts:read ecom.carts:write ecom.checkouts:read ecom.checkouts:write ecom.orders:read ecom.orders:write';
        $state = csrf_token();
        $url = "https://www.wix.com/installer/install?client_id={$clientId}&redirect_uri={$redirectUri}&scope={$scope}&state={$state}";
        return redirect($url);
    }

    public function handleCallback(Request $request)
    {
        Log::info('WIX CALLBACK CALLED', $request->all());
        $code = $request->input('code');
        $clientId = env('WIX_APP_ID');
        $clientSecret = env('WIX_APP_SECRET');
        $redirectUri = route('wix.callback');

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post('https://www.wixapis.com/oauth/access', [
            'grant_type' => 'authorization_code',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'code' => $code,
        ]);

        $data = $response->json();
        Log::info('WIX CALLBACK RESPONSE', $data);
        $success = false;
        if (isset($data['access_token'])) {
            // Сохраняем access_token и refresh_token в settings
            DB::table('settings')->updateOrInsert(
                ['key' => 'wix_access_token'],
                ['value' => $data['access_token'], 'updated_at' => now()]
            );
            if (isset($data['refresh_token'])) {
                DB::table('settings')->updateOrInsert(
                    ['key' => 'wix_refresh_token'],
                    ['value' => $data['refresh_token'], 'updated_at' => now()]
                );
            }
            $success = true;
            Log::info('WIX TOKEN SAVED');
        } else {
            Log::error('WIX CALLBACK ERROR: no access_token', $data);
        }
        dd(['success' => $success, 'data' => $data, 'response' => $response, 'request' => $request->all()]);
        if ($success) {
            return redirect('/')->with('success', 'Wix connected!');
        } else {
            return redirect('/')->with('error', 'Wix connection failed! Check logs.');
        }
    }
}
