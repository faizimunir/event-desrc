<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\LiveResultCategory;
use App\Services\GoogleSheetsService;
use Illuminate\Http\Request;

class LiveResultController extends Controller
{
    protected $googleSheetsService;

    public function __construct(GoogleSheetsService $googleSheetsService)
    {
        $this->googleSheetsService = $googleSheetsService;
    }

    /**
     * Display a listing of events for live result.
     */
    public function index()
    {
        $events = Event::where('status', 'published')
            ->orderBy('start_date', 'desc')
            ->get()
            ->map(function ($event) {
                // Generate slug if not exists
                if (empty($event->slug)) {
                    $event->generateSlug();
                    $event->saveQuietly(); // Save without triggering events
                }
                return $event;
            })
            ->filter(function ($event) {
                // Only return events with slug
                return !empty($event->slug);
            })
            ->values(); // Re-index the collection

        return view('live-result.index', compact('events'));
    }

    /**
     * Display the specified event live result.
     */
    public function show(Request $request, $slug)
    {
        $event = Event::where('slug', $slug)
            ->where('status', 'published')
            ->firstOrFail();

        // Get categories for this event (only those with selected_sheets)
        $categories = LiveResultCategory::where('event_id', $event->id)
            ->where('is_active', true)
            ->whereNotNull('selected_sheets')
            ->whereJsonLength('selected_sheets', '>', 0)
            ->orderBy('order')
            ->orderBy('title')
            ->get();

        // Get selected category and round
        $selectedCategoryId = $request->get('category');
        $selectedRound = $request->get('round');
        
        $selectedCategory = null;
        $sheetData = null;

        if ($selectedCategoryId) {
            $selectedCategory = $categories->find($selectedCategoryId);
            
            if ($selectedCategory && $selectedRound) {
                // Fetch data from Google Sheets
                $result = $this->googleSheetsService->getSheetData(
                    $selectedCategory->spreadsheet_id,
                    $selectedRound
                );
                
                if ($result['success']) {
                    $rawData = $result['values'];
                    
                    // Also fetch B1 separately to ensure we get it (keterangan)
                    // Range must include sheet name: SheetName!B1
                    // If sheet name has spaces or special characters, wrap in single quotes
                    $b1Range = $selectedRound . '!B1';
                    // Handle sheet names with spaces or special characters (except underscore)
                    if (preg_match('/[^a-zA-Z0-9_]/', $selectedRound)) {
                        // Escape single quotes in sheet name by doubling them, then wrap in quotes
                        $escapedSheetName = str_replace("'", "''", $selectedRound);
                        $b1Range = "'" . $escapedSheetName . "'!B1";
                    }
                    
                    $b1Result = $this->googleSheetsService->getSheetData(
                        $selectedCategory->spreadsheet_id,
                        $selectedRound,
                        $b1Range,
                        false // Don't use cache to ensure fresh data for each sheet
                    );
                    
                    $b1Value = '';
                    if ($b1Result['success'] && !empty($b1Result['values']) && isset($b1Result['values'][0][0])) {
                        $b1Value = trim($b1Result['values'][0][0]);
                    }
                    
                    // Log for debugging (can be removed later)
                    \Log::info('B1 Fetch for Round', [
                        'round' => $selectedRound,
                        'range' => $b1Range,
                        'success' => $b1Result['success'],
                        'has_value' => !empty($b1Value),
                        'value_preview' => substr($b1Value, 0, 50),
                    ]);
                    
                    // Parse and group data
                    $parsedData = $this->parseSheetData($rawData, $selectedCategory->spreadsheet_id, $selectedRound, $b1Value);
                    $sheetData = $parsedData;
                }
            }
        }

        return view('live-result.show', compact('event', 'categories', 'selectedCategory', 'selectedRound', 'sheetData'));
    }

