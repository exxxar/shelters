<?php

namespace App\Classes;

use App\User;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;

class TelegramBotHandler extends BaseBot
{
    private $user;

    public function __construct()
    {
        $this->bot = new Api(env("TELEGRAM_BOT_TOKEN"));


        //$this->bot->setWebhook(['url' => 'https://api.telegram.org/bot482400672:AAEZu9rGFZfbMOwrqCghI9cR4JhUAf_4xjQ/setWebhook?url=https://9324-109-254-191-71.ngrok.io']);


    }

    public function createUser($telegram_chat_id){
        $response = $this->bot->getMe();

        $botId = $response->getId();
        $firstName = $response->getFirstName();
        $username = $response->getUsername();


        $this->user = User::where("telegram_chat_id", $telegram_chat_id)->first();

        if (is_null($this->user)) {
            $this->user = User::create([
                'name' => $username ?? $firstName ?? null,
                'email' => "$telegram_chat_id@donbassit.ru",
                'telegram_chat_id' => $telegram_chat_id,
                'password' => bcrypt($telegram_chat_id),
                'full_name' => $firstName ?? null,
                'radius' => 0.3

            ]);
        }
    }

    public function currentUser(){
        return $this->user;
    }

    public function bot()
    {
        return $this;
    }

    public function handler()
    {

        $update = $this->bot->getWebhookUpdate();

        include_once base_path('routes/bot.php');

        $item = json_decode($update);

        Log::info(print_r($item, true));

        $message = $item->message ?? $item->edited_message ?? $item->callback_query->message ?? null;

        if (is_null($message))
            return;

        $this->createUser($message->chat->id);

        $query = $item->message->text ?? $item->callback_query->data ?? '';

        $this->chatId = $message->chat->id;

        $find = false;


        if (isset($update["message"]["location"])) {
            foreach ($this->routes as $item) {

                if (is_null($item["path"]))
                    continue;

                if ($item["path"] === "location")
                    try {
                        $item["function"]($message, (object)[
                            "lat" => $update["message"]["location"]["latitude"],
                            "lon" => $update["message"]["location"]["longitude"]
                        ]);
                    } catch (\Exception $e) {
                        Log::error($e->getMessage() . " " . $e->getLine());
                    }
            }


            return;
        }

        $matches = [];
        $arguments = [];

        foreach ($this->routes as $item) {

            if (is_null($item["path"]) || $item["is_service"])
                continue;

            $reg = $item["path"];

            if (preg_match($reg . "$/i", $query, $matches) != false) {
                foreach ($matches as $match)
                    array_push($arguments, $match);

                try {
                    $item["function"]($message, ... $arguments);
                    $find = true;
                } catch (\Exception $e) {
                    Log::error($e->getMessage() . " " . $e->getLine());
                }
                break;
            }

        }

        if (!empty($this->next)){
            foreach ($this->next as $item){
                try {
                    $item["function"]($message);
                    $find = true;
                }catch (\Exception $e){
                    Log::error($e->getMessage() . " " . $e->getLine());
                }
            }
        }

        if (!$find) {
            $isFallbackFind = false;
            foreach ($this->routes as $item) {

                if (is_null($item["path"]))
                    continue;

                if ($item["path"] === "fallback") {
                    try {
                        $item["function"]($message);
                        $isFallbackFind = true;
                    } catch (\Exception $e) {
                        Log::error($e->getMessage() . " " . $e->getLine());
                    }
                }
            }

            if (!$isFallbackFind)
                $this->reply("Ошибка обработки данных!");
        }


    }

}
