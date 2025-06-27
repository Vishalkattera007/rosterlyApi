<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Unavailability Notification</title>
</head>
<body>
    <p>Hello,</p>

    <p><strong>{{ $userName }}</strong> has submitted a request:</p>

    <ul>
        <li><strong>Type:</strong> {{ $title }}</li>
        @if ($day)
            <li><strong>Day:</strong> {{ $day }}</li>
        @endif
        @if ($fromDT && $toDT)
            <li><strong>From:</strong> {{ $fromDT }}</li>
            <li><strong>To:</strong> {{ $toDT }}</li>
        @endif
        <li><strong>Reason:</strong> {{ $reason }}</li>
    </ul>

    <p>Please login to your dashboard to approve or deny the request.</p>

    <a href="{{ env('APP_LIVE_URL') }}" style="color: blue;">Login Here</a>
</body>
</html>
