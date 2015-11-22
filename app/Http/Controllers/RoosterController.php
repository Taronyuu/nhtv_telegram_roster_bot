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
    private $api = "http://roster.nhtv.nl/api/roster?";
    private $keyboard = [
        ['/now', '/today'],
        ['/tomorrow', '/week'],
        ['/nextweek', '/contact']
    ];

    public function __construct()
    {
        $this->telegram = new Api(env('TELEGRAM_BOT_TOKEN'));
        $this->response = $this->telegram->getWebhookUpdates();
//        $this->response = json_decode('{"update_id":422291758,"message":{"message_id":27,"from":{"id":15775927,"first_name":"Zander","last_name":"van der Meer"},"chat":{"id":15775927,"first_name":"Zander","last_name":"van der Meer","type":"private"},"date":1448139513,"text":"\/test"}}', true);
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
        }elseif($command[0] == '/class') {
            $result = $this->setClass($command[1]);
        }elseif($command[0] == '/now'){
            $result = $this->getNow();
        }elseif($command[0] == '/today'){
            $result = $this->getToday();
        }elseif($command[0] == '/tomorrow'){
            $result = $this->getTomorrow();
        }elseif($command[0] == '/week'){
            $result = $this->getWeekly();
        }elseif($command[0] == '/nextweek'){
            $result = $this->getNextWeek();
        }elseif($command[0] == '/contact'){
            $result = $this->getContact();
        }

        if(!$result){
            $result = $this->invalidRequest();
        }

//        $start = Carbon::now()->addDays(3)->startOfDay()->timestamp;
//        $end = Carbon::now()->addDays(3)->endOfDay()->timestamp;
//        $user = User::find(1);
//        $result = $this->sendMessage($user, $start, $end);
        $reply_markup = $this->telegram->replyKeyboardMarkup($this->keyboard, true, false);
        $this->telegram->sendMessage($this->chatId, $result, false, null, $reply_markup);
    }

    public function sendMessage($user, $start, $end)
    {
        $query = http_build_query([
            'classes[]' => $user->class,
            'start'     => $start,
            'end'       => $end
        ]);
        $result = json_decode(file_get_contents($this->api . $query));
        $previousDay = 0;
        $message = "";
        foreach($result->data as $course){
            if($previousDay != Carbon::createFromTimestamp($course->start)->format('Y-m-d')) {
                if($previousDay){
                    $message .= "\n";
                }
                $previousDay = Carbon::createFromTimestamp($course->start)->format('Y-m-d');
                $message .= Carbon::createFromTimestamp($course->start)->format('l d F (Y-m-d)') . "\n";
            }
            $start = Carbon::createFromTimestamp($course->start)->format('H:i');
            $end = Carbon::createFromTimestamp($course->end)->format('H:i');
            $message .= $start . '-' . $end . ' ' . $course->course . ' (' . $course->location . ")\n";
        }

        if(!$message){
            return "There are no activities inside this time period.";
        }
        return $message;
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

    public function getNow()
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
        $result = json_decode(file_get_contents($this->api . $query));
        $now = Carbon::now()->timestamp;
        $message = false;
        foreach($result->data as $course){
            if($course->start > $now && $course->end < $now) {
                $message = "Current activity: " . $start . '-' . $end . ' ' . $course->course . ' (' . $course->location . ")\n";
            }
        }
        if(!$message){
            $message = "You don't have any activities planned right now.";
        }
        return $message;
    }

    public function getToday()
    {
        $user = User::where('chat_id', $this->chatId)->first();
        if(!$user){
            return "You probably forgot to set your class. Please use /class <classname> to set your class.";
        }

        $start = Carbon::now()->startOfDay()->timestamp;
        $end = Carbon::now()->endOfDay()->timestamp;
        return $this->sendMessage($user, $start, $end);
    }

    public function getTomorrow()
    {
        $user = User::where('chat_id', $this->chatId)->first();
        if(!$user){
            return "You probably forgot to set your class. Please use /class <classname> to set your class.";
        }

        $start = Carbon::now()->tomorrow()->startOfDay()->timestamp;
        $end = Carbon::now()->tomorrow()->endOfDay()->timestamp;
        return $this->sendMessage($user, $start, $end);
    }

    public function getWeekly()
    {
        $user = User::where('chat_id', $this->chatId)->first();
        if(!$user){
            return "You probably forgot to set your class. Please use /class <classname> to set your class.";
        }

        $start = Carbon::now()->startOfWeek()->timestamp;
        $end = Carbon::now()->endOfWeek()->timestamp;
        return $this->sendMessage($user, $start, $end);
    }

    public function getNextWeek()
    {
        $user = User::where('chat_id', $this->chatId)->first();
        if(!$user){
            return "You probably forgot to set your class. Please use /class <classname> to set your class.";
        }

        $start = Carbon::now()->addDays(7)->startOfWeek()->timestamp;
        $end = Carbon::now()->addDays(7)->endOfWeek()->timestamp;
        return $this->sendMessage($user, $start, $end);
    }

    public function getContact()
    {
        return "Hi there!\n\nIf you have any questions, suggestions or anything else that I should know you can contact me by clicking here: @Zandervdm\n\nDon't worry, I won't bite. :)";
    }

    public function invalidRequest()
    {
        return "Whoops, something went wrong. Are you sure this is the right command? If so, you can contact me. ";
    }
}