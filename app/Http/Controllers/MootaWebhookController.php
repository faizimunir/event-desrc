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
        $signature = $request->header('Signature')
            ?? $request->header('signature')
            ?? $request->header('X-Signature')
            ?? $request->header('x-signature');

        if (is_string($signature)) {
            $signature = trim($signature);
            if (str_starts_with(strtolower($signature), 'sha256=')) {
                $signature = substr($signature, 7);
            }
        }

        if (! $service->verifySignature($signature, $payload, $secret)) {
            Log::warning('moota.webhook.invalid_signature', [
                'has_signature' => is_string($signature) && $signature !== '',
                'content_type' => $request->header('Content-Type'),
            ]);

            return response('Unauthorized', 401);
        }

        $decoded = json_decode($payload, true);
        $mutations = $service->normalizePayload($decoded);
        if ($mutations === []) {
            Log::warning('moota.webhook.empty_or_unknown_payload', [
                'payload_preview' => mb_substr($payload, 0, 500),
            ]);
        }

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
