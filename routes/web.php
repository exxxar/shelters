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

   /* $lon = 37.782442;
    $lat = 47.978296;
    $radius = 2;
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

    dd(collect($array)->skip(10)->take(5));*/
/*    $data = YaGeo::setQuery("Горловка ул. Пионерская 10")->load();
    dd($data->);
    return;*/

 /*   $shelters = Shelter::query()->where("lat",0)->get();

    set_time_limit(3600);

    foreach ($shelters as $shelter) {
        $data = YaGeo::setQuery("$shelter->city $shelter->address")->load();

        if (!is_null($data->getResponse())) {
            $data = (object)$data->getResponse()->getRawData();

            $tmp = explode(' ', $data->Point["pos"]);

            $shelter->lat = $tmp[1] ?? 0;
            $shelter->lon = $tmp[0] ?? 0;
            $shelter->save();
        }
    }*/

   /* set_time_limit(3600);
    $users = \App\User::query()->get();

    foreach ($users as $user){
        \App\Facades\MilitaryServiceFacade::bot()->sendMessage($user->telegram_chat_id,"
        ⚡️Друзья, подписывайтесь на Telegram-канал Народной Дружины и будьте вкурсе последних новостей.\n
Подписаться можно здесь👇🏻\n
@nddnr
        ");
    }*/

});

Route::any('/telegram/handler', [\App\Http\Controllers\TelegramController::class, "handler"]);
