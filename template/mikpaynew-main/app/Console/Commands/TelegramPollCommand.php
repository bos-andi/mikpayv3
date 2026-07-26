<?php

namespace App\Console\Commands;

use App\Services\TelegramBotService;
use Illuminate\Console\Command;

class TelegramPollCommand extends Command
{
    protected $signature = 'mikpay:telegram-poll';

    protected $description = 'Poll Telegram commands for all enabled users';

    public function handle(TelegramBotService $telegramBotService): int
    {
        $telegramBotService->pollAllUsers();
        $telegramBotService->monitorConnectionChangesAllUsers();
        $this->info('Telegram poll selesai.');
        return self::SUCCESS;
    }
}
