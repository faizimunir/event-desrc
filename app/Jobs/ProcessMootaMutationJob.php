<?php

namespace App\Jobs;

use App\Services\MootaWebhookService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessMootaMutationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    /** @var array<int, int> */
    public array $backoff = [5, 15, 30, 60];

    public int $timeout = 60;

    /**
     * @param  array<string, mixed>  $mutation
     */
    public function __construct(public array $mutation)
    {
        $this->onConnection((string) config('services.moota.queue_connection', 'redis'));
        $this->onQueue((string) config('services.moota.queue', 'moota'));
    }

    public function handle(MootaWebhookService $service): void
    {
        $service->processMutation($this->mutation);
    }

    public function failed(?\Throwable $e): void
    {
        Log::error('moota.webhook.job_failed', [
            'message' => $e?->getMessage(),
            'mutation_id' => $this->mutation['mutation_id'] ?? null,
            'token' => $this->mutation['token'] ?? null,
        ]);
    }
}
