<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

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

    /**
     * Get spreadsheet metadata to fetch sheet names (rounds)
     * Set $useCache to false to bypass cache (for manual sync)
     */
    public function getSpreadsheetMetadata(string $spreadsheetId, bool $useCache = true): array
    {
        $cacheKey = "sheets_metadata_{$spreadsheetId}";
        
        if (!$useCache) {
            Cache::forget($cacheKey);
        }
        
        return Cache::remember($cacheKey, 3600, function () use ($spreadsheetId) {
            try {
                if (empty($this->apiKey)) {
                    throw new \Exception('Google Sheets API Key is not configured');
                }

                // Build URL with API key as query parameter
                $url = "{$this->baseUrl}/spreadsheets/{$spreadsheetId}?key=" . urlencode($this->apiKey);
                
                Log::info('Fetching Google Sheets metadata', [
                    'url_base' => "{$this->baseUrl}/spreadsheets/{$spreadsheetId}",
                    'has_api_key' => !empty($this->apiKey),
                    'api_key_length' => strlen($this->apiKey),
                    'api_key_prefix' => substr($this->apiKey, 0, 10) . '...',
                    'spreadsheet_id' => $spreadsheetId,
                ]);

                $response = Http::timeout(30)->get($url);

                if (!$response->successful()) {
                    $error = $response->json();
                    $statusCode = $response->status();
                    $responseBody = $response->body();
                    
                    Log::error('Google Sheets API Error', [
                        'status_code' => $statusCode,
                        'error' => $error,
                        'response_body' => $responseBody,
                        'api_key_present' => !empty($this->apiKey),
                        'spreadsheet_id' => $spreadsheetId,
                    ]);
                    
                    $errorMessage = 'Unknown error';
                    if (isset($error['error'])) {
                        if (is_array($error['error'])) {
                            $errorMessage = $error['error']['message'] ?? ($error['error']['status'] ?? 'Unknown error');
                        } else {
                            $errorMessage = $error['error'];
                        }
                    } elseif (is_string($error)) {
                        $errorMessage = $error;
                    }
                    
                    // Provide more helpful error messages
                    if (str_contains($errorMessage, 'unregistered callers') || str_contains($errorMessage, 'API key')) {
                        $errorMessage .= '. Pastikan API key valid dan Google Sheets API sudah diaktifkan di Google Cloud Console.';
                    } elseif (str_contains($errorMessage, 'not found') || str_contains($errorMessage, 'permission')) {
                        $errorMessage .= '. Pastikan spreadsheet ID benar dan spreadsheet sudah di-share sebagai "Anyone with the link can view".';
                    }
                    
                    throw new \Exception($errorMessage);
                }

                $data = $response->json();
                
                // Extract sheet names
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
                Log::error('Google Sheets Service Error: ' . $e->getMessage());
                return [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'sheets' => [],
                ];
            }
        });
    }

    /**
     * Get data from a specific sheet
     * Set $useCache to false to bypass cache (for manual sync)
     */
    public function getSheetData(string $spreadsheetId, string $sheetName, string $range = null, bool $useCache = true): array
    {
        $range = $range ?: $sheetName;
        $cacheKey = "sheets_data_{$spreadsheetId}_{$sheetName}_{$range}";
        
        if (!$useCache) {
            Cache::forget($cacheKey);
        }
        
        return Cache::remember($cacheKey, 300, function () use ($spreadsheetId, $range) {
            try {
                if (empty($this->apiKey)) {
                    throw new \Exception('Google Sheets API Key is not configured');
                }

                // Build URL with API key as query parameter
                $encodedRange = urlencode($range);
                $url = "{$this->baseUrl}/spreadsheets/{$spreadsheetId}/values/{$encodedRange}?key=" . urlencode($this->apiKey);
                
                $response = Http::timeout(30)->get($url);

                if (!$response->successful()) {
                    $error = $response->json();
                    Log::error('Google Sheets API Error: ' . json_encode($error));
                    throw new \Exception('Failed to fetch sheet data: ' . ($error['error']['message'] ?? 'Unknown error'));
                }

                $data = $response->json();
                
                return [
                    'success' => true,
                    'values' => $data['values'] ?? [],
                    'range' => $data['range'] ?? $range,
                ];
            } catch (\Exception $e) {
                Log::error('Google Sheets Service Error: ' . $e->getMessage());
                return [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'values' => [],
                ];
            }
        });
    }

    /**
     * Clear cache for a specific spreadsheet
     */
    public function clearCache(string $spreadsheetId, string $sheetName = null): void
    {
        if ($sheetName) {
            // Clear all ranges for this sheet
            $pattern = "sheets_data_{$spreadsheetId}_{$sheetName}_*";
            // Note: Laravel cache doesn't support wildcard, so we'll clear metadata and let it expire naturally
            Cache::forget("sheets_metadata_{$spreadsheetId}");
        } else {
            Cache::forget("sheets_metadata_{$spreadsheetId}");
            // Clear all data caches for this spreadsheet (approximate)
            Cache::forget("sheets_metadata_{$spreadsheetId}");
        }
    }

    /**
     * Clear all cache for a spreadsheet (all sheets)
     */
    public function clearAllCache(string $spreadsheetId): void
    {
        Cache::forget("sheets_metadata_{$spreadsheetId}");
    }

    /**
     * Validate spreadsheet ID format
     */
    public function isValidSpreadsheetId(string $spreadsheetId): bool
    {
        // Google Sheets ID is typically 44 characters alphanumeric
        return preg_match('/^[a-zA-Z0-9_-]{44}$/', $spreadsheetId);
    }
}

