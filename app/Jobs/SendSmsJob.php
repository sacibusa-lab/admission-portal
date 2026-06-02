<?php

namespace App\Jobs;

use App\Services\TermiiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $phone;
    protected string $message;

    /**
     * Create a new job instance.
     */
    public function __construct(string $phone, string $message)
    {
        $this->phone = $phone;
        $this->message = $message;
    }

    /**
     * Execute the job.
     */
    public function handle(TermiiService $termiiService): void
    {
        $termiiService->send($this->phone, $this->message);
    }
}
