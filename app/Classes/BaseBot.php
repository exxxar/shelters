<?php

namespace App\Classes;

use Telegram\Bot\FileUpload\InputFile;
use Telegram\Bot\Laravel\Facades\Telegram;

abstract class BaseBot
{
    protected $bot; //экземпляр бота

    protected $chatId; //идентификатор чата текущего пользовтеля

    protected $routes = []; //добавленные в систему маршруты

    protected $next = []; //список маршрутов для повторного вызова

    /* блок ответа на сообщения */
    public function reply($message)
    {
        return $this->sendMessage($this->chatId, $message);
    }

    public function replyEditInlineKeyboard($messageId, $keyboard)
    {
        return $this->editInlineKeyboard($this->chatId, $messageId, $keyboard);
    }

    public function replyLocation($lat, $lon)
    {
        return $this->sendLocation($this->chatId, $lat, $lon);
    }

    public function replyInvoice($title, $description, $prices, $data)
    {
        return $this->sendInvoice($this->chatId, $title, $description, $prices, $data);
    }

    public function replyKeyboard($message, $keyboard = [])
    {
        return $this->sendReplyKeyboard($this->chatId, $message, $keyboard);
    }

    public function replyDocument($caption, $path, $filename = 'locations.pdf')
    {
        return $this->sendDocument($this->chatId, $caption, InputFile::createFromContents($path, $filename));
    }

    public function inlineKeyboard($message, $keyboard = [])
    {
        return $this->sendInlineKeyboard($this->chatId, $message, $keyboard);

    }

    /* блок отправки сообщения на указанный чат id */
    //отправка текстового сообщения
    public function sendMessage($chatId, $message)
    {
        try {
            $this->bot->sendMessage([//метод из телеграм API sendMessage
                "chat_id" => $chatId, //основной параметр, chat_id того, кому отправляется сообщение
                "text" => $message, //основной параметр, текст сообщения
                "parse_mode" => "HTML" //вспомогательный параметр - тип разметки сообщения
            ]);
        } catch (\Exception $e) {

        }
        return $this; //возвращаем ссылку на себя же для упрощенного вызова функций
    }

    //отправка локации
    public function sendLocation($chatId, $lat, $lon)
    {
        try {
            $this->bot->sendLocation([
                "chat_id" => $chatId, //идентификатор телеграм чата
                "latitude" => $lat, //широта
                "longitude" => $lon, //долгота
                "parse_mode" => "HTML" //режима отображения контента (парсинга),
                // другие вариант: Markdown и MarkdownV2
            ]);
        } catch (\Exception $e) {

        }
        return $this;

    }

    //отправка документ
    public function sendDocument($chatId, $caption, $path)
    {
        try {
            $this->bot->sendDocument([
                "chat_id" => $chatId,//идентификатор телеграм чата
                "document" => $path, //отпрваляемое содержимое или путь
                "caption" => $caption, //подпись к документу
                "parse_mode" => "HTML" //режима отображения контента (парсинга),
                // другие вариант: Markdown и MarkdownV2
            ]);
        } catch (\Exception $e) {

        }
        return $this;

    }

    //отправка фото
    public function sendPhoto($chatId, $caption, $path)
    {
        try {
            $this->bot->sendPhoto([
                "chat_id" => $chatId, //идентификатор телеграм чата
                "photo" => $path,//отпрваляемое содержимое или путь
                "caption" => $caption, //подпись к изображению
                "parse_mode" => "HTML" //режима отображения контента (парсинга),
                // другие вариант: Markdown и MarkdownV2
            ]);
        } catch (\Exception $e) {

        }

        return $this;

    }

    //отправка клавитауры главного меню
    public function sendReplyKeyboard($chatId, $message, $keyboard)
    {
        try {
            $this->bot->sendMessage([
                "chat_id" => $chatId, //идентификатор телеграм чата
                "text" => $message, //отправляемое сообщение (Вместе с клавиатурой)
                "parse_mode" => "HTML", //режима отображения контента (парсинга),
                // другие вариант: Markdown и MarkdownV2
                'reply_markup' => json_encode([
                    'keyboard' => $keyboard, //объект клавиатуры
                    'resize_keyboard' => true, //растягивать или нет клавиатуру до максимально возможного значения по высоте
                    'input_field_placeholder' => "Выбор действия" //текстовая подпись для поля ввода
                ])

            ]);

        } catch (\Exception $e) {

        }
        return $this;
    }




    //отправка запроса на оплату
    public function sendInvoice($chatId, $title, $description, $prices, $data)
    {
        try {
            $this->bot->sendInvoice([
                "chat_id" => $chatId, //идентификатор телеграм чата
                "title" => $title, //пояснение к оплате
                "description" => $description, //описание сделки
                "payload" => $data, //полезная нагрузка
                "provider_token" => env("PAYMENT_PROVIDER_TOKEN"), //ключ платежной системы
                "currency" => env("PAYMENT_PROVIDER_CURRENCY"), //валюта оплаты
                "prices" => $prices, //цена

                //[
                //                ["label"=>"Test", "amount"=>10000]
                //            ]
            ]);
        } catch (\Exception $e) {

        }

        return $this;
    }

    //редактирование встроенной клавитатуры
    public function editInlineKeyboard($chatId, $messageId, $keyboard)
    {
        try {
            $this->bot->editMessageReplyMarkup([
                "chat_id" => $chatId, //идентификатор телеграм чата
                "message_id" => $messageId, //идентификатор сообщения внутри чта
                "parse_mode" => "HTML",//режима отображения контента (парсинга),
                // другие вариант: Markdown и MarkdownV2
                'reply_markup' => json_encode([
                    'inline_keyboard' => $keyboard, //новая встроенная клавиатура,
                    // должна отличаться от содержимого предидущей клавиатуры
                ])

            ]);
        } catch (\Exception $e) {

        }
        return $this;
    }

    //отправка встроенной клавитауры
    public function sendInlineKeyboard($chatId, $message, $keyboard)
    {

        try {
            $this->bot->sendMessage([
                "chat_id" => $chatId,//идентификатор телеграм чата
                "text" => $message, //текст сообещения, который отправляется с клавиатурой
                "parse_mode" => "HTML",//режима отображения контента (парсинга),
                // другие вариант: Markdown и MarkdownV2
                'reply_markup' => json_encode([
                    'inline_keyboard' => $keyboard, //встроенная клавиатура,
                ])

            ]);
        } catch (\Exception $e) {

        }

        return $this;
    }

    /* блок системных функций ядра бот */

    public function next($name)
    {
        //проверяем массив маршрутов $routes на вхождение функции с именем $name
        foreach ($this->routes as $route) {
            if (isset($route["name"])) //проверка проходит только среди существующих параметров
                if ($route["name"] == $name) //сравнение на равенство
                    array_push($this->next, [ //если имя найдено в маршрутах,
                        // то копируем связанную с этим именем функцию в новый массив $next
                        "name" => $name,
                        "function" => $route["function"], //скопированная функция
                        //  "arguments"=>$arguments??[]
                    ]);
        }

        return $this;
    }

    public function addRoute($path, $function, $name = null): TelegramBotHandler //возврщает ссылку на базовый класс
    {
        //добавляем в общий список маршрутов новый маршрут
        array_push($this->routes, [
            "path" => $path, //внешний путь для обращения (регулярное выражение)
            "is_service" => false, //является ли маршрут сервисным (системным)
            "function" => $function, //вызываемая функция
            "name" => $name //внутреннее имя маршрута
        ]);

        return $this;
    }

    public function addRouteLocation($function): TelegramBotHandler
    {
        array_push($this->routes, [
            "path" => "location",
            "is_service" => true,
            "function" => $function
        ]);

        return $this;
    }

    public function addRouteFallback($function): TelegramBotHandler
    {
        array_push($this->routes, [
            "path" => "fallback",
            "is_service" => true,
            "function" => $function
        ]);

        return $this;
    }
}
