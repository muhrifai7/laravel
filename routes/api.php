<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

// Login Allo via Arka API
Route::post('/allo/create_header_allo', 'API\AlloApiController@create_header_allo')->name('create_header_allo');
Route::post('/allo/create_code_challenge', 'API\AlloApiController@create_code_challenge')->name('create_code_challenge');
Route::post('/allo/decrypt_header', 'API\AlloApiController@decrypt_header')->name('decrypt_header');
Route::post('/allo/allo_login_register','API\AlloApiController@allo_auth_page' )->name('allo_auth_page');
Route::post('/allo/allo_request_token','API\AlloApiController@allo_request_token' )->name('allo_request_token');
Route::post('/allo/allo_refresh_token','API\AlloApiController@allo_refresh_token' )->name('allo_refresh_token');
Route::post('/allo/allo_member_profile','API\AlloApiController@allo_member_profile' )->name('allo_member_profile');