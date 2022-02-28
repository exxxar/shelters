<?php

namespace App\Classes;

use App\User;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;

class TelegramBotHandler extends BaseBot
{
    private $user;

    //$this->bot->setWebhook(['url' => 'https://api.telegram.org/bot482400672:AAEZu9rGFZfbMOwrqCghI9cR4JhUAf_4xjQ/setWebhook?url=https://9324-109-254-191-71.ngrok.io']);

    public function __construct()
    {
        $this->bot = new Api(env("TELEGRAM_BOT_TOKEN"));
    }

    public function createUser($from)
    {

        $telegram_chat_id = $from->id; //идентификатор чата пользователя из телеграм
        $first_name = $from->first_name ?? null; //имя пользователя из телеграм
        $last_name = $from->last_name ?? null; //фамилия пользователя из телеграм
        $username = $from->username ?? null; //псевдоним пользователя

        //проверяем наличие данного пользователя по его телеграм ID. В системе может быть только 1 такой
        //пользователь. И если он есть, то мы просто возвращаем его данные.
        $this->user = User::where("telegram_chat_id", $telegram_chat_id)->first();

        //А если пользователя нет, то создаем нового пользователя
        if (is_null($this->user)) {
            $this->user = User::create([
                'name' => $username ?? $telegram_chat_id, //берем псевдоним пользователя,
                // а в случае отсуствия - берем в качестве псевдонима идентификатор
                'email' => "$telegram_chat_id@donbassit.ru", //создаем почту н основе идентификатора пользователя
                'telegram_chat_id' => $telegram_chat_id, //задаем телеграм ID (уникальное значение)
                'password' => bcrypt($telegram_chat_id), //генерируем пароль на основе идентификатора
                'full_name' => "$first_name $last_name" ?? null, //заполняем имя пользовтеля
                'radius' => 0.5 //указываем радиус поиска объектов по умолчанию.

            ]);
        }
    }

    public function currentUser()
    {
        return $this->user; //отдает текущего пользователя системы
    }

    public function bot()
    {
        return $this; // отдает данные о текущем классе, необходимо для фасада
    }

    public function handler()
    {

        $update = $this->bot->getWebhookUpdate(); //получаем данные от телеграм по средствам WebHook

        include_once base_path('routes/bot.php'); //подключем обработку методов самого бота из отдельного файла

        $item = json_decode($update); //преобрзуем полученные текстовые данны в объект json

        // Log::info(print_r($item, true));

        //формируем сообщение из возможных вариантов входных данных
        $message = $item->message ??
            $item->edited_message ??
            $item->callback_query->message ??
            null;

        //если сообщения нет, то завершаем работу
        if (is_null($message))
            return;

        //разделяем логику получения данных об отправителе,
        // так как данные приходят в разных частях JSON-объекта,
        // то создадим условие, по которому будем различать откуда получать эти данные
        if (isset($update["callback_query"]))
            $this->createUser($item->callback_query->from);
        else
            $this->createUser($message->from);

        //получаем текстовую составляющую сообщения: это может быть либо текст либо команда по нажатию кнопки
        $query = $item->message->text ?? $item->callback_query->data ?? '';

        //сохраняем идентификатор чата текущего пользователя
        $this->chatId = $message->chat->id;

        $find = false; //флаг, отвечающих за поиск методов в системе

        if (isset($update["message"]["location"])) { //проверяем есть ли сообщении объект с координатами
            //если такой объект есть, то ищем для него обработчик среди существующих маршрутов
            foreach ($this->routes as $item) {
                //если путь в маршруте отсутствует, то такой маршрут игнорируем
                if (is_null($item["path"]))
                    continue;

                //если путь соотвествует обработку локации, то вызываем его (сколько бы таких обработчиков не было)
                if ($item["path"] === "location")
                    try {
                        //вызываем связанную с маршрутом функцию через замыкание
                        // и передаем объект сообщения и объект с координатами в функцию
                        $item["function"]($message, (object)[
                            "lat" => $update["message"]["location"]["latitude"],
                            "lon" => $update["message"]["location"]["longitude"]
                        ]);
                    } catch (\Exception $e) {
                        //вывод ошибки в случае если функция не найдена или параметры функции не верные
                        Log::error($e->getMessage() . " " . $e->getLine());
                    }
            }

            //для случая локаций дальнейшая обработк данных от вебхук закончена
            return;
        }

        $matches = [];
        $arguments = [];
        foreach ($this->routes as $item) { //перебирем список всех маршрутов системы
            //если маршрут пустой или является сервисным, то пропускаем его обработку
            if (is_null($item["path"]) || $item["is_service"])
                continue;
            $reg = $item["path"]; //получаем маршрут как регулярное выражение
            //проверяем текущий входной текст регулярным выражением текущего маршрута,
            // а  в ответ получаем список параметров регулярного выражения
            if (preg_match($reg . "$/i", $query, $matches) != false) {
                //если у регулярного выражения есть параметры, то формируем из них массив,
                // который будет передан на вход вызываемой функции
                foreach ($matches as $match)
                    array_push($arguments, $match);

                try {
                    //вызываем найденную функцию и передаем в неё аргументы
                    $item["function"]($message, ... $arguments);
                    $find = true; //говорим системе что маршрут найден
                } catch (\Exception $e) {
                    //в случае ошибки вызова функции или ошибки числ аргументов
                    Log::error($e->getMessage() . " " . $e->getLine());
                }
                break; //завершаем поиск функций
            }

        }

        //проверим содержимое массива "следующих" функций,
        // если массив не пустой, то вызовим еще одну функцию (или несколько)
        if (!empty($this->next)) {
            foreach ($this->next as $item) {
                try {
                    $item["function"]($message);
                    $find = true;
                } catch (\Exception $e) {
                    Log::error($e->getMessage() . " " . $e->getLine());
                }
            }
        }

        //на случай если всё-таки ничего не найден
        if (!$find) {
            $isFallbackFind = false; //флаг поиска обработчиков ошибочных ситуаций
            foreach ($this->routes as $item) {
                //если пустое значение path, то пропускаем данную функцию
                if (is_null($item["path"]))
                    continue;

                //проверяем является ли содержимое path обрботчиком ошибочного ввода
                if ($item["path"] === "fallback") {
                    try {
                        //вызываем связанную с этим обработчиком функцию
                        $item["function"]($message); //передаем только объект сообщения
                        $isFallbackFind = true; //отмечаем что обработчик найден
                    } catch (\Exception $e) {
                        //если возникла ошибка в функции или неверно переданы аргументы
                        Log::error($e->getMessage() . " " . $e->getLine());
                    }
                }
            }
            //если не найден обработчик, то остается только оповестить пользователя об ошибке
            if (!$isFallbackFind)
                $this->reply("Ошибка обработки данных!");
        }


    }

}
