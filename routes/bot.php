<?php

use App\Exports\ShelterExport;
use App\Facades\MilitaryServiceFacade;
use App\Models\Shelter;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

function sortNearestQuestPointsArray($array, $lat, $lon){
    for ($j = 0; $j < count($array) - 1; $j++){
        for ($i = 0; $i < count($array) - $j - 1; $i++){
            if (round(Shelter::dist($array[$i]["lat"], $array[$i]["lon"], $lat, $lon)) >
                round(Shelter::dist($array[$i+1]["lat"], $array[$i+1]["lon"], $lat, $lon))
            )
            {
                $tmp_var = $array[$i + 1];
                $array[$i + 1] = $array[$i];
                $array[$i] = $tmp_var;
            }
        }
    }

    return $array;
}

function getInfoByCoords($coords, $page = 0)
{

    $lat = $coords->lat;
    $lon = $coords->lon;

    $user = MilitaryServiceFacade::bot()->currentUser();

    $radius = 0.5;

    if (!is_null($user))
        $radius = $user->radius ?? 0.5;

    //MilitaryServiceFacade::bot()->reply(print_r(Shelter::getNearestQuestPoints($lat, $lon, $user->radius)->toArray(), true));
    $findLocation = false;

    $array = Shelter::getNearestQuestPoints($lat, $lon, $radius)->toArray();
    $array = collect(sortNearestQuestPointsArray($array, $lat, $lon))->skip($page*5)->take(5);

    foreach ($array as $pos) {

        $pos = (object)$pos;

        $tmp_text = "<b>Ближайшие точки (в настройках ~$radius км):</b>\n";
        $tmp_text .= "\xF0\x9F\x94\xB6 " . $pos->address . "\n" . round(Shelter::dist($pos->lat, $pos->lon, $lat, $lon)) . " метров от вас \n";
        $tmp_text .= "Город: <b>" . $pos->city . "</b>\n";
        $tmp_text .= "На балане: <b>" . $pos->balance_holder . "</b>\n";
        $tmp_text .= "Отвественный: <b>" . $pos->responsible_person . "</b>\n";
        $tmp_text .= "Описание: <b>" . $pos->description . "</b>\n";

        MilitaryServiceFacade::bot()->replyLocation($pos->lat, $pos->lon);
        MilitaryServiceFacade::bot()->reply($tmp_text);

        $findLocation = true;
        /*  if ($pos->inRange($lat, $lng)) {
              $tmp_text .= "	\xF0\x9F\x94\xB7Точка " . $pos->city . " находится в 0.1км от вас!\n";
          }*/
    }

    if ($findLocation) {

        $tmp = base64_encode("$lat $lon");

        MilitaryServiceFacade::bot()->inlineKeyboard("В вашем радиусе (~$radius км) есть еще точки!", [
            [
                ["text" => "Показать еще!", "callback_data" => "/more_shelters $tmp " . ($page + 1)],
            ]
        ]);
    }

    if (!$findLocation) {
        MilitaryServiceFacade::bot()->inlineKeyboard("Не найдено (в радиусе ~$radius км) ни одной ближайшей к вам точки:(", [
            [
                ["text" => "Сменить настройки дальности", "callback_data" => "/settings"],
            ]
        ]);
    }
}