    /**
     * Parse sheet data with grouping and column mapping
     */
    protected function parseSheetData(array $rawData, string $spreadsheetId, string $sheetName, string $b1Value = ''): array
    {
        if (empty($rawData)) {
            return [
                'keterangan' => '',
                'groups' => [],
                'columns' => [],
            ];
        }

        // Get keterangan from B1
        // Priority: Use explicitly fetched B1 value, otherwise try from rawData
        $keterangan = '';
        
        if (!empty($b1Value)) {
            $keterangan = $b1Value;
        } elseif (isset($rawData[0]) && is_array($rawData[0])) {
            // B1 = Row 1 (index 0), Column B (index 1)
            // Note: Column B is index 1 (A=0, B=1, C=2, etc.)
            if (isset($rawData[0][1])) {
                $value = trim($rawData[0][1] ?? '');
                if (!empty($value)) {
                    $keterangan = $value;
                }
            }
        }

        // Find header row (usually first row with data)
        $headerRowIndex = 0;
        $headers = [];
        
        // Try to find header row (look for common column names)
        // Skip row 1 (index 0) as it might be title, start from row 2
        for ($i = 1; $i < min(10, count($rawData)); $i++) {
            if (isset($rawData[$i]) && is_array($rawData[$i])) {
                $row = array_map(function($cell) {
                    return strtolower(trim($cell ?? ''));
                }, $rawData[$i]);
                // Check if this row contains header keywords
                if (in_array('plate', $row) || in_array('nama', $row) || in_array('rank', $row) || in_array('poin', $row)) {
                    $headerRowIndex = $i;
                    $headers = $rawData[$i];
                    break;
                }
            }
        }

        // If no header found, use first row
        if (empty($headers) && isset($rawData[0])) {
            $headers = $rawData[0];
            $headerRowIndex = 0;
        }

        // Normalize headers (trim and lowercase for comparison)
        $normalizedHeaders = [];
        $headerMap = [];
        foreach ($headers as $index => $header) {
            $normalized = strtolower(trim($header));
            $normalizedHeaders[$index] = $normalized;
            $headerMap[$normalized] = $index;
        }

        // Check if sheet is "Qualifikasi" (case insensitive)
        $isQualifikasi = stripos($sheetName, 'qualifikasi') !== false;
        
        // Find column indices
        $colPlate = $this->findColumnIndex($headerMap, ['plate']);
        $colNama = $this->findColumnIndex($headerMap, ['nama', 'name']);
        $colPanggilan = $this->findColumnIndex($headerMap, ['panggilan', 'nickname', 'nick']);
        $colTeam = $this->findColumnIndex($headerMap, ['team', 'tim']);
        
        // Gate columns: For Qualifikasi use Gate Moto 1/2/3, otherwise use Gate
        if ($isQualifikasi) {
            $colGateMoto1 = $this->findColumnIndex($headerMap, ['gate moto 1', 'gate1', 'gate 1']);
            $colGateMoto2 = $this->findColumnIndex($headerMap, ['gate moto 2', 'gate2', 'gate 2']);
            $colGateMoto3 = $this->findColumnIndex($headerMap, ['gate moto 3', 'gate3', 'gate 3']);
            $colGate = null; // Not used for Qualifikasi
        } else {
            $colGate = $this->findColumnIndex($headerMap, ['gate']);
            $colGateMoto1 = null;
            $colGateMoto2 = null;
            $colGateMoto3 = null;
        }
        
        $colPoinMoto1 = $this->findColumnIndex($headerMap, ['poin moto 1', 'poin1', 'points1', 'point moto 1']);
        $colPoinMoto2 = $this->findColumnIndex($headerMap, ['poin moto 2', 'poin2', 'points2', 'point moto 2']);
        $colPoinMoto3 = $this->findColumnIndex($headerMap, ['poin moto 3', 'poin3', 'points3', 'point moto 3']);
        $colTotal = $this->findColumnIndex($headerMap, ['poin', 'total', 'total poin', 'points']);
        $colRank = $this->findColumnIndex($headerMap, ['rank', 'peringkat']);
        $colKet = $this->findColumnIndex($headerMap, ['ket', 'keterangan', 'note', 'notes']);

        // Determine which columns exist
        $hasGateMoto3 = $colGateMoto3 !== null;
        $hasPoinMoto1 = $colPoinMoto1 !== null;
        $hasPoinMoto2 = $colPoinMoto2 !== null;
        $hasPoinMoto3 = $colPoinMoto3 !== null;
        $hasGate = $colGate !== null;

        // Group data by empty rows
        // Each group has its own header row, so we need to skip header after each empty row
        $groups = [];
        $currentGroup = [];
        $currentGroupName = ''; // Store group name from column A
        
        // Get first group name from column A
        // Check header row first
        if (isset($rawData[$headerRowIndex]) && is_array($rawData[$headerRowIndex]) && isset($rawData[$headerRowIndex][0])) {
            $headerColA = trim($rawData[$headerRowIndex][0] ?? '');
            // Only use if it's not a header keyword (like "Plate", "Nama", etc.)
            $headerKeywords = ['plate', 'nama', 'rank', 'poin', 'gate', 'team', 'panggilan', 'ket', 'keterangan', 'total'];
            if (!empty($headerColA) && !in_array(strtolower($headerColA), $headerKeywords)) {
                $currentGroupName = $headerColA;
            }
        }
        
        // If not found in header row, check row before header (if exists)
        if (empty($currentGroupName) && $headerRowIndex > 0 && isset($rawData[$headerRowIndex - 1]) && is_array($rawData[$headerRowIndex - 1]) && isset($rawData[$headerRowIndex - 1][0])) {
            $prevRowColA = trim($rawData[$headerRowIndex - 1][0] ?? '');
            if (!empty($prevRowColA)) {
                $currentGroupName = $prevRowColA;
            }
        }
        
        $groupNumber = 1;
        $skipNextRow = false; // Flag to skip header row after empty row

        // Start from row after first header
        for ($i = $headerRowIndex + 1; $i < count($rawData); $i++) {
            $row = $rawData[$i];
            
            // Check if row is empty (all cells are empty or whitespace)
            $isEmpty = true;
            if (is_array($row)) {
                foreach ($row as $cell) {
                    if (!empty(trim($cell ?? ''))) {
                        $isEmpty = false;
                        break;
                    }
                }
            } else {
                $isEmpty = true;
            }

            if ($isEmpty) {
                // Empty row = end of current group
                if (!empty($currentGroup)) {
                    // Sort data in group before adding
                    $currentGroup = $this->sortGroupData($currentGroup, $isQualifikasi);
                    // Use group name from column A, or fallback to Batch X
                    $groupName = !empty($currentGroupName) ? trim($currentGroupName) : 'Batch ' . $groupNumber;
                    $groups[] = [
                        'name' => $groupName,
                        'data' => $currentGroup,
                    ];
                    $currentGroup = [];
                    $currentGroupName = '';
                    $groupNumber++;
                }
                // Set flag to skip next row (which will be header of next group)
                $skipNextRow = true;
            } else {
                // Non-empty row
                if ($skipNextRow) {
                    // This is a header row of new group
                    // Get group name from column A (index 0) of this header row
                    if (is_array($row) && isset($row[0])) {
                        $currentGroupName = trim($row[0] ?? '');
                    }
                    $skipNextRow = false;
                    continue;
                }
                
                // Ensure row is an array
                if (!is_array($row)) {
                    continue;
                }
                
                // Check if this row looks like a header (contains header keywords)
                $rowLower = array_map(function($cell) {
                    return strtolower(trim($cell ?? ''));
                }, $row);
                
                $isHeaderRow = in_array('plate', $rowLower) || 
                               in_array('nama', $rowLower) || 
                               in_array('rank', $rowLower) ||
                               in_array('poin', $rowLower);
                
                if ($isHeaderRow) {
                    // Skip header rows
                    continue;
                }
                
                $rowData = $this->mapRowData($row, [
                    'plate' => $colPlate,
                    'nama' => $colNama,
                    'panggilan' => $colPanggilan,
                    'team' => $colTeam,
                    'gate' => $colGate,
                    'gate_moto_1' => $colGateMoto1,
                    'gate_moto_2' => $colGateMoto2,
                    'gate_moto_3' => $colGateMoto3,
                    'poin_moto_1' => $colPoinMoto1,
                    'poin_moto_2' => $colPoinMoto2,
                    'poin_moto_3' => $colPoinMoto3,
                    'total' => $colTotal,
                    'rank' => $colRank,
                    'ket' => $colKet,
                ]);

                // Only add row if it has at least plate or nama
                if (!empty($rowData['plate']) || !empty($rowData['nama'])) {
                    $currentGroup[] = $rowData;
                }
            }
        }

        // Add last group if exists
        if (!empty($currentGroup)) {
            // Sort data in group
            $currentGroup = $this->sortGroupData($currentGroup, $isQualifikasi);
            // Use group name from column A, or fallback to Batch X
            $groupName = !empty($currentGroupName) ? trim($currentGroupName) : 'Batch ' . $groupNumber;
            $groups[] = [
                'name' => $groupName,
                'data' => $currentGroup,
            ];
        }

        return [
            'keterangan' => $keterangan,
            'groups' => $groups,
            'is_qualifikasi' => $isQualifikasi,
            'columns' => [
                'has_gate' => $hasGate,
                'has_gate_moto_3' => $hasGateMoto3,
                'has_poin_moto_1' => $hasPoinMoto1,
                'has_poin_moto_2' => $hasPoinMoto2,
                'has_poin_moto_3' => $hasPoinMoto3,
            ],
        ];
    }

