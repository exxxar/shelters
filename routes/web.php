<?php

use App\Models\Shelter;
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

    $lon = 37.782442;
    $lat = 47.978296;
    $radius = 1;
    $page = 0;
    $array = Shelter::getNearestQuestPoints($lat, $lon, $radius)->toArray();

// перебираем массив
    for ($j = 0; $j < count($array) - 1; $j++){

        for ($i = 0; $i < count($array) - $j - 1; $i++){


            $array[$i]["dist"] = round(Shelter::dist($array[$i]["lat"], $array[$i]["lon"], $lat, $lon));
            if (round(Shelter::dist($array[$i]["lat"], $array[$i]["lon"], $lat, $lon)) >
                round(Shelter::dist($array[$i+1]["lat"], $array[$i+1]["lon"], $lat, $lon))

            ) // если текущий элемент больше следующего
           {
                // меняем местами элементы
                $tmp_var = $array[$i + 1];
                $array[$i + 1] = $array[$i];
                $array[$i] = $tmp_var;


            }
        }
    }

    dd(collect($array)->take(5)->skip(0));

});

Route::any('/telegram/handler', [\App\Http\Controllers\TelegramController::class, "handler"]);
