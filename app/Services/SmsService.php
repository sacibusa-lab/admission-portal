<?php

namespace App\Services;

use App\Jobs\SendSmsJob;

class SmsService
{
    protected TermiiService $termiiService;

    public function __construct(TermiiService $termiiService)
    {
        $this->termiiService = $termiiService;
    }

    /**
     * Send SMS synchronously.
     */
    public function send(string $phone, string $message): array
    {
        return $this->termiiService->send($phone, $message);
    }

    /**
     * Queue SMS for background dispatch.
     */
    public function queue(string $phone, string $message): bool
    {
        SendSmsJob::dispatch($phone, $message);
        return true;
    }
}
