<?php

namespace App\Facades;

use App\Classes\TelegramBotHandler;
use Illuminate\Support\Facades\Facade;

/**
 * @method static TelegramBotHandler bot()
 * @see \Illuminate\Log\Logger
 */
class MilitaryServiceFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'military.service';
    }
}

