<?php

use Illuminate\Http\Request;

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

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::group(['middleware' => ['api']], function () {
    Route::post('friendRequest', 'Api\UserController@friendRequest');
    Route::post('acceptRequest', 'Api\UserController@acceptRequest');
    Route::post('rejectRequest', 'Api\UserController@rejectRequest');
    Route::post('listRequest', 'Api\UserController@listRequest');
    Route::post('listFriend', 'Api\UserController@listFriend');
    Route::post('blockUser', 'Api\UserController@blockUser');
});
