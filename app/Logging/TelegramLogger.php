<?php

namespace App\Logging;

use Monolog\Logger;
use Monolog\Handler\TelegramBotHandler;

class TelegramLogger
{
    public function __invoke(array $config)
    {
        $logger = new Logger($config['name'] ?? 'telegram');

        if ($config['type'] === 'info') {
            $botToken = env('TELEGRAM_INFO_BOT_TOKEN');
            $chatId = env('TELEGRAM_INFO_CHAT_ID');
        } elseif ($config['type'] === 'error') {
            $botToken = env('TELEGRAM_ERROR_BOT_TOKEN');
            $chatId = env('TELEGRAM_ERROR_CHAT_ID');
        } else {
            throw new \Exception("Invalid Telegram logger type: " . ($config['type'] ?? 'undefined'));
        }

        if (!$botToken || !$chatId) {
            throw new \Exception("Missing required environment variables for Telegram logger type: " . $config['type']);
        }

        $handler = new TelegramBotHandler($botToken, $chatId);
        $logger->pushHandler($handler);

        return $logger;
    }
}
