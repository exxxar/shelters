<?php

namespace App\Http\Controllers;

use App\Classes\TelegramBotHandler;
use App\Facades\MilitaryServiceFacade;
use Illuminate\Http\Request;
use Telegram\Bot\Api;

class TelegramController extends Controller
{
    public function handler(Request $request)
    {
        MilitaryServiceFacade::bot()->handler();
    }
}
