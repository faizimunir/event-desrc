<?php

namespace App\Http\Controllers;

use App\Services\MootaWebhookService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class MootaWebhookController extends Controller
{
    public function handle(Request $request, MootaWebhookService $service): Response
    {
        $secret = trim((string) config('services.moota.webhook_secret', ''));
        if ($secret === '') {
            Log::warning('moota.webhook.missing_secret', [
                'hint' => 'Set MOOTA_WEBHOOK_SECRET in .env (same as secret_token when creating the webhook in Moota), then run php artisan config:clear or php artisan config:cache.',
            ]);

            return response()->json([
                'error' => 'moota_webhook_secret_missing',
                'message' => 'MOOTA_WEBHOOK_SECRET is not set or empty. Add it to .env and refresh config cache.',
            ], 503);
        }

        $payload = $request->getContent();
        $signature = $request->header('Signature') ?? $request->header('signature');

        if (! $service->verifySignature($signature, $payload, $secret)) {
            Log::warning('moota.webhook.invalid_signature');

            return response('Unauthorized', 401);
        }

        $decoded = json_decode($payload, true);
        $mutations = $service->normalizePayload($decoded);

        foreach ($mutations as $mutation) {
            try {
                $service->processMutation($mutation);
            } catch (\Throwable $e) {
                Log::error('moota.webhook.process_error', [
                    'message' => $e->getMessage(),
                    'mutation_id' => $mutation['mutation_id'] ?? null,
                ]);

                return response('Internal error', 500);
            }
        }

        return response('OK', 200);
    }
}
