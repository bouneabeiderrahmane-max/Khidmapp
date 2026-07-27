<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>{{ $notificationTitle }}</title>
</head>
<body style="font-family: sans-serif; background-color: #f4f4f5; padding: 24px;">
    <div style="max-width: 480px; margin: 0 auto; background: #ffffff; border-radius: 8px; padding: 24px;">
        <h1 style="font-size: 18px; margin-bottom: 12px;">Khidmapp</h1>
        <h2 style="font-size: 16px; margin-bottom: 12px;">{{ $notificationTitle }}</h2>
        <p style="font-size: 14px; color: #333;">{{ $notificationBody }}</p>
    </div>
</body>
</html>
