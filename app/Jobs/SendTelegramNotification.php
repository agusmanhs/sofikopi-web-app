<?php

namespace App\Jobs;

use App\Services\TelegramService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendTelegramNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 10;

    public function __construct(
        public string $title,
        public array $details,
        public string $icon = 'ℹ️',
        public ?string $photoPath = null,
        public ?string $chatId = null,
    ) {}

    public function handle(TelegramService $telegramService): void
    {
        $telegramService->sendNotificationNow(
            $this->title, $this->details, $this->icon, $this->photoPath, $this->chatId
        );
    }
}
