<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/callback', function (\Illuminate\Http\Request $request) {
    if (Session::has('google_auth') && $request->has('code')) {
        $google = \App\Models\Google::find(Session::get('google_auth'));
        if (isset($google)) {
            $google->authorization_code = $request->get('code');
            $token                      = $google->getGoogleClient()->fetchAccessTokenWithAuthCode($google->authorization_code);
            $google->setToken($token);
            return response('authorized');
        }
    }
    return response('something went wrong');
})->name('google.callback');

Route::get('/google', function (\Illuminate\Http\Request $request) {
    $google = \App\Models\Google::first();
    $client = $google->getGoogleClient();
    $url    = $client->createAuthUrl();
    if (config('app.debug')) {
        $url .= '&XDEBUG_IDEKEY=phpstorm';
    }
    Session::put('google_auth', $google->id);
    return Redirect::to($url);
})->name('google.login');
