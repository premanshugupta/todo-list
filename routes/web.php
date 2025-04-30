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
});

Route::get('/','App\Http\Controllers\TaskController@index')->name('index');
Route::get('/tasks','App\Http\Controllers\TaskController@getAll')->name('getAll');
Route::post('/tasks','App\Http\Controllers\TaskController@store')->name('store');
Route::put('/tasks/{task}/toggle','App\Http\Controllers\TaskController@toggle')->name('toggle');
Route::delete('/tasks/{task}','App\Http\Controllers\TaskController@delete')->name('delete');
