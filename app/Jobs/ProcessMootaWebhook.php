<?php

namespace App\Jobs;

use App\Models\Payment;
use App\Models\Participant;
use App\Services\MootaService;
use App\Jobs\SendConfirmNotificationJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ProcessMootaWebhook implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public $mutationData;

    /**
     * Create a new job instance.
     */
    public function __construct(array $mutationData)
    {
        $this->mutationData = $mutationData;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $mootaService = new MootaService();
            $verification = $mootaService->verifyPayment($this->mutationData);

            if ($verification && $verification['success']) {
                $payment = $verification['payment'];
                $participant = $verification['participant'];

                // Pastikan payment belum diverifikasi sebelumnya
                if ($payment->status === 'verified') {
                    Log::info('Moota Webhook: Payment already verified', [
                        'payment_id' => $payment->id,
                        'mutation_id' => $verification['mutation_id'],
                    ]);
                    return;
                }

                DB::transaction(function () use ($payment, $participant, $verification) {
                    // Update payment status
                    $payment->update([
                        'status' => 'verified',
                        'payment_date' => $verification['mutation_date'] ? \Carbon\Carbon::parse($verification['mutation_date']) : now(),
                        'payment_verified_at' => now(),
                        'payment_reference' => $verification['mutation_id'],
                        'transaction_id' => $verification['mutation_id'],
                    ]);

                    // Update participant status
                    $participant->update([
                        'status' => 'confirmed',
                    ]);

                    // Dispatch confirmation notification job
                    SendConfirmNotificationJob::dispatch($participant->fresh());
                });

                Log::info('Moota Webhook: Payment verified successfully', [
                    'payment_id' => $payment->id,
                    'participant_id' => $participant->id,
                    'mutation_id' => $verification['mutation_id'],
                    'match_type' => $verification['match_type'],
                ]);
            } else {
                // Log unmatched transaction
                if (config('moota.log_unmatched_transactions', true)) {
                    Log::warning('Moota Webhook: Unmatched transaction', [
                        'amount' => $this->mutationData['amount'] ?? null,
                        'note' => $this->mutationData['note'] ?? $this->mutationData['description'] ?? null,
                        'mutation_id' => $this->mutationData['id'] ?? null,
                        'date' => $this->mutationData['date'] ?? null,
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Moota Webhook Processing Error: ' . $e->getMessage(), [
                'exception' => $e,
                'mutation_data' => $this->mutationData,
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw untuk retry mechanism
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Moota Webhook Job Failed', [
            'exception' => $exception->getMessage(),
            'mutation_data' => $this->mutationData,
        ]);
    }
}
