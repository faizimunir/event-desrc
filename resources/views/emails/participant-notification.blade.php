<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $type === 'pending' ? 'Konfirmasi Registrasi' : ($type === 'confirmed' ? 'Pembayaran Dikonfirmasi' : 'Pembayaran Ditolak') }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
        }
        .header h1 {
            color: #2563eb;
            margin: 0;
            font-size: 24px;
        }
        .content {
            margin-bottom: 30px;
        }
        .info-box {
            background-color: #f8f9fa;
            border-left: 4px solid #2563eb;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .info-box h3 {
            margin-top: 0;
            color: #2563eb;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: bold;
            color: #666;
        }
        .info-value {
            color: #333;
        }
        .amount-box {
            background-color: #eff6ff;
            border: 2px solid #2563eb;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            text-align: center;
        }
        .amount-box .amount {
            font-size: 28px;
            font-weight: bold;
            color: #2563eb;
            margin: 10px 0;
        }
        .bank-info {
            background-color: #fef3c7;
            border: 1px solid #fbbf24;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .bank-info h3 {
            margin-top: 0;
            color: #92400e;
        }
        .qr-code {
            text-align: center;
            margin: 30px 0;
            padding: 20px;
            background-color: #f9fafb;
            border-radius: 8px;
        }
        .qr-code img {
            max-width: 250px;
            height: auto;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
            color: #666;
            font-size: 12px;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #2563eb;
            color: #ffffff;
            text-decoration: none;
            border-radius: 6px;
            margin: 20px 0;
        }
        .success-badge {
            display: inline-block;
            background-color: #10b981;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 20px;
        }
        .warning-badge {
            display: inline-block;
            background-color: #f59e0b;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 20px;
        }
        .error-badge {
            display: inline-block;
            background-color: #ef4444;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Event Registration</h1>
            @if($type === 'confirmed')
                <span class="success-badge">✓ Pembayaran Dikonfirmasi</span>
            @elseif($type === 'pending')
                <span class="warning-badge">⏳ Menunggu Pembayaran</span>
            @elseif($type === 'rejected')
                <span class="error-badge">✗ Pembayaran Ditolak</span>
            @endif
        </div>

        <div class="content">
            @php
                $participant = $participant->load(['package.category.event', 'payment']);
                $package = $participant->package;
                $category = $package->category;
                $event = $category->event;
                $payment = $participant->payment;
                
                $bankInfo = [
                    'bank_name' => config('app.bank_name', 'Bank BCA'),
                    'account_number' => config('app.bank_account', '1234567890'),
                    'account_name' => config('app.bank_account_name', 'Event Registration'),
                ];
            @endphp

            <p>Halo <strong>{{ $participant->name }}</strong>,</p>

            @if($type === 'pending')
                <p>Terima kasih telah mendaftar untuk event berikut:</p>

                <div class="info-box">
                    <h3>Detail Event</h3>
                    <div class="info-row">
                        <span class="info-label">Event:</span>
                        <span class="info-value">{{ $event->name }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Kategori:</span>
                        <span class="info-value">{{ $category->name }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Paket:</span>
                        <span class="info-value">{{ $package->name }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Lokasi:</span>
                        <span class="info-value">{{ $event->location }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Tanggal:</span>
                        <span class="info-value">{{ \Carbon\Carbon::parse($event->start_date)->format('d M Y') }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">No. Registrasi:</span>
                        <span class="info-value"><strong>{{ $participant->registration_number }}</strong></span>
                    </div>
                </div>

                <div class="amount-box">
                    <div>Total Pembayaran</div>
                    <div class="amount">Rp {{ number_format($package->price, 0, ',', '.') }}</div>
                    <div style="margin-top: 10px;">
                        <small>Kode Unik: <strong>{{ $participant->unique_code }}</strong></small>
                    </div>
                    <div style="margin-top: 10px; font-size: 18px;">
                        <strong>Total Transfer: Rp {{ number_format($package->price + (int)$participant->unique_code, 0, ',', '.') }}</strong>
                    </div>
                </div>

                <div class="bank-info">
                    <h3>Informasi Transfer Bank</h3>
                    <div class="info-row">
                        <span class="info-label">Bank:</span>
                        <span class="info-value"><strong>{{ $bankInfo['bank_name'] }}</strong></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">No. Rekening:</span>
                        <span class="info-value"><strong>{{ $bankInfo['account_number'] }}</strong></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Atas Nama:</span>
                        <span class="info-value"><strong>{{ $bankInfo['account_name'] }}</strong></span>
                    </div>
                </div>

                <p style="margin-top: 30px;">
                    <strong>Silakan lakukan pembayaran sesuai nominal di atas.</strong> Setelah pembayaran, upload bukti pembayaran di halaman pembayaran.
                </p>

                <div style="text-align: center; margin: 30px 0;">
                    <a href="{{ route('payment.show', $participant->id) }}" class="button">Upload Bukti Pembayaran</a>
                </div>

            @elseif($type === 'confirmed')
                <p>Pembayaran Anda telah <strong>dikonfirmasi</strong>!</p>

                <div class="info-box">
                    <h3>Detail Registrasi</h3>
                    <div class="info-row">
                        <span class="info-label">Event:</span>
                        <span class="info-value">{{ $event->name }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Kategori:</span>
                        <span class="info-value">{{ $category->name }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Paket:</span>
                        <span class="info-value">{{ $package->name }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Lokasi:</span>
                        <span class="info-value">{{ $event->location }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Tanggal:</span>
                        <span class="info-value">{{ \Carbon\Carbon::parse($event->start_date)->format('d M Y') }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">No. Registrasi:</span>
                        <span class="info-value"><strong>{{ $participant->registration_number }}</strong></span>
                    </div>
                </div>

                @if($qrCodePath)
                    <div class="qr-code">
                        <h3>QR Code Registrasi</h3>
                        <p>Gunakan QR Code ini saat check-in di lokasi event:</p>
                        <img src="{{ asset($qrCodePath) }}" alt="QR Code">
                        <p style="margin-top: 10px; font-size: 12px; color: #666;">
                            No. Registrasi: <strong>{{ $participant->registration_number }}</strong>
                        </p>
                    </div>
                @endif

                <p style="margin-top: 30px;">
                    <strong>Anda telah terdaftar resmi untuk event ini.</strong> Kami akan mengirimkan informasi lebih lanjut melalui email atau WhatsApp.
                </p>

            @elseif($type === 'rejected')
                <p>Maaf, pembayaran Anda untuk event <strong>{{ $event->name }}</strong> <strong>tidak dapat kami terima</strong>.</p>

                @if($payment && $payment->notes)
                    <div class="info-box" style="border-left-color: #ef4444;">
                        <h3 style="color: #ef4444;">Alasan Penolakan</h3>
                        <p>{{ $payment->notes }}</p>
                    </div>
                @endif

                <p>Silakan hubungi admin untuk informasi lebih lanjut atau melakukan pembayaran ulang.</p>

                <div style="text-align: center; margin: 30px 0;">
                    <a href="{{ route('payment.show', $participant->id) }}" class="button">Cek Status Pembayaran</a>
                </div>
            @endif
        </div>

        <div class="footer">
            <p>Terima kasih telah menggunakan layanan kami.</p>
            <p>Jika ada pertanyaan, silakan hubungi admin.</p>
            <p style="margin-top: 20px; font-size: 11px; color: #999;">
                Email ini dikirim secara otomatis, mohon jangan membalas email ini.
            </p>
        </div>
    </div>
</body>
</html>