MilitaryServiceFacade::bot()
    ->addRoute("/.*Скачать список", function ($message) {

        MilitaryServiceFacade::bot()->reply("Скачать список!");


        Excel::store(new ShelterExport, 'coords.xlsx');


        MilitaryServiceFacade::bot()->replyDocument("Список всех убежищ", \Illuminate\Support\Facades\Storage::get("coords.xlsx"), "coords.xlsx");


        $schelters = \App\Models\Shelter::query()->get();

        if (empty($schelters)) {
            MilitaryServiceFacade::bot()->reply("Список убежищ пуст!");
            return;
        }

    })
    ->addRoute("/.*Доступные регионы ([()0-9]+)", function ($message, $command, $count) {

        MilitaryServiceFacade::bot()->reply("Доступные регионы!");

        $shelters = Shelter::query()->select("city", "id")->get()->unique('city');

        $keyboard = [];

        $index = 0;

        $tmp = [];

        foreach ($shelters as $key => $shelter) {

            $index++;

            array_push($tmp, ["text" => $shelter->city, "callback_data" => "/region " . $key . " 0"]);

            if ($index % 2 == 0 || $index == count($shelters)) {
                array_push($keyboard, $tmp);
                $tmp = [];
            }

        }

        MilitaryServiceFacade::bot()
            ->inlineKeyboard("Какой регион интересует?", $keyboard)
            ->next("start");

    }, "regions")
    ->addRoute("/settings|.*Настройки", function ($message) {

        $radius_table = [
            0.1, 0.2, 0.3, 0.4, 0.5, 1, 2
        ];
        $index = 0;

        $user = MilitaryServiceFacade::bot()->currentUser();

        $radius = 0.5;

        if (!is_null($user))
            $radius = $user->radius ?? 0.5;

        foreach ($radius_table as $key => $value) {
            if ($radius === $value) {
                $index = $key;
                break;
            }
        }

        MilitaryServiceFacade::bot()->inlineKeyboard("<b>Радиус поиска убежищ</b>",
            [
                [
                    ["text" => ($index == 0 ? "\xE2\x9C\x85" : "") . "До 100 метров", "callback_data" => "/change_setting 0"],
                    ["text" => ($index == 1 ? "\xE2\x9C\x85" : "") . "До 200 метров", "callback_data" => "/change_setting 1"],
                    ["text" => ($index == 2 ? "\xE2\x9C\x85" : "") . "До 300 метров", "callback_data" => "/change_setting 2"],
                    ["text" => ($index == 3 ? "\xE2\x9C\x85" : "") . "До 400 метров", "callback_data" => "/change_setting 3"],
                ],
                [

                    ["text" => ($index == 4 ? "\xE2\x9C\x85" : "") . "До 500 метров", "callback_data" => "/change_setting 4"],
                    ["text" => ($index == 5 ? "\xE2\x9C\x85" : "") . "До 1000 метров", "callback_data" => "/change_setting 5"],
                    ["text" => ($index == 6 ? "\xE2\x9C\x85" : "") . "До 2000 метров", "callback_data" => "/change_setting 6"],
                ]
            ]
        );
    }, "settings")
    ->addRoute("/.*Показать список", function ($message) {

        MilitaryServiceFacade::bot()->reply("Показать список!");

        $schelters = \App\Models\Shelter::query()->get();

        if (empty($schelters)) {
            MilitaryServiceFacade::bot()->reply("Список убежищ пуст!");
            return;
        }

        $tmp = "";
        foreach ($schelters as $schelter)
            $tmp .= "<a href='http://www.example.com/'>inline URL</a>";

        MilitaryServiceFacade::bot()->reply($tmp);
    })
    ->addRoute("/.*Разработчикам на кофе", function ($message) {
        MilitaryServiceFacade::bot()->inlineKeyboard("А вот тут вы сможете пожертвовать для разработчиков на кофе:)", [
            [
                ["text" => "Пожертвовать 500 руб", "callback_data" => "/invoice", "pay" => true],
            ],

        ]);
    })
    ->addRouteLocation(function ($message, $coords) {
        //MilitaryServiceFacade::bot()->reply("Координаты!" . $coords->lon . " " . $coords->lat);
        getInfoByCoords((object)[
            "lat" => $coords->lat ?? 0,
            "lon" => $coords->lon ?? 0
        ]);
    })
    ->addRouteFallback(function ($message) {
        $need_to_search = false;
        $text = $message->text ?? "";

        $objects = ["ул.", "c.", "пгт.", "город", "г.", "квартал", "улица", "район", "микрорайон", "мк-р"];

        foreach ($objects as $object) {
            if (mb_strpos($text, $object) !== false) {
                $need_to_search = true;
            }

        }

        if ($need_to_search) {

            try {
                $data = YaGeo::setQuery($text)->load();

                if (!is_null($data->getResponse())){
                    $data = (object)$data->getResponse()->getRawData();

                    $tmp = explode(' ', $data->Point["pos"]);

                    getInfoByCoords((object)[
                        "lat" => $tmp[1] ?? 0,
                        "lon" => $tmp[0] ?? 0
                    ]);
                } else {
                    MilitaryServiceFacade::bot()->reply("На ваш запрос ничего не найдено! Попробуйте ввести данные по примеру <b>город Донецк, ул. Кирова, 22</b>");
                }

            } catch (Exception $e) {
                MilitaryServiceFacade::bot()->reply("На текущий момент поиск ограничен!");
            }


        } else {
            MilitaryServiceFacade::bot()->reply("Обратитесь за помощью в https://vk.com/nddnr");
        }


        //MilitaryServiceFacade::bot()->reply("Методов не обнаружено!");

    });

