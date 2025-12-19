<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print - {{ $category->title }} - {{ $event->name }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            font-size: 11pt;
            line-height: 1.4;
            color: #000;
            background: #fff;
        }

        /* Print Header - appears on every page */
        .print-header {
            display: table;
            width: 100%;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #000;
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
            padding-left: 20px;
        }

        .print-header .logo {
            max-height: 60px;
            max-width: 150px;
            object-fit: contain;
        }

        .print-header .event-title {
            font-size: 18pt;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .print-header .event-info {
            font-size: 10pt;
            color: #333;
        }

        /* Group Container */
        .group-container {
            margin-bottom: 20px;
            page-break-inside: avoid;
        }

        .group-title {
            font-size: 14pt;
            font-weight: bold;
            margin-bottom: 8px;
            padding-bottom: 5px;
            border-bottom: 1px solid #ccc;
        }

        .group-keterangan {
            font-size: 10pt;
            font-style: italic;
            color: #555;
            margin-bottom: 10px;
            padding: 5px;
            background-color: #f5f5f5;
            border-left: 3px solid #000;
        }

        /* Table Styles */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            page-break-inside: auto;
        }

        table thead {
            background-color: #f0f0f0;
            page-break-inside: avoid;
            page-break-after: avoid;
        }

        table thead th {
            border: 1px solid #000;
            padding: 8px 6px;
            text-align: left;
            font-weight: bold;
            font-size: 9pt;
        }

        table tbody tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        table tbody td {
            border: 1px solid #333;
            padding: 6px 4px;
            font-size: 9pt;
            vertical-align: top;
        }

        /* Riders Column */
        .riders-cell {
            min-width: 150px;
        }

        .riders-name {
            font-weight: bold;
            margin-bottom: 2px;
        }

        .riders-nickname {
            font-size: 8pt;
            color: #555;
            margin-bottom: 2px;
        }

        .riders-team {
            font-size: 8pt;
            font-style: italic;
            color: #777;
        }

        /* Page Break */
        .page-break {
            page-break-before: always;
            display: block;
        }

        /* Print Button (screen only) */
        @media screen {
            .print-actions {
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 1000;
                display: flex;
                gap: 10px;
            }

            .print-btn, .back-btn {
                padding: 10px 20px;
                font-size: 14px;
                border: none;
                border-radius: 5px;
                cursor: pointer;
                font-weight: bold;
            }

            .print-btn {
                background-color: #007bff;
                color: white;
            }

            .print-btn:hover {
                background-color: #0056b3;
            }

            .back-btn {
                background-color: #6c757d;
                color: white;
            }

            .back-btn:hover {
                background-color: #545b62;
            }
        }

        @media print {
            .print-actions {
                display: none;
            }

            .page-break {
                display: block;
                page-break-before: always;
            }

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
        }

        /* Round Info */
        .round-info {
            text-align: center;
            margin-bottom: 15px;
            font-size: 12pt;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <!-- Print Actions (Screen Only) -->
    <div class="print-actions">
        <button onclick="window.print()" class="print-btn">🖨️ Print Sekarang</button>
        <a href="{{ route('admin.live-result-categories.index', $event->id) }}" class="back-btn">← Kembali</a>
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
            @endif

            <!-- Group Container -->
            <div class="group-container">
                <div class="group-title">{{ $group['name'] }}</div>
                
                @php
                    // Show keterangan only for first group on each page
                    $isFirstGroupOnPage = ($groupIndex == 0 || $groupIndex % 2 == 0);
                    $isFinal = stripos($selectedRound ?? '', 'final') !== false;
                    $showKeterangan = !empty($sheetData['keterangan']) && $isFirstGroupOnPage && (!$isFinal || $groupIndex == 0);
                @endphp
                @if($showKeterangan)
                    <div class="group-keterangan">
                        {{ $sheetData['keterangan'] }}
                    </div>
                @endif

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
                                    <td style="text-align: center;">{{ $row['plate'] ?? '-' }}</td>
                                    
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
                                    
                                    <td style="text-align: center;">
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
                                        <td style="text-align: center;">{{ !empty($row['poin_moto_1']) ? $row['poin_moto_1'] : '-' }}</td>
                                    @endif
                                    
                                    @if($sheetData['columns']['has_poin_moto_2'] ?? false)
                                        <td style="text-align: center;">{{ !empty($row['poin_moto_2']) ? $row['poin_moto_2'] : '-' }}</td>
                                    @endif
                                    
                                    @if($sheetData['columns']['has_poin_moto_3'] ?? false)
                                        <td style="text-align: center;">{{ !empty($row['poin_moto_3']) ? $row['poin_moto_3'] : '-' }}</td>
                                    @endif
                                    
                                    @if($sheetData['is_qualifikasi'] ?? false)
                                        <td style="text-align: center; font-weight: bold;">{{ !empty($row['total']) ? $row['total'] : '-' }}</td>
                                    @endif
                                    
                                    <td style="text-align: center; font-weight: bold;">{{ !empty($row['rank']) ? $row['rank'] : '-' }}</td>
                                    
                                    <td>{{ !empty($row['ket']) ? $row['ket'] : '-' }}</td>
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

    <script>
        // Auto trigger print dialog on page load (optional)
        // window.addEventListener('load', function() {
        //     window.print();
        // });
    </script>
</body>
</html>

