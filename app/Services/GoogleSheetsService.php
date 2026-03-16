<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleSheetsService
{
    protected $apiKey;

    protected $baseUrl = 'https://sheets.googleapis.com/v4';

    public function __construct()
    {
        $this->apiKey = config('services.google_sheets.api_key');

        if (empty($this->apiKey)) {
            Log::warning('Google Sheets API Key is not configured. Please set GOOGLE_SHEETS_API_KEY in .env file.');
        }
    }

    public function getSpreadsheetMetadata(string $spreadsheetId, bool $useCache = true): array
    {
        $cacheKey = "sheets_metadata_{$spreadsheetId}";

        if (! $useCache) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, 3600, function () use ($spreadsheetId) {
            try {
                if (empty($this->apiKey)) {
                    throw new \Exception('Google Sheets API Key is not configured');
                }

                $url = "{$this->baseUrl}/spreadsheets/{$spreadsheetId}?key=".urlencode($this->apiKey);

                $response = Http::timeout(30)->get($url);

                if (! $response->successful()) {
                    $error = $response->json();
                    $errorMessage = $error['error']['message'] ?? 'Unknown error';
                    if (str_contains($errorMessage, 'unregistered callers') || str_contains($errorMessage, 'API key')) {
                        $errorMessage .= '. Pastikan API key valid dan Google Sheets API sudah diaktifkan di Google Cloud Console.';
                    } elseif (str_contains($errorMessage, 'not found') || str_contains($errorMessage, 'permission')) {
                        $errorMessage .= '. Pastikan spreadsheet ID benar dan spreadsheet sudah di-share sebagai "Anyone with the link can view".';
                    }
                    throw new \Exception($errorMessage);
                }

                $data = $response->json();
                $sheets = [];
                if (isset($data['sheets'])) {
                    foreach ($data['sheets'] as $sheet) {
                        $sheets[] = $sheet['properties']['title'];
                    }
                }

                return [
                    'success' => true,
                    'title' => $data['properties']['title'] ?? '',
                    'sheets' => $sheets,
                ];
            } catch (\Exception $e) {
                Log::error('Google Sheets Service Error: '.$e->getMessage());

                return [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'sheets' => [],
                ];
            }
        });
    }

    public function getSheetData(string $spreadsheetId, string $sheetName, ?string $range = null, bool $useCache = true): array
    {
        $range = $range ?: $sheetName;
        $cacheKey = "sheets_data_{$spreadsheetId}_{$sheetName}_{$range}";

        if (! $useCache) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, 300, function () use ($spreadsheetId, $range) {
            try {
                if (empty($this->apiKey)) {
                    throw new \Exception('Google Sheets API Key is not configured');
                }

                $encodedRange = urlencode($range);
                $url = "{$this->baseUrl}/spreadsheets/{$spreadsheetId}/values/{$encodedRange}?key=".urlencode($this->apiKey);

                $response = Http::timeout(30)->get($url);

                if (! $response->successful()) {
                    $error = $response->json();
                    throw new \Exception('Failed to fetch sheet data: '.($error['error']['message'] ?? 'Unknown error'));
                }

                $data = $response->json();

                return [
                    'success' => true,
                    'values' => $data['values'] ?? [],
                    'range' => $data['range'] ?? $range,
                ];
            } catch (\Exception $e) {
                Log::error('Google Sheets Service Error: '.$e->getMessage());

                return [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'values' => [],
                ];
            }
        });
    }

    public function clearAllCache(string $spreadsheetId): void
    {
        Cache::forget("sheets_metadata_{$spreadsheetId}");
    }

    public function isValidSpreadsheetId(string $spreadsheetId): bool
    {
        return preg_match('/^[a-zA-Z0-9_-]{44}$/', $spreadsheetId);
    }
}
