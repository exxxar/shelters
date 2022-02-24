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

Route::get("/test" , function (){

    /*$data = YaGeo::setQuery('Kiev, Vishnevoe, Lesi Ukrainki, 57')->load();
    $data = (object)$data->getResponse()->getRawData();


    $tmp = explode(' ',$data->Point["pos"]);
    dd($tmp);*/



});

Route::any('/telegram/handler', [\App\Http\Controllers\TelegramController::class, "handler"]);
