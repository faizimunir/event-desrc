<?php

namespace App\Services;

class LiveResultSheetParser
{
    public static function parse(array $rawData, string $sheetName, string $b1Value = ''): array
    {
        if (empty($rawData)) {
            return [
                'keterangan' => '',
                'groups' => [],
                'columns' => ['has_gate' => false, 'has_gate_moto_3' => false, 'has_poin_moto_1' => false, 'has_poin_moto_2' => false, 'has_poin_moto_3' => false],
                'is_qualifikasi' => false,
            ];
        }

        $keterangan = $b1Value;
        if (empty($keterangan) && isset($rawData[0][1])) {
            $v = trim($rawData[0][1] ?? '');
            if ($v !== '') {
                $keterangan = $v;
            }
        }

        $headerRowIndex = 0;
        $headers = [];
        for ($i = 1; $i < min(10, count($rawData)); $i++) {
            if (isset($rawData[$i]) && is_array($rawData[$i])) {
                $row = array_map(fn ($cell) => strtolower(trim($cell ?? '')), $rawData[$i]);
                if (in_array('plate', $row) || in_array('nama', $row) || in_array('rank', $row) || in_array('poin', $row)) {
                    $headerRowIndex = $i;
                    $headers = $rawData[$i];
                    break;
                }
            }
        }
        if (empty($headers) && isset($rawData[0])) {
            $headers = $rawData[0];
            $headerRowIndex = 0;
        }

        $headerMap = [];
        foreach ($headers as $index => $header) {
            $headerMap[strtolower(trim($header))] = $index;
        }

        $isQualifikasi = stripos($sheetName, 'qualifikasi') !== false;

        $colPlate = self::findCol($headerMap, ['plate']);
        $colNama = self::findCol($headerMap, ['nama', 'name']);
        $colPanggilan = self::findCol($headerMap, ['panggilan', 'nickname', 'nick']);
        $colTeam = self::findCol($headerMap, ['team', 'tim']);
        $colGateMoto1 = self::findCol($headerMap, ['gate moto 1', 'gate1', 'gate 1']);
        $colGateMoto2 = self::findCol($headerMap, ['gate moto 2', 'gate2', 'gate 2']);
        $colGateMoto3 = self::findCol($headerMap, ['gate moto 3', 'gate3', 'gate 3']);
        $colGate = self::findCol($headerMap, ['gate']);
        $colPoinMoto1 = self::findCol($headerMap, ['poin moto 1', 'poin1', 'points1', 'point moto 1']);
        $colPoinMoto2 = self::findCol($headerMap, ['poin moto 2', 'poin2', 'points2', 'point moto 2']);
        $colPoinMoto3 = self::findCol($headerMap, ['poin moto 3', 'poin3', 'points3', 'point moto 3']);
        $colTotal = self::findCol($headerMap, ['poin', 'total', 'total poin', 'points']);
        $colRank = self::findCol($headerMap, ['rank', 'peringkat']);
        $colKet = self::findCol($headerMap, ['ket', 'keterangan', 'note', 'notes']);

        $columnMap = [
            'plate' => $colPlate, 'nama' => $colNama, 'panggilan' => $colPanggilan, 'team' => $colTeam,
            'gate' => $colGate, 'gate_moto_1' => $colGateMoto1, 'gate_moto_2' => $colGateMoto2, 'gate_moto_3' => $colGateMoto3,
            'poin_moto_1' => $colPoinMoto1, 'poin_moto_2' => $colPoinMoto2, 'poin_moto_3' => $colPoinMoto3,
            'total' => $colTotal, 'rank' => $colRank, 'ket' => $colKet,
        ];

        $groups = [];
        $currentGroup = [];
        $currentGroupName = '';
        $headerKeywords = ['plate', 'nama', 'rank', 'poin', 'gate', 'team', 'panggilan', 'ket', 'keterangan', 'total'];
        if (isset($rawData[$headerRowIndex][0])) {
            $headerColA = trim($rawData[$headerRowIndex][0] ?? '');
            if ($headerColA !== '' && ! in_array(strtolower($headerColA), $headerKeywords)) {
                $currentGroupName = $headerColA;
            }
        }
        if ($currentGroupName === '' && $headerRowIndex > 0 && isset($rawData[$headerRowIndex - 1][0])) {
            $currentGroupName = trim($rawData[$headerRowIndex - 1][0] ?? '');
        }
        $groupNumber = 1;
        $skipNextRow = false;

        for ($i = $headerRowIndex + 1; $i < count($rawData); $i++) {
            $row = $rawData[$i];
            $isEmpty = ! is_array($row) || array_reduce($row, fn ($c, $cell) => $c && trim($cell ?? '') === '', true);
            if ($isEmpty) {
                if ($currentGroup !== []) {
                    $currentGroup = self::sortGroup($currentGroup);
                    $groups[] = ['name' => $currentGroupName !== '' ? trim($currentGroupName) : 'Batch '.$groupNumber, 'data' => $currentGroup];
                    $currentGroup = [];
                    $currentGroupName = '';
                    $groupNumber++;
                }
                $skipNextRow = true;
            } else {
                if ($skipNextRow) {
                    if (is_array($row) && isset($row[0])) {
                        $currentGroupName = trim($row[0] ?? '');
                    }
                    $skipNextRow = false;
                    continue;
                }
                if (! is_array($row)) {
                    continue;
                }
                $rowLower = array_map(fn ($cell) => strtolower(trim($cell ?? '')), $row);
                if (in_array('plate', $rowLower) || in_array('nama', $rowLower) || in_array('rank', $rowLower) || in_array('poin', $rowLower)) {
                    continue;
                }
                $rowData = self::mapRow($row, $columnMap);
                if (($rowData['plate'] ?? '') !== '' || ($rowData['nama'] ?? '') !== '') {
                    $currentGroup[] = $rowData;
                }
            }
        }
        if ($currentGroup !== []) {
            $currentGroup = self::sortGroup($currentGroup);
            $groups[] = ['name' => $currentGroupName !== '' ? trim($currentGroupName) : 'Batch '.$groupNumber, 'data' => $currentGroup];
        }

        return [
            'keterangan' => $keterangan,
            'groups' => $groups,
            'is_qualifikasi' => $isQualifikasi,
            'columns' => [
                'has_gate' => $colGate !== null,
                'has_gate_moto_3' => $colGateMoto3 !== null,
                'has_poin_moto_1' => $colPoinMoto1 !== null,
                'has_poin_moto_2' => $colPoinMoto2 !== null,
                'has_poin_moto_3' => $colPoinMoto3 !== null,
            ],
        ];
    }

