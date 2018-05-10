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

Route::match(['get', 'post'], '/', ['uses' => 'Controller@home'])->middleware('login.oauth');
Route::match(['get', 'post'], '/login', ['uses' => 'Controller@login'])->middleware('login.oauth');
Route::match(['get', 'post'], '/resource/{idResource}', ['uses' => 'Controller@resource'])->middleware('login.oauth')->where('idResource', '[0-9]+');
Route::match(['get', 'post'], '/logout', ['uses' => 'Controller@logout'])->middleware('login.oauth');
Route::post('/booking/status', ['uses' => 'Controller@updateStatus'])->middleware('login.oauth');
Route::post('/booking/timer', ['uses' => 'Controller@updateTimer'])->middleware('login.oauth');

