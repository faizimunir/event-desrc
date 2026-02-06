<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Preview - {{ $category->title }} - {{ $event->name }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            font-size: 8pt;
            line-height: 1.15;
            color: #000;
            background: #fff;
        }

        /* Print Header - appears on every page */
        .print-header {
            display: table;
            width: 100%;
            margin-bottom: 5px;
            padding-bottom: 3px;
            border-bottom: 1.5px solid #000;
            page-break-inside: avoid;
        }

        .print-header-left {
            display: table-cell;
            width: 20%;
            vertical-align: middle;
        }

        .print-header-right {
            display: table-cell;
            width: 80%;
            vertical-align: middle;
            text-align: right;
            padding-left: 15px;
        }

        .print-header .logo {
            max-height: 80px;
            max-width: 200px;
            object-fit: contain;
        }

        .print-header .event-title {
            font-size: 14pt;
            font-weight: bold;
            margin-bottom: 2px;
            line-height: 1.2;
        }

        .print-header .event-info {
            font-size: 8pt;
            color: #333;
            line-height: 1.2;
        }

        /* Round Info */
        .round-info {
            text-align: center;
            margin-bottom: 5px;
            font-size: 10pt;
            font-weight: bold;
            page-break-after: avoid;
            line-height: 1.2;
        }

        /* Group Container */
        .group-container {
            margin-bottom: 6px;
            page-break-inside: avoid;
        }

        .group-title {
            font-size: 11pt;
            font-weight: bold;
            margin-bottom: 3px;
            padding-bottom: 2px;
            border-bottom: 1px solid #ccc;
            line-height: 1.2;
        }

        .group-keterangan {
            font-size: 8pt;
            font-style: italic;
            color: #555;
            margin-bottom: 4px;
            padding: 3px;
            background-color: #f5f5f5;
            border-left: 2px solid #000;
            line-height: 1.2;
        }

        /* Table Styles */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
            page-break-inside: auto;
        }

        table thead {
            background-color: #f0f0f0;
            page-break-inside: avoid;
            page-break-after: avoid;
        }

        table thead th {
            border: 1px solid #000;
            padding: 3px 3px;
            text-align: left;
            font-weight: bold;
            font-size: 7pt;
            line-height: 1.1;
        }

        table tbody tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        table tbody td {
            border: 1px solid #000;
            padding: 3px 2px;
            font-size: 7pt;
            vertical-align: top;
            line-height: 1.1;
        }

        /* Special font size for Plate and Gate Moto columns */
        .plate-cell, .gate-moto-cell {
            font-size: 10pt !important;
            font-weight: bold;
            text-align: center;
        }

        /* Special font size for Poin Moto, Rank, and Ket columns */
        .poin-moto-cell, .rank-cell, .ket-cell {
            font-size: 10pt !important;
            font-weight: bold;
            text-align: center;
        }

        .ket-cell {
            text-align: left;
        }

        /* Riders Column */
        .riders-cell {
            min-width: 120px;
        }

        .riders-name {
            font-weight: bold;
            margin-bottom: 1px;
            font-size: 8pt;
            line-height: 1.1;
        }

        .riders-nickname {
            font-size: 6.5pt;
            color: #555;
            margin-bottom: 1px;
            line-height: 1.1;
        }

        .riders-team {
            font-size: 6.5pt;
            font-style: italic;
            color: #777;
            line-height: 1.1;
        }

        /* Page Break */
        .page-break {
            page-break-before: always;
            display: block;
        }

        /* Floating Print Button (screen only) */
        @media screen {
            .floating-print-btn {
                padding: 15px 30px;
                font-size: 16px;
                font-weight: bold;
                background-color: #007bff;
                color: white;
                border: none;
                border-radius: 50px;
                cursor: pointer;
                box-shadow: 0 4px 12px rgba(0, 123, 255, 0.4);
                transition: all 0.3s ease;
            }

            .floating-print-btn:hover {
                background-color: #0056b3;
                box-shadow: 0 6px 16px rgba(0, 123, 255, 0.6);
                transform: translateY(-2px);
            }

            .floating-print-btn:active {
                transform: translateY(0);
            }
        }

        /* Print Media Query */
        @media print {
            @page {
                size: A4;
                margin: 0.5cm 0.7cm;
            }

            body {
                background: #fff;
            }

            /* Hide floating buttons */
            .floating-print-btn,
            a[style*="floating"] {
                display: none !important;
            }
            
            div[style*="position: fixed"] {
                display: none !important;
            }

            /* Page break rules */
            .page-break {
                page-break-before: always;
                display: block;
            }

            /* Table page break rules */
            table {
                page-break-inside: auto;
            }

            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }

            thead {
                display: table-header-group;
            }

            tfoot {
                display: table-footer-group;
            }

            /* Group container should not break */
            .group-container {
                page-break-inside: avoid;
            }

            /* Ensure header appears on every page */
            .print-header {
                page-break-after: avoid;
            }
        }
    </style>
