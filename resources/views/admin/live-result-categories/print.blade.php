<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Print') }} - {{ $liveResultCategory->title }} - {{ $event->title }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Arial', sans-serif; font-size: 10pt; line-height: 1.2; color: #000; background: #fff; }
        .print-page { page-break-after: always; }
        .print-page:last-child { page-break-after: auto; }
        .print-header { display: table; width: 100%; margin-bottom: 4px; padding-bottom: 4px; border-bottom: 1.5px solid #000; page-break-inside: avoid; }
        .print-header-left { display: table-cell; width: 18%; vertical-align: middle; }
        .print-header-right { display: table-cell; width: 82%; vertical-align: middle; text-align: right; padding-left: 10px; }
        .print-header .logo { max-height: 36px; max-width: 100px; object-fit: contain; }
        .print-header .event-title { font-size: 12pt; font-weight: bold; margin-bottom: 0; line-height: 1.2; }
        .print-header .event-info { font-size: 8pt; color: #333; line-height: 1.2; }
        .group-container { margin-bottom: 6px; page-break-inside: avoid; }
        .group-title { font-size: 10pt; font-weight: bold; margin-bottom: 2px; padding-bottom: 2px; border-bottom: 1px solid #ccc; line-height: 1.2; }
        .group-keterangan { font-size: 7.5pt; font-style: italic; color: #555; margin-bottom: 3px; padding: 3px; background-color: #f5f5f5; border-left: 2px solid #000; line-height: 1.2; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 4px; page-break-inside: avoid; }
        table thead { background-color: #f0f0f0; page-break-inside: avoid; page-break-after: avoid; }
        table thead th { border: 1px solid #000; padding: 3px 4px; text-align: left; font-weight: bold; font-size: 7.5pt; line-height: 1.2; }
        table tbody tr { page-break-inside: avoid; }
        table tbody td { border: 1px solid #333; padding: 3px 4px; font-size: 9pt; vertical-align: top; line-height: 1.25; }
        .plate-cell { font-size: 9pt; }
        .riders-cell { min-width: 150px; font-size: 9pt; line-height: 1.25; }
        .riders-name { font-weight: bold; margin-bottom: 1px; font-size: 9pt; line-height: 1.25; }
        .riders-nickname { font-size: 8pt; color: #555; margin-bottom: 1px; line-height: 1.25; }
        .riders-team { font-size: 8pt; font-style: italic; color: #777; line-height: 1.25; }
        .gate-moto-cell { font-size: 9pt; }
        .round-info { text-align: center; margin-bottom: 4px; font-size: 9pt; font-weight: bold; line-height: 1.2; }
        @media screen {
            .print-actions { position: fixed; top: 20px; right: 20px; z-index: 1000; display: flex; gap: 10px; }
            .print-btn, .back-btn { padding: 10px 20px; font-size: 14px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; text-decoration: none; display: inline-block; }
            .print-btn { background-color: #007bff; color: white; }
            .print-btn:hover { background-color: #0056b3; color: white; }
            .back-btn { background-color: #6c757d; color: white; }
            .back-btn:hover { background-color: #545b62; color: white; }
        }
        @media print {
            @page { size: A4; margin: 8mm; }
            .print-actions { display: none !important; }
            .print-page { page-break-after: always; }
            .print-page:last-child { page-break-after: auto; }
            thead { display: table-header-group; }
        }
    </style>
</head>
<body>
    <div class="print-actions">
        <button type="button" onclick="window.print()" class="print-btn">{{ __('Print Sekarang') }}</button>
        <a href="{{ $backUrl }}" class="back-btn">{{ __('Kembali') }}</a>
    </div>

    @if($sheetData && isset($sheetData['groups']) && count($sheetData['groups']) > 0)
        @foreach(array_chunk($sheetData['groups'], 2) as $pageIndex => $groupsOnPage)
            <div class="print-page">
                <div class="print-header">
                    <div class="print-header-left">
                        @if($event->logoUrl())
                            <img src="{{ $event->logoUrl() }}" alt="{{ $event->title }}" class="logo">
                        @endif
                    </div>
                    <div class="print-header-right">
                        <div class="event-title">{{ $event->title }}</div>
                        <div class="event-info">
                            {{ $event->location?->name ?? '-' }} | {{ $event->start_at?->format('d M Y') ?? '-' }}
                        </div>
                    </div>
                </div>
                <div class="round-info">{{ $liveResultCategory->title }} - {{ $selectedRound }}</div>

                @foreach($groupsOnPage as $groupIndexOnPage => $group)
                    <div class="group-container">
                        <div class="group-title">{{ $group['name'] }}</div>
                        @php
                            $showKeterangan = !empty($sheetData['keterangan']) && $pageIndex === 0 && $groupIndexOnPage === 0;
                        @endphp
                        @if($showKeterangan)
                            <div class="group-keterangan">{{ $sheetData['keterangan'] }}</div>
                        @endif

                        <table>
                            <thead>
                                <tr>
                                    <th style="width: 5%;">Plate</th>
                                    <th style="width: 37.5%;">Riders</th>
                                    <th style="width: 15%;">Gate Moto</th>
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
                                            <td class="plate-cell" style="text-align: center;">{{ $row['plate'] ?? '-' }}</td>
                                            <td class="riders-cell">
                                                @if(!empty($row['nama']))<div class="riders-name">{{ $row['nama'] }}</div>@endif
                                                @if(!empty($row['panggilan']))<div class="riders-nickname">{{ $row['panggilan'] }}</div>@endif
                                                @if(!empty($row['team']))<div class="riders-team">{{ $row['team'] }}</div>@endif
                                            </td>
                                            <td class="gate-moto-cell" style="text-align: center;">
                                                @php
                                                    $gates = [];
                                                    if (!empty($row['gate_moto_1'])) $gates[] = $row['gate_moto_1'];
                                                    if (!empty($row['gate_moto_2'])) $gates[] = $row['gate_moto_2'];
                                                    if (!empty($row['gate_moto_3'])) $gates[] = $row['gate_moto_3'];
                                                @endphp
                                                @if(!empty($gates))
                                                    {{ implode(' | ', $gates) }}
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
                                        <td colspan="{{ 3 + ($sheetData['columns']['has_poin_moto_1'] ?? false ? 1 : 0) + ($sheetData['columns']['has_poin_moto_2'] ?? false ? 1 : 0) + ($sheetData['columns']['has_poin_moto_3'] ?? false ? 1 : 0) + ($sheetData['is_qualifikasi'] ?? false ? 1 : 0) + 2 }}" style="text-align: center; padding: 6px;">{{ __('Tidak ada data') }}</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                @endforeach
            </div>
        @endforeach
    @else
        <div style="text-align: center; padding: 40px;">{{ __('Tidak ada data yang tersedia untuk dicetak.') }}</div>
    @endif
</body>
</html>