    protected static function findCol(array $headerMap, array $names): ?int
    {
        foreach ($names as $name) {
            if (isset($headerMap[$name])) {
                return $headerMap[$name];
            }
        }
        return null;
    }

    protected static function mapRow(array $row, array $columnMap): array
    {
        $mapped = [];
        foreach ($columnMap as $key => $index) {
            if ($index !== null && isset($row[$index]) && $row[$index] !== null) {
                $v = $row[$index];
                $mapped[$key] = is_numeric($v) ? (string) $v : trim((string) $v);
            } else {
                $mapped[$key] = '';
            }
        }
        return $mapped;
    }

    protected static function sortGroup(array $groupData): array
    {
        $hasRank = false;
        foreach ($groupData as $row) {
            if (isset($row['rank']) && $row['rank'] !== '' && is_numeric($row['rank'])) {
                $hasRank = true;
                break;
            }
        }
        if ($hasRank) {
            usort($groupData, fn ($a, $b) => (int) ($a['rank'] ?? 9999) <=> (int) ($b['rank'] ?? 9999));
        } else {
            usort($groupData, function ($a, $b) {
                $vA = isset($a['gate_moto_1']) && $a['gate_moto_1'] !== '' && is_numeric($a['gate_moto_1'])
                    ? (int) $a['gate_moto_1']
                    : (isset($a['gate']) && $a['gate'] !== '' && is_numeric($a['gate']) ? (int) $a['gate'] : 9999);
                $vB = isset($b['gate_moto_1']) && $b['gate_moto_1'] !== '' && is_numeric($b['gate_moto_1'])
                    ? (int) $b['gate_moto_1']
                    : (isset($b['gate']) && $b['gate'] !== '' && is_numeric($b['gate']) ? (int) $b['gate'] : 9999);
                return $vA <=> $vB;
            });
        }
        return $groupData;
    }
}
