<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Telegram\Bot\Laravel\Facades\Telegram;

class enableWebhook extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:enable';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set the webhook for Telegram';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        Telegram::setWebhook('https://api.telegram.org/' . env('TELEGRAM_BOT_TOKEN') . '/setwebhook?url=https://nhtv.snapr.pw/message');
    }
}
