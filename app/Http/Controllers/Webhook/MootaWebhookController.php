<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessMootaWebhook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class MootaWebhookController extends Controller
{
    /**
     * Handle incoming webhook from Moota
     * 
     * Contoh payload dari Moota:
     * {
     *   "id": "mutation_id_123",
     *   "bank_id": "bank_id_456",
     *   "account_number": "1234567890",
     *   "bank_type": "bca",
     *   "date": "2024-01-15 10:30:00",
     *   "amount": 150000,
     *   "type": "credit",
     *   "note": "TRANSFER REG-20240115-ABC123",
     *   "description": "Transfer masuk",
     *   "balance": 5000000
     * }
     */
    public function handle(Request $request)
    {
        try {
            $payload = $request->all();
            
            // Moota mengirim payload sebagai array dengan satu elemen
            // Normalize payload: jika array, ambil elemen pertama
            if (is_array($payload) && isset($payload[0]) && is_array($payload[0])) {
                $payload = $payload[0];
            }

            // Handle test request from Moota (when checking URL, Moota sends empty/minimal payload)
            // Jika payload kosong atau tidak memiliki mutation_id, treat sebagai test request
            if (empty($payload) || !isset($payload['mutation_id'])) {
                Log::info('Moota Webhook: Test/Check request received', [
                    'payload' => $request->all(),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Webhook endpoint is active and ready to receive mutations',
                ], 200);
            }

            // Normalize data dari struktur Moota
            $mutationData = $this->normalizeMootaPayload($payload);

            // Validasi data yang sudah dinormalisasi
            $validator = Validator::make($mutationData, [
                'id' => 'required|string',
                'bank_id' => 'required|string',
                'account_number' => 'required|string',
                'amount' => 'required|numeric',
                'type' => 'required|in:credit,debit',
                'date' => 'required|date',
            ]);

            if ($validator->fails()) {
                Log::warning('Moota Webhook: Invalid request', [
                    'errors' => $validator->errors(),
                    'original_payload' => $request->all(),
                    'normalized_data' => $mutationData,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid request data',
                    'errors' => $validator->errors(),
                ], 400);
            }

            // Hanya proses transaksi masuk (credit)
            if ($mutationData['type'] !== 'credit') {
                Log::info('Moota Webhook: Ignoring debit transaction', [
                    'mutation_id' => $mutationData['id'],
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Debit transaction ignored',
                ], 200);
            }

            // Dispatch job untuk memproses webhook di queue
            // Ini memastikan server tidak terbebani dan semua data terrekam
            ProcessMootaWebhook::dispatch($mutationData);

            Log::info('Moota Webhook: Received and queued', [
                'mutation_id' => $mutationData['id'],
                'amount' => $mutationData['amount'],
                'account_number' => $mutationData['account_number'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Webhook received and queued for processing',
            ], 200);

        } catch (\Exception $e) {
            Log::error('Moota Webhook: Exception occurred', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal server error',
            ], 500);
        }
    }

    /**
     * Normalize Moota payload ke format standar
     * 
     * Moota mengirim payload dengan struktur yang berbeda:
     * - mutation_id (bukan id)
     * - type: "CR" atau "DB" (bukan "credit" atau "debit")
     * - note bisa null
     * - description bisa null
     */
    private function normalizeMootaPayload(array $payload): array
    {
        // Normalize type: "CR" -> "credit", "DB" -> "debit"
        $type = strtoupper($payload['type'] ?? '');
        $normalizedType = ($type === 'CR' || $type === 'CREDIT') ? 'credit' : 'debit';

        // Extract mutation_id atau id
        $mutationId = $payload['mutation_id'] ?? $payload['id'] ?? $payload['token'] ?? null;
        
        // Extract bank_id
        $bankId = $payload['bank_id'] ?? $payload['bank']['bank_id'] ?? $payload['account']['account_id'] ?? null;

        // Extract account_number
        $accountNumber = $payload['account_number'] ?? $payload['bank']['account_number'] ?? $payload['account']['account_number'] ?? null;

        // Extract amount
        $amount = $payload['amount'] ?? $payload['payment_detail']['total'] ?? 0;

        // Extract date
        $date = $payload['date'] ?? $payload['created_at'] ?? now()->toDateTimeString();

        // Extract note/description
        $note = $payload['note'] ?? $payload['description'] ?? $payload['payment_detail']['unique_note'] ?? null;

        return [
            'id' => $mutationId,
            'mutation_id' => $mutationId,
            'bank_id' => $bankId,
            'account_number' => $accountNumber,
            'amount' => (float) $amount,
            'type' => $normalizedType,
            'date' => $date,
            'note' => $note,
            'description' => $payload['description'] ?? null,
            'balance' => $payload['balance'] ?? 0,
            'bank_type' => $payload['bank_type'] ?? $payload['bank']['bank_type'] ?? null,
            // Keep original payload for reference
            'original_payload' => $payload,
        ];
    }
}
