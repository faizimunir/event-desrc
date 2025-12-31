<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class MootaService
{
    protected $apiKey;
    protected $apiUrl;

    public function __construct()
    {
        $this->apiKey = config('moota.api_key');
        $this->apiUrl = config('moota.api_url');
    }

    /**
     * Mengambil data mutasi dari API Moota
     *
     * @param string|null $bankId ID bank (opsional, jika null akan mengambil semua)
     * @param int $limit Limit jumlah mutasi
     * @param int $skip Skip jumlah mutasi
     * @return array|null
     */
    public function getMutations($bankId = null, $limit = 10, $skip = 0)
    {
        try {
            $url = $this->apiUrl . '/bank/mutation';
            
            $params = [
                'limit' => $limit,
                'skip' => $skip,
            ];

            if ($bankId) {
                $params['bank_id'] = $bankId;
            }

            $response = Http::withToken($this->apiKey)
                ->timeout(30)
                ->get($url, $params);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Moota API Error: ' . $response->body(), [
                'status' => $response->status(),
                'url' => $url,
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Moota Service Exception: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return null;
        }
    }

    /**
     * Mencocokkan transaksi dengan pembayaran berdasarkan nominal dan kode unik
     *
     * @param float $amount Nominal transaksi
     * @param string|null $note Catatan/deskripsi transaksi
     * @return array|null Array berisi informasi pembayaran yang cocok atau null
     */
    public function matchTransaction($amount, $note = null)
    {
        try {
            // Ambil semua pembayaran yang masih pending
            $payments = \App\Models\Payment::with(['participant.package.event'])
                ->where('status', 'pending')
                ->whereHas('participant', function ($query) {
                    $query->whereHas('package.event', function ($eventQuery) {
                        $eventQuery->where('payment_method', 'moota');
                    });
                })
                ->get();

            $tolerance = config('moota.amount_tolerance', 0);

            foreach ($payments as $payment) {
                $participant = $payment->participant;
                $package = $participant->package;
                $expectedAmount = $package->price + (int)$participant->unique_code;

                // Cek apakah nominal cocok (dengan toleransi)
                if (abs($amount - $expectedAmount) <= $tolerance) {
                    // Jika ada note, cek apakah mengandung kode unik atau nomor registrasi
                    if ($note) {
                        $note = strtoupper($note);
                        $uniqueCode = strtoupper($participant->unique_code);
                        $registrationNumber = strtoupper($participant->registration_number);

                        // Cocokkan berdasarkan kode unik atau nomor registrasi jika diaktifkan
                        $matchByUniqueCode = config('moota.match_by_unique_code', true);
                        $matchByRegistrationNumber = config('moota.match_by_registration_number', true);

                        if ($matchByUniqueCode && strpos($note, $uniqueCode) !== false) {
                            return [
                                'payment' => $payment,
                                'participant' => $participant,
                                'match_type' => 'unique_code',
                            ];
                        }

                        if ($matchByRegistrationNumber && strpos($note, $registrationNumber) !== false) {
                            return [
                                'payment' => $payment,
                                'participant' => $participant,
                                'match_type' => 'registration_number',
                            ];
                        }
                    }

                    // Jika tidak ada note atau tidak cocok dengan note, tetap cocokkan berdasarkan nominal saja
                    // (untuk kasus transfer tanpa catatan)
                    return [
                        'payment' => $payment,
                        'participant' => $participant,
                        'match_type' => 'amount_only',
                    ];
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Moota Match Transaction Exception: ' . $e->getMessage(), [
                'exception' => $e,
                'amount' => $amount,
                'note' => $note,
            ]);

            return null;
        }
    }

    /**
     * Verifikasi pembayaran berdasarkan data mutasi Moota
     *
     * @param array $mutationData Data mutasi dari Moota
     * @return array|null
     */
    public function verifyPayment($mutationData)
    {
        try {
            $amount = (float) ($mutationData['amount'] ?? 0);
            $note = $mutationData['note'] ?? $mutationData['description'] ?? null;
            $mutationId = $mutationData['id'] ?? null;
            $date = $mutationData['date'] ?? null;

            $match = $this->matchTransaction($amount, $note);

            if ($match) {
                return [
                    'success' => true,
                    'payment' => $match['payment'],
                    'participant' => $match['participant'],
                    'match_type' => $match['match_type'],
                    'mutation_id' => $mutationId,
                    'mutation_date' => $date,
                ];
            }

            return [
                'success' => false,
                'message' => 'No matching payment found',
                'amount' => $amount,
                'note' => $note,
            ];
        } catch (\Exception $e) {
            Log::error('Moota Verify Payment Exception: ' . $e->getMessage(), [
                'exception' => $e,
                'mutation_data' => $mutationData,
            ]);

            return null;
        }
    }

    /**
     * Mengambil daftar bank yang terhubung dengan Moota
     *
     * @return array|null
     */
    public function getBanks()
    {
        try {
            $url = $this->apiUrl . '/bank';

            $response = Http::withToken($this->apiKey)
                ->timeout(30)
                ->get($url);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Moota Get Banks Error: ' . $response->body(), [
                'status' => $response->status(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Moota Get Banks Exception: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return null;
        }
    }
}