</head>
<body>
    <!-- Floating Print Button (Screen Only) -->
    <div style="position: fixed; bottom: 30px; right: 30px; z-index: 1000; display: flex; gap: 10px;">
        <button onclick="window.print()" class="floating-print-btn">
            🖨️ Cetak Sekarang
        </button>
        @if(isset($backUrl))
            <a href="{{ $backUrl }}" style="padding: 15px 30px; font-size: 16px; font-weight: bold; background-color: #6c757d; color: white; border: none; border-radius: 50px; cursor: pointer; box-shadow: 0 4px 12px rgba(108, 117, 125, 0.4); transition: all 0.3s ease; text-decoration: none; display: inline-block;">
                ← Kembali
            </a>
        @endif
    </div>

    @if($sheetData && isset($sheetData['groups']) && count($sheetData['groups']) > 0)
        @foreach($sheetData['groups'] as $groupIndex => $group)
            @if($groupIndex > 0 && $groupIndex % 2 == 0)
                <div class="page-break"></div>
            @endif

            <!-- Print Header (appears on every page, only at start of page) -->
            @if($groupIndex == 0 || $groupIndex % 2 == 0)
                <div class="print-header">
                    <div class="print-header-left">
                        @if($event->logo_url)
                            <img src="{{ asset('storage/' . $event->logo_url) }}" alt="{{ $event->name }} Logo" class="logo">
                        @endif
                    </div>
                    <div class="print-header-right">
                        <div class="event-title">{{ $event->name }}</div>
                        <div class="event-info">
                            {{ $event->location }} | {{ \Carbon\Carbon::parse($event->start_date)->format('d M Y') }}
                        </div>
                    </div>
                </div>

                <!-- Round Info -->
                <div class="round-info">
                    {{ $category->title }} - {{ $selectedRound }}
                </div>

                <!-- Keterangan (only on first page) -->
                @php
                    $isFinal = stripos($selectedRound ?? '', 'final') !== false;
                    $showKeterangan = !empty($sheetData['keterangan']) && (!$isFinal || $groupIndex == 0);
                @endphp
                @if($showKeterangan)
                    <div class="group-keterangan" style="margin-bottom: 4px;">
                        <strong>Keterangan:</strong> {{ $sheetData['keterangan'] }}
                    </div>
                @endif
            @endif

            <!-- Group Container -->
            <div class="group-container">
                <div class="group-title">{{ $group['name'] }}</div>

                <!-- Table -->
                <table>
                    <thead>
                        <tr>
                            <th style="width: 5%;">Plate</th>
                            <th style="width: 25%;">Riders</th>
                            <th style="width: 10%;">Gate Moto</th>
                            @if($sheetData['columns']['has_poin_moto_1'] ?? false)
                                <th style="width: 8%;">Poin Moto 1</th>
                            @endif
                            @if($sheetData['columns']['has_poin_moto_2'] ?? false)
                                <th style="width: 8%;">Poin Moto 2</th>
                            @endif
                            @if($sheetData['columns']['has_poin_moto_3'] ?? false)
                                <th style="width: 8%;">Poin Moto 3</th>
                            @endif
                            @if($sheetData['is_qualifikasi'] ?? false)
                                <th style="width: 8%;">Total</th>
                            @endif
                            <th style="width: 6%;">Rank</th>
                            <th style="width: 12%;">Ket</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(!empty($group['data']))
                            @foreach($group['data'] as $row)
                                <tr>
                                    <td class="plate-cell">{{ $row['plate'] ?? '-' }}</td>
                                    
                                    <td class="riders-cell">
                                        @if(!empty($row['nama']))
                                            <div class="riders-name">{{ $row['nama'] }}</div>
                                        @endif
                                        @if(!empty($row['panggilan']))
                                            <div class="riders-nickname">{{ $row['panggilan'] }}</div>
                                        @endif
                                        @if(!empty($row['team']))
                                            <div class="riders-team">{{ $row['team'] }}</div>
                                        @endif
                                    </td>
                                    
                                    <td class="gate-moto-cell">
                                        @if($sheetData['is_qualifikasi'] ?? false)
                                            @php
                                                $gates = [];
                                                if (!empty($row['gate_moto_1'])) $gates[] = $row['gate_moto_1'];
                                                if (!empty($row['gate_moto_2'])) $gates[] = $row['gate_moto_2'];
                                                if (!empty($row['gate_moto_3'])) $gates[] = $row['gate_moto_3'];
                                            @endphp
                                            {{ !empty($gates) ? implode(' | ', $gates) : '-' }}
                                        @else
                                            {{ !empty($row['gate']) ? $row['gate'] : '-' }}
                                        @endif
                                    </td>
                                    
                                    @if($sheetData['columns']['has_poin_moto_1'] ?? false)
                                        <td class="poin-moto-cell">{{ !empty($row['poin_moto_1']) ? $row['poin_moto_1'] : '-' }}</td>
                                    @endif
                                    
                                    @if($sheetData['columns']['has_poin_moto_2'] ?? false)
                                        <td class="poin-moto-cell">{{ !empty($row['poin_moto_2']) ? $row['poin_moto_2'] : '-' }}</td>
                                    @endif
                                    
                                    @if($sheetData['columns']['has_poin_moto_3'] ?? false)
                                        <td class="poin-moto-cell">{{ !empty($row['poin_moto_3']) ? $row['poin_moto_3'] : '-' }}</td>
                                    @endif
                                    
                                    @if($sheetData['is_qualifikasi'] ?? false)
                                        <td class="poin-moto-cell">{{ !empty($row['total']) ? $row['total'] : '-' }}</td>
                                    @endif
                                    
                                    <td class="rank-cell">{{ !empty($row['rank']) ? $row['rank'] : '-' }}</td>
                                    
                                    <td class="ket-cell">{{ !empty($row['ket']) ? $row['ket'] : '-' }}</td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="{{ 3 + ($sheetData['columns']['has_poin_moto_1'] ?? false ? 1 : 0) + ($sheetData['columns']['has_poin_moto_2'] ?? false ? 1 : 0) + ($sheetData['columns']['has_poin_moto_3'] ?? false ? 1 : 0) + ($sheetData['is_qualifikasi'] ?? false ? 1 : 0) + 2 }}" style="text-align: center; padding: 20px;">
                                    Tidak ada data
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        @endforeach
    @else
        <div style="text-align: center; padding: 40px;">
            <p>Tidak ada data yang tersedia untuk dicetak.</p>
        </div>
    @endif
</body>
</html>

