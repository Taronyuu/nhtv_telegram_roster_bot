<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\User;
use Carbon\Carbon;
use Telegram\Bot\Api;

class RoosterController extends Controller
{

    private $telegram;
    private $chatId;
    private $command;
    private $response;

    private $keyboard = [
        ['/today', '/tomorrow'],
        ['/weekly', '/nextweek']
    ];

    public function __construct()
    {
        $this->telegram = new Api(env('TELEGRAM_BOT_TOKEN'));
        $this->response = $this->telegram->getWebhookUpdates();
        $this->chatId   = $this->response['message']['chat']['id'];
        $this->command  = $this->response['message']['text'];
    }

    public function message()
    {
        $result = false;
        $command = (explode(" ", $this->command, 2));
        $command[0] = stripslashes($command[0]);
//        $this->telegram->sendMessage($this->chatId, $command);
//        return 'ok';
        if($command[0] == '/start'){
            $result = $this->getStarted();
        }elseif($command[0] == '/class'){
            $result = $this->setClass($command[1]);
        }elseif($command[0] == '/today'){
            $result = $this->getToday();
        }elseif($command[0] == '/tomorrow'){
            $result = $this->getTomorrow();
        }elseif($command[0] == '/week'){
            $result = $this->getWeekly();
        }elseif($command[0] == '/nextweek'){
            $result = $this->getNextWeek();
        }

        if(!$result){
            $result = $this->invalidRequest();
        }

        $reply_markup = $this->telegram->replyKeyboardMarkup($this->keyboard, true, true);
        $this->telegram->sendMessage($this->chatId, $result, false, null, $reply_markup);
    }

    public function getStarted()
    {
        return  "Hello there!\n\nPlease enter your class using /class to get started.\n\nExample: /class 1 A- 1 ISD\n\n(Due to the implementation of your college's api you must include spaces)";
    }

    public function setClass($arg = 0)
    {
        if(!$arg){
            return false;
        }

        $data = [
            'chat_id' => $this->chatId,
            'class'   => $arg
        ];

        $user = User::where('chat_id', $this->chatId)->first();
        if($user){
            $user->update($data);
        }else {
            User::create($data);
        }

        return "Class has been succesfully saved.\n\nRemember: If you can't find any rosters use the /class <classname> again to set the right class.";
    }

    public function getToday()
    {
        $user = User::where('chat_id', $this->chatId)->first();
        if(!$user){
            return "You probably forgot to set your class. Please use /class <classname> to set your class.";
        }

        $start = Carbon::now()->startOfDay()->timestamp;
        $end = Carbon::now()->endOfDay()->timestamp;
        $query = http_build_query([
            'classes[]' => $user->class,
            'start'     => $start,
            'end'       => $end
        ]);
        $result = file_get_contents('http://roster.nhtv.nl/api/roster?' . $query);
        return $result;
    }

    public function getTomorrow()
    {
        $user = User::where('chat_id', $this->chatId)->first();
        if(!$user){
            return "You probably forgot to set your class. Please use /class <classname> to set your class.";
        }

        $start = Carbon::now()->tomorrow()->startOfDay()->timestamp;
        $end = Carbon::now()->tomorrow()->endOfDay()->timestamp;
        $query = http_build_query([
            'classes[]' => $user->class,
            'start'     => $start,
            'end'       => $end
        ]);
        $result = file_get_contents('http://roster.nhtv.nl/api/roster?' . $query);
        return $result;
    }

    public function getWeekly()
    {
        $user = User::where('chat_id', $this->chatId)->first();
        if(!$user){
            return "You probably forgot to set your class. Please use /class <classname> to set your class.";
        }

        $start = Carbon::now()->startOfWeek()->timestamp;
        $end = Carbon::now()->endOfWeek()->timestamp;
        $query = http_build_query([
            'classes[]' => $user->class,
            'start'     => $start,
            'end'       => $end
        ]);
        $result = file_get_contents('http://roster.nhtv.nl/api/roster?' . $query);
        return $result;
    }

    public function getNextWeek()
    {
        $user = User::where('chat_id', $this->chatId)->first();
        if(!$user){
            return "You probably forgot to set your class. Please use /class <classname> to set your class.";
        }

        $start = Carbon::now()->addDays(7)->startOfWeek()->timestamp;
        $end = Carbon::now()->addDays(7)->endOfWeek()->timestamp;
        $query = http_build_query([
            'classes[]' => $user->class,
            'start'     => $start,
            'end'       => $end
        ]);
        $result = file_get_contents('http://roster.nhtv.nl/api/roster?' . $query);
        return $result;
    }

    public function invalidRequest()
    {
        return "Whoops, something went wrong. Please try again. If it stil fails please contact me.";
    }
}