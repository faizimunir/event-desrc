<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Payment link') }}</title>
</head>
<body style="font-family: sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <p>{{ __('Hello') }}, {{ $recipientName }}.</p>
    <p>{{ __('Your payment link for :event is ready. Please complete your payment by clicking the link below:', ['event' => $eventTitle]) }}</p>
    <p style="margin: 24px 0;">
        <a href="{{ $paymentLinkUrl }}" style="display: inline-block; padding: 12px 24px; background: #2563eb; color: #fff; text-decoration: none; border-radius: 8px;">{{ __('Open payment page') }}</a>
    </p>
    <p style="font-size: 14px; color: #666;">{{ __('If the button does not work, copy and paste this link into your browser:') }}</p>
    <p style="font-size: 13px; word-break: break-all; color: #555;">{{ $paymentLinkUrl }}</p>
    <p style="margin-top: 32px; font-size: 13px; color: #666;">{{ __('Thank you.') }}</p>
</body>
</html>
