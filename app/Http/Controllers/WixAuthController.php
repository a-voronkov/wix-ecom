<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WixAuthController extends Controller
{
    /** Шаг 0. Сгенерировать ссылку установки из админ-панели сайта */
    public function redirectToWix()
    {
        $clientId  = env('WIX_APP_ID');
        $redirect  = route('wix.callback');       // https://ваш-домен/wix/callback
        $scope     = implode(' ', [
            'stores.products:read',
            'stores.collections:read',
            'ecom.carts:read',   'ecom.carts:write',
            'ecom.checkouts:read','ecom.checkouts:write',
            'ecom.orders:read',  'ecom.orders:write',
        ]);

        $state = Str::random(32);

        // !!! redirectUrl (camelCase), а не redirect_uri
        $url = 'https://www.wix.com/installer/install?' . http_build_query([
            'client_id'   => $clientId,
            'redirectUrl' => $redirect,
            'scope'       => $scope,
            'state'       => $state,
        ]);

        return redirect()->away($url);
    }

    /** Шаг 1 и 2. Принимаем либо ?token, либо ?code */
    public function handleCallback(Request $request)
    {
        Log::info('WIX CALLBACK', $request->all());

        // ----- Шаг 1. Wix прислал только token (legacy-flow) -----------------
        if ($request->filled('token') && !$request->filled('code')) {
            $installUrl = 'https://www.wix.com/installer/install?' . http_build_query([
                'token'       => $request->input('token'),
                'appId'       => env('WIX_APP_ID'),
                'redirectUrl' => route('wix.callback'),
                'scope'       => implode(' ', [
                    'stores.products:read',
                    'stores.collections:read',
                    'ecom.carts:read',   'ecom.carts:write',
                    'ecom.checkouts:read','ecom.checkouts:write',
                    'ecom.orders:read',  'ecom.orders:write',
                ]),
                'state'       => Str::random(32),
            ]);

            return redirect()->away($installUrl);   // отправляем владельца подтвердить права
        }

        // ----- Шаг 2. Пришёл code, меняем на access_token ---------------------
        if (!$request->filled('code')) {
            Log::error('Wix callback without code or token');
            return redirect('/')->with('error', 'Wix callback without code or token');
        }

        $code          = $request->input('code');
        $instanceId    = $request->input('instanceId'); // пригодится позже
        $clientId      = env('WIX_APP_ID');
        $clientSecret  = env('WIX_APP_SECRET');
        $redirect      = route('wix.callback');

        $response = Http::asJson()                 // → установит Content-Type: application/json
        ->post('https://www.wixapis.com/oauth/access', [
            'grant_type'    => 'authorization_code',
            'client_id'     => config('services.wix.app_id'),
            'client_secret' => config('services.wix.app_secret'),
            'code'          => $code,
            'redirect_uri'  => route('wix.callback'),   // можно оставить, Wix не против
        ]);

        $data = $response->json();
        Log::info('WIX TOKEN RESPONSE', $data);

        if (empty($data['access_token'])) {
            Log::error('Wix OAuth error', $data);
            return redirect('/')->with('error', 'Wix connection failed! Check logs.');
        }

        // ----- сохраняем токены и instanceId ---------------------------------
        DB::table('settings')->updateOrInsert(
            ['key' => 'wix_access_token'],
            ['value' => $data['access_token'], 'updated_at' => now()]
        );

        if (!empty($data['refresh_token'])) {
            DB::table('settings')->updateOrInsert(
                ['key' => 'wix_refresh_token'],
                ['value' => $data['refresh_token'], 'updated_at' => now()]
            );
        }

        if ($instanceId) {
            DB::table('settings')->updateOrInsert(
                ['key' => 'wix_instance_id'],
                ['value' => $instanceId, 'updated_at' => now()]
            );
        }

        return redirect('/')->with('success', 'Wix connected!');
    }
}
