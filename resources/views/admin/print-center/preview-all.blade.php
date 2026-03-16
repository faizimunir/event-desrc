<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Print Preview') }} - {{ __('Semua Kategori Final') }} - {{ $event->title }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Arial', sans-serif; font-size: 8pt; line-height: 1.15; color: #000; background: #fff; }
        .print-header { display: table; width: 100%; margin-bottom: 5px; padding-bottom: 3px; border-bottom: 1.5px solid #000; page-break-inside: avoid; page-break-after: avoid; }
        .print-header-left { display: table-cell; width: 20%; vertical-align: middle; }
        .print-header-right { display: table-cell; width: 80%; vertical-align: middle; text-align: right; padding-left: 15px; }
        .print-header .logo { max-height: 80px; max-width: 200px; object-fit: contain; }
        .print-header .event-title { font-size: 14pt; font-weight: bold; margin-bottom: 2px; line-height: 1.2; }
        .print-header .event-info { font-size: 8pt; color: #333; line-height: 1.2; }
        .category-section { margin-bottom: 15px; page-break-inside: avoid; }
        .round-info { text-align: center; margin-bottom: 5px; font-size: 10pt; font-weight: bold; page-break-after: avoid; page-break-inside: avoid; line-height: 1.2; }
        .group-container { margin-bottom: 6px; page-break-inside: avoid; }
        .group-title { font-size: 11pt; font-weight: bold; margin-bottom: 3px; padding-bottom: 2px; border-bottom: 1px solid #ccc; line-height: 1.2; }
        .group-keterangan { font-size: 8pt; font-style: italic; color: #555; margin-bottom: 4px; padding: 3px; background-color: #f5f5f5; border-left: 2px solid #000; line-height: 1.2; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 5px; page-break-inside: auto; }
        table thead { background-color: #f0f0f0; page-break-inside: avoid; page-break-after: avoid; }
        table thead th { border: 1px solid #000; padding: 3px; text-align: left; font-weight: bold; font-size: 7pt; line-height: 1.1; }
        table tbody tr { page-break-inside: avoid; }
        table tbody td { border: 1px solid #000; padding: 3px 2px; font-size: 7pt; vertical-align: top; line-height: 1.1; }
        .plate-cell, .gate-moto-cell { font-size: 10pt !important; font-weight: bold; text-align: center; }
        .poin-moto-cell, .rank-cell { font-size: 10pt !important; font-weight: bold; text-align: center; }
        .ket-cell { font-size: 10pt !important; font-weight: bold; text-align: left; }
        .riders-cell { min-width: 120px; }
        .riders-name { font-weight: bold; margin-bottom: 1px; font-size: 8pt; line-height: 1.1; }
        .riders-nickname { font-size: 6.5pt; color: #555; margin-bottom: 1px; line-height: 1.1; }
        .riders-team { font-size: 6.5pt; font-style: italic; color: #777; line-height: 1.1; }
        .page-break { page-break-before: always; display: block; }
        .category-wrapper { page-break-inside: avoid; }
        @media screen {
            .print-actions { position: fixed; bottom: 30px; right: 30px; z-index: 1000; display: flex; gap: 10px; }
            .print-btn, .back-btn { padding: 15px 30px; font-size: 16px; font-weight: bold; border: none; border-radius: 50px; cursor: pointer; text-decoration: none; display: inline-block; }
            .print-btn { background-color: #007bff; color: white; box-shadow: 0 4px 12px rgba(0, 123, 255, 0.4); }
            .print-btn:hover { background-color: #0056b3; color: white; }
            .back-btn { background-color: #6c757d; color: white; box-shadow: 0 4px 12px rgba(108, 117, 125, 0.4); }
            .back-btn:hover { background-color: #545b62; color: white; }
        }
        @media print {
            .print-actions { display: none !important; }
            .page-break { display: block; page-break-before: always; }
            thead { display: table-header-group; }
        }
    </style>
</head>
<body>
    <div class="print-actions">
        <button type="button" onclick="window.print()" class="print-btn">{{ __('Cetak Sekarang') }}</button>
        <a href="{{ route('print-center.index') }}" class="back-btn">{{ __('Kembali') }}</a>
    </div>

    @php $globalGroupIndex = 0; @endphp
    @foreach($categoriesData as $categoryIndex => $categoryData)
        @php
            $category = $categoryData['category'];
            $selectedRound = $categoryData['round'];
            $sheetData = $categoryData['sheetData'];
        @endphp

        @if($categoryIndex > 0)
            <div class="page-break"></div>
        @endif

        <div class="category-wrapper">
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

            <div class="round-info">{{ $category->title }} - {{ $selectedRound }}</div>

            <div class="category-section">
            @if($sheetData && isset($sheetData['groups']) && count($sheetData['groups']) > 0)
                @if(!empty($sheetData['keterangan']))
                    <div class="group-keterangan" style="margin-bottom: 4px;"><strong>{{ __('Keterangan') }}:</strong> {{ $sheetData['keterangan'] }}</div>
                @endif

                @foreach($sheetData['groups'] as $groupIndex => $group)
                    @if($groupIndex > 0 && $groupIndex % 2 === 0)
                        {{-- Header ulang setiap 2 grup --}}
                        <div style="page-break-before: always;"></div>
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
                        <div class="round-info">{{ $category->title }} - {{ $selectedRound }}</div>
                    @endif

                    <div class="group-container">
                        <div class="group-title">{{ $group['name'] }}</div>
                        <table>
                            <thead>
                                <tr>
                                    <th style="width: 8%;">Plate</th>
                                    <th style="width: 40%;">Riders</th>
                                    @if($sheetData['is_qualifikasi'] ?? false)
                                        <th style="width: 10%;">Total</th>
                                    @endif
                                    <th style="width: 8%;">Rank</th>
                                    <th style="width: 34%;">Ket</th>
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
                                            @if($sheetData['is_qualifikasi'] ?? false)
                                                <td class="poin-moto-cell">{{ !empty($row['total']) ? $row['total'] : '-' }}</td>
                                            @endif
                                            <td class="rank-cell">{{ !empty($row['rank']) ? $row['rank'] : '-' }}</td>
                                            <td class="ket-cell">{{ !empty($row['ket']) ? $row['ket'] : '-' }}</td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="{{ 2 + ($sheetData['is_qualifikasi'] ?? false ? 1 : 0) + 2 }}" style="text-align: center; padding: 20px;">{{ __('Tidak ada data') }}</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                @endforeach
            @else
                <div style="text-align: center; padding: 20px;">{{ __('Tidak ada data yang tersedia untuk kategori ini.') }}</div>
            @endif
            </div>
        </div>
    @endforeach

    @if(empty($categoriesData))
        <div style="text-align: center; padding: 40px;">{{ __('Tidak ada data yang tersedia untuk dicetak.') }}</div>
    @endif
</body>
</html>