MilitaryServiceFacade::bot()
    ->addRoute("/region ([0-9a-zA-Z=]+) ([0-9]+)", function ($message, $command, $index, $page) {
        $regions = Shelter::query()->select("city", "id", "lat", "lon")
            ->get()
            ->unique('city')->toArray();

        $shelters = Shelter::query()
            ->where("city", $regions[$index]["city"])
            ->take(20)
            ->skip(0)
            ->get();

        $shelter_in_base = Shelter::query()
            ->where("city", $regions[$index]["city"])->count();


        $tmp = "Вы выбрали город <b>" . $regions[$index]["city"] . "</b>\n";


        foreach ($shelters as $shelter) {

            if ($shelter->lon == 0 || $shelter->lat == 0)
                $link = "https://www.google.com/maps/search/" . $shelter->address." ".$shelter->city;
            else
                $link = "https://www.google.com/maps/search/" . $shelter->lat . "," . $shelter->lon;

            $tmp .= "\xF0\x9F\x93\x8D " . ($shelter->address ?? "-") . " <a href='" . $link . "'>На карте</a>\n";
        }


        $keyboard = [];

        if ($shelter_in_base > 20) {
            array_push($keyboard, [
                ["text" => "Еще убежища", "callback_data" => "/shelters " . $index . " 1"]
            ]);
        }
        MilitaryServiceFacade::bot()->inlineKeyboard("Локаций в регионе ($shelter_in_base - в нашей базе):\n $tmp", $keyboard);


    })
    ->addRoute("/more_shelters ([0-9a-zA-Z=]+) ([0-9]+)", function ($message, $command, $bCoords, $page) {

        $tmp = base64_decode($bCoords);

        $tmp = explode(" ", $tmp);

        $lat = $tmp[0] ?? 0;
        $lon = $tmp[1] ?? 0;

        getInfoByCoords((object)[
            "lat" => $lat,
            "lon" => $lon
        ], $page);

    })
    ->addRoute("/shelters ([0-9a-zA-Z=]+) ([0-9]+)", function ($message, $command, $index, $page) {

        $regions = Shelter::query()->select("city", "id", "lat", "lon")
            ->get()
            ->unique('city')->toArray();

        $shelters = Shelter::query()
            ->where("city", $regions[$index]["city"])
            ->take(20)
            ->skip($page * 20)
            ->get();

        $shelter_in_base = Shelter::query()
            ->where("city", $regions[$index]["city"])->count();


        $tmp = "Вы выбрали город <b>" . $regions[$index]["city"] . "</b>\n";


        foreach ($shelters as $shelter) {

            if ($shelter->lon == 0 || $shelter->lat == 0)
                $link = "https://www.google.com/maps/search/" . $shelter->address." ". $shelter->city;
            else
                $link = "https://www.google.com/maps/search/" . $shelter->lat . "," . $shelter->lon;

            $tmp .= "\xF0\x9F\x93\x8D " . ($shelter->address ?? "-") . " <a href='" . $link . "'>На карте</a>\n";
        }


        $keyboard = [];

        if ($shelter_in_base > $page * 20 + $shelters->count()) {
            array_push($keyboard, [
                ["text" => "Еще убежища", "callback_data" => "/shelters " . $index . " " . ($page + 1)]
            ]);
        }
        MilitaryServiceFacade::bot()->inlineKeyboard("Локаций в регионе ($shelter_in_base - в нашей базе):\n $tmp", $keyboard);


    })
    ->addRoute("/change_setting ([0-9]+)", function ($message, $command, $index) {

        $message = (object)$message;

        $radius_table = [
            0.1, 0.2, 0.3, 0.4, 0.5, 1, 2
        ];
        $user = MilitaryServiceFacade::bot()->currentUser();
        $user->radius = $radius_table[$index < count($radius_table) ? $index : 0];
        $user->save();

        MilitaryServiceFacade::bot()->replyEditInlineKeyboard($message->message_id, [
            [
                ["text" => ($index == 0 ? "\xE2\x9C\x85" : "") . "До 100 метров", "callback_data" => "/change_setting 0"],
                ["text" => ($index == 1 ? "\xE2\x9C\x85" : "") . "До 200 метров", "callback_data" => "/change_setting 1"],
                ["text" => ($index == 2 ? "\xE2\x9C\x85" : "") . "До 300 метров", "callback_data" => "/change_setting 2"],
                ["text" => ($index == 3 ? "\xE2\x9C\x85" : "") . "До 400 метров", "callback_data" => "/change_setting 3"],
            ],
            [

                ["text" => ($index == 4 ? "\xE2\x9C\x85" : "") . "До 500 метров", "callback_data" => "/change_setting 4"],
                ["text" => ($index == 5 ? "\xE2\x9C\x85" : "") . "До 1000 метров", "callback_data" => "/change_setting 5"],
                ["text" => ($index == 6 ? "\xE2\x9C\x85" : "") . "До 2000 метров", "callback_data" => "/change_setting 6"],
            ]
        ]);


    })
    ->addRoute("/next ([0-9a-zA-Z=]+) ([0-9a-zA-Z=]+)", function ($message, $command, $region, $page) {
        MilitaryServiceFacade::bot()->reply("Следующий регион! $command $region $page");
    })
    ->addRoute("/start", function ($message) {

        $shelters_count = Shelter::query()->select("city", "id")->get()->unique('city')->count();
        MilitaryServiceFacade::bot()->replyKeyboard(
            "Главное меню. Тестовая версия. Обновлено <b>25.02.2022 18:00</b>\n
⚡️Друзья, подписывайтесь на Telegram-канал Народной Дружины и будьте вкурсе последних новостей.\n
Подписаться можно здесь👇🏻\n

@nddnr
",

            [
            [
                ["text" => "\xF0\x9F\x93\x8DОтправить координаты", "request_location" => true],
            ],
            [
                ["text" => "\xF0\x9F\x8C\x8DДоступные регионы ($shelters_count)"],
            ],
            [
                ["text" => "\xF0\x9F\x93\x91Скачать список"],
                ["text" => "\xF0\x9F\x92\xBBНастройки"],
            ],
            /*[
                ["text" => "\xE2\x98\x95Разработчикам на кофе"],
            ]*/
        ]);
    }, "start")
    ->addRoute("/help", function ($message) {
        MilitaryServiceFacade::bot()->reply("Здравствуйте!\n
Приветствуем Вас в чат-боте <b>НД ДНР</b>, который покажет адреса бомбоубежищ.\n
В скором времени будет запущено приложение, работающее в оффлайн-режиме.\n
Надеемся, оно Вам не пригодится 🙃\n
<b>Дежурная часть МГБ: 071-300-19-81, 062-301-85-38, 062-340-62-99</b>
");
        MilitaryServiceFacade::bot()->reply("https://vk.com/nddnr По вопросам помощи обращаться по ссылке!");
    })
    ->addRoute("/invoice", function ($message) {
        MilitaryServiceFacade::bot()->replyInvoice("Временно в разработке", "test", [
            ["label" => "В разработке", "amount" => 10000]
        ], "data");
    });
