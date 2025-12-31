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
            // Handle test request from Moota (when checking URL, Moota sends empty/minimal payload)
            // If request is empty or missing required fields, treat it as a test/check request
            if (empty($request->all()) || !$request->has('id') || !$request->has('bank_id')) {
                Log::info('Moota Webhook: Test/Check request received', [
                    'payload' => $request->all(),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Webhook endpoint is active and ready to receive mutations',
                ], 200);
            }

            // Validasi request untuk real webhook
            $validator = Validator::make($request->all(), [
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
                    'payload' => $request->all(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid request data',
                    'errors' => $validator->errors(),
                ], 400);
            }

            // Hanya proses transaksi masuk (credit)
            if ($request->input('type') !== 'credit') {
                Log::info('Moota Webhook: Ignoring debit transaction', [
                    'mutation_id' => $request->input('id'),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Debit transaction ignored',
                ], 200);
            }

            // Dispatch job untuk memproses webhook di queue
            // Ini memastikan server tidak terbebani dan semua data terrekam
            ProcessMootaWebhook::dispatch($request->all());

            Log::info('Moota Webhook: Received and queued', [
                'mutation_id' => $request->input('id'),
                'amount' => $request->input('amount'),
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
}