    /**
     * Find column index by matching header names
     */
    protected function findColumnIndex(array $headerMap, array $possibleNames): ?int
    {
        foreach ($possibleNames as $name) {
            if (isset($headerMap[$name])) {
                return $headerMap[$name];
            }
        }
        return null;
    }

    /**
     * Map row data to structured format
     */
    protected function mapRowData(array $row, array $columnMap): array
    {
        $mapped = [];
        foreach ($columnMap as $key => $index) {
            if ($index !== null && isset($row[$index]) && $row[$index] !== null) {
                $value = $row[$index];
                // Handle numeric values
                if (is_numeric($value)) {
                    $mapped[$key] = (string)$value;
                } else {
                    $mapped[$key] = trim((string)$value);
                }
            } else {
                $mapped[$key] = '';
            }
        }
        return $mapped;
    }

    /**
     * Sort group data by Rank (if exists) or Gate/Gate Moto 1
     */
    protected function sortGroupData(array $groupData, bool $isQualifikasi): array
    {
        // Check if any row has rank
        $hasRank = false;
        foreach ($groupData as $row) {
            if (!empty($row['rank']) && is_numeric($row['rank'])) {
                $hasRank = true;
                break;
            }
        }

        if ($hasRank) {
            // Sort by Rank (ascending)
            usort($groupData, function($a, $b) {
                $rankA = !empty($a['rank']) && is_numeric($a['rank']) ? (int)$a['rank'] : 9999;
                $rankB = !empty($b['rank']) && is_numeric($b['rank']) ? (int)$b['rank'] : 9999;
                return $rankA <=> $rankB;
            });
        } else {
            // Sort by Gate or Gate Moto 1
            usort($groupData, function($a, $b) use ($isQualifikasi) {
                if ($isQualifikasi) {
                    $valueA = !empty($a['gate_moto_1']) && is_numeric($a['gate_moto_1']) ? (int)$a['gate_moto_1'] : 9999;
                    $valueB = !empty($b['gate_moto_1']) && is_numeric($b['gate_moto_1']) ? (int)$b['gate_moto_1'] : 9999;
                } else {
                    $valueA = !empty($a['gate']) && is_numeric($a['gate']) ? (int)$a['gate'] : 9999;
                    $valueB = !empty($b['gate']) && is_numeric($b['gate']) ? (int)$b['gate'] : 9999;
                }
                return $valueA <=> $valueB;
            });
        }

        return $groupData;
    }
}
