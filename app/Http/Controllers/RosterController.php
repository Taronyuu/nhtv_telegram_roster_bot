<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;
use Telegram\Bot\Laravel\Facades\Telegram;

class RosterController extends Controller
{
    public $telegram;
    public $api;
    public $updates;
    public $chatId;
    public $command;

    public function __construct()
    {
        $this->telegram = new Api(env('TELEGRAM_BOT_TOKEN'));
        $this->api = 'https://api.telegram.org/bot' . env('TELEGRAM_BOT_TOKEN');
        $this->updates = $this->telegram->getWebhookUpdates();
        $this->chatId = $this->updates['message']['chat']['id'];
        $this->command = $this->updates['message']['text'];
    }

    public function message()
    {
        if($this->command == '/today'){

        }elseif($this->command == '/weekly'){

        }
        $this->api = $this->api . '/sendmessage?chat_id=' . $this->chatId . '&text=' . $this->updates;
        file_get_contents($this->api);
    }

    public function getToday()
    {

    }
}
