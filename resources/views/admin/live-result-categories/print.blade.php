<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Print') }} - {{ $liveResultCategory->title }} - {{ $event->title }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        /* Kepadatan mengikuti print-center preview (2 grup per halaman) */
        body { font-family: 'Arial', sans-serif; font-size: 8pt; line-height: 1.15; color: #000; background: #fff; }
        .print-page { page-break-after: always; }
        .print-page:last-child { page-break-after: auto; }
        .print-header { display: table; width: 100%; table-layout: fixed; margin-bottom: 5px; padding-bottom: 3px; border-bottom: 1.5px solid #000; page-break-inside: avoid; page-break-after: avoid; }
        .print-header-left { display: table-cell; width: 22%; vertical-align: middle; text-align: left; }
        .print-header-center { display: table-cell; width: 56%; vertical-align: middle; text-align: center; padding: 0 10px; }
        .print-header-right { display: table-cell; width: 22%; vertical-align: middle; text-align: right; }
        .print-header .logo { object-fit: contain; display: inline-block; vertical-align: middle; }
        .print-header .logo-event { max-height: 48px; max-width: 120px; }
        .print-header .logo-drc { max-height: 32px; max-width: 110px; }
        .print-header .event-title { font-size: 14pt; font-weight: bold; margin-bottom: 2px; line-height: 1.2; text-align: justify; text-align-last: center; -moz-text-align-last: center; }
        .print-header .event-info { font-size: 8pt; color: #333; line-height: 1.2; text-align: center; }
        .round-info { text-align: center; margin-bottom: 5px; font-size: 10pt; font-weight: bold; page-break-after: avoid; page-break-inside: avoid; line-height: 1.2; }
        .group-keterangan-block { font-size: 8pt; font-style: italic; color: #555; margin-bottom: 4px; padding: 3px; background-color: #f5f5f5; border-left: 2px solid #000; line-height: 1.2; page-break-inside: avoid; }
        .group-container { margin-bottom: 6px; page-break-inside: avoid; }
        /* Usahakan grup kedua tetap di halaman yang sama dengan grup pertama (sepasang) */
        .print-page .group-container:first-of-type { page-break-after: avoid; }
        .group-title { font-size: 11pt; font-weight: bold; margin-bottom: 3px; padding-bottom: 2px; border-bottom: 1px solid #ccc; line-height: 1.2; page-break-after: avoid; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 5px; page-break-inside: auto; }
        table thead { background-color: #f0f0f0; page-break-inside: avoid; page-break-after: avoid; }
        table thead th { border: 1px solid #000; padding: 2px 3px; text-align: left; font-weight: bold; font-size: 7pt; line-height: 1.1; }
        table tbody tr { page-break-inside: avoid; }
        table tbody td { border: 1px solid #000; padding: 2px 3px; font-size: 7pt; vertical-align: top; line-height: 1.1; }
        .plate-cell, .gate-moto-cell { font-size: 10pt !important; font-weight: bold; text-align: center; }
        .poin-moto-cell, .rank-cell { font-size: 10pt !important; font-weight: bold; text-align: center; }
        .ket-cell { font-size: 10pt !important; font-weight: bold; text-align: left; }
        .riders-cell { min-width: 120px; }
        .riders-name { font-weight: bold; margin-bottom: 1px; font-size: 8pt; line-height: 1.1; }
        .riders-nickname { font-size: 6.5pt; color: #555; margin-bottom: 1px; line-height: 1.1; }
        .riders-team { font-size: 6.5pt; font-style: italic; color: #777; line-height: 1.1; }
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
                @include('admin.partials.print-event-header-branded', ['event' => $event])
                <div class="round-info">{{ $liveResultCategory->title }} - {{ $selectedRound }}</div>

                @if(!empty($sheetData['keterangan']))
                    <div class="group-keterangan-block"><strong>{{ __('Keterangan') }}:</strong> {{ $sheetData['keterangan'] }}</div>
                @endif

                @foreach($groupsOnPage as $group)
                    <div class="group-container">
                        <div class="group-title">{{ $group['name'] }}</div>

                        <table>
                            <thead>
                                <tr>
                                    <th style="width: 5%;">Plate</th>
                                    <th style="width: 32%;">Riders</th>
                                    <th style="width: 12%;">Gate Moto</th>
                                    @if($sheetData['columns']['has_poin_moto_1'] ?? false)
                                        <th style="width: 7%;">Poin M1</th>
                                    @endif
                                    @if($sheetData['columns']['has_poin_moto_2'] ?? false)
                                        <th style="width: 7%;">Poin M2</th>
                                    @endif
                                    @if($sheetData['columns']['has_poin_moto_3'] ?? false)
                                        <th style="width: 7%;">Poin M3</th>
                                    @endif
                                    @if($sheetData['is_qualifikasi'] ?? false)
                                        <th style="width: 7%;">Total</th>
                                    @endif
                                    <th style="width: 6%;">Rank</th>
                                    <th style="width: 17%;">Ket</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if(!empty($group['data']))
                                    @foreach($group['data'] as $row)
                                        <tr>
                                            <td class="plate-cell">{{ $row['plate'] ?? '-' }}</td>
                                            <td class="riders-cell">
                                                @if(!empty($row['nama']))<div class="riders-name">{{ $row['nama'] }}</div>@endif
                                                @if(!empty($row['panggilan']))<div class="riders-nickname">{{ $row['panggilan'] }}</div>@endif
                                                @if(!empty($row['team']))<div class="riders-team">{{ $row['team'] }}</div>@endif
                                            </td>
                                            <td class="gate-moto-cell">
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
