<?php

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
});

Route::get('/players', 'DataController@getPlayers');
Route::get('/players/{playerId}/posts', 'DataController@getPlayerPosts');
Route::get('/players/{playerId}/stats', 'DataController@getPlayerStats');
Route::get('/players/{playerId}/posts-cloud', 'DataController@getPlayerPostCloud');
Route::get('/players/{playerId}/comments-cloud', 'DataController@getPlayerCommentsCloud');
Route::get('/players/{playerId}/compare/{player2Id}', 'DataController@getPlayersCompare');
Route::get('/last', 'DataController@getLast');
Route::get('/lastest', 'DataController@getLast');