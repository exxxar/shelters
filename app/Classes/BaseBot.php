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
                "chat_id" => $chatId,
                "latitude" => $lat,
                "longitude" => $lon,
                "parse_mode" => "HTML"
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
                "chat_id" => $chatId,
                "document" => $path,
                "caption" => $caption,
                "parse_mode" => "HTML"
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
                "chat_id" => $chatId,
                "text" => $message,
                "parse_mode" => "HTML",
                'reply_markup' => json_encode([
                    'keyboard' => $keyboard,
                    'resize_keyboard' => true,
                    'input_field_placeholder' => "Выбор действия"
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
                "chat_id" => $chatId,
                "title" => $title,
                "description" => $description,
                "payload" => $data,
                "provider_token" => env("PAYMENT_PROVIDER_TOKEN"),
                "currency" => env("PAYMENT_PROVIDER_CURRENCY"),
                "prices" => $prices,
            ]);
        } catch (\Exception $e) {

        }

        return $this;

        //[
        //                ["label"=>"Test", "amount"=>10000]
        //            ]
    }
    //редактирование встроенной клавитатуры
    public function editInlineKeyboard($chatId, $messageId, $keyboard)
    {
        try {
            $this->bot->editMessageReplyMarkup([
                "chat_id" => $chatId,
                "message_id" => $messageId,
                "parse_mode" => "HTML",
                'reply_markup' => json_encode([
                    'inline_keyboard' => $keyboard,
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
                "chat_id" => $chatId,
                "text" => $message,
                "parse_mode" => "HTML",
                'reply_markup' => json_encode([
                    'inline_keyboard' => $keyboard,
                ])

            ]);
        } catch (\Exception $e) {

        }

        return $this;
    }

    /* блок системных функций ядра бот */
    public function next($name)
    {
        foreach ($this->routes as $route) {
            if (isset($route["name"]))
                if ($route["name"] == $name)
                    array_push($this->next, [
                        "name" => $name,
                        "function" => $route["function"],
                        //  "arguments"=>$arguments??[]
                    ]);
        }

        return $this;
    }

    public function addRoute($path, $function, $name = null): TelegramBotHandler
    {
        array_push($this->routes, [
            "path" => $path,
            "is_service" => false,
            "function" => $function,
            "name" => $name
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
