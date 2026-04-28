<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessMootaMutationJob;
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
        $directOrderId = is_array($decoded) ? data_get($decoded, 'payment_detail.order_id') : null;
        $directTrxId = is_array($decoded) ? data_get($decoded, 'payment_detail.trx_id') : null;
        $listOrderIds = is_array($decoded) ? data_get($decoded, '*.payment_detail.order_id') : null;
        $listTrxIds = is_array($decoded) ? data_get($decoded, '*.payment_detail.trx_id') : null;
        Log::info('moota.webhook.reference_probe', [
            'direct_order_id' => $directOrderId,
            'direct_trx_id' => $directTrxId,
            'list_order_ids' => is_array($listOrderIds) ? array_values(array_filter($listOrderIds, fn ($v) => is_scalar($v) && (string) $v !== '')) : [],
            'list_trx_ids' => is_array($listTrxIds) ? array_values(array_filter($listTrxIds, fn ($v) => is_scalar($v) && (string) $v !== '')) : [],
        ]);
        $mutations = $service->normalizePayload($decoded);
        if ($mutations === []) {
            Log::warning('moota.webhook.empty_or_unknown_payload', [
                'payload_preview' => mb_substr($payload, 0, 500),
            ]);
        }

        foreach ($mutations as $mutation) {
            ProcessMootaMutationJob::dispatch($mutation);
        }

        return response('OK', 200);
    }
}
