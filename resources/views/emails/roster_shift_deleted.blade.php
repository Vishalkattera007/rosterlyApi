<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Shift Cancelled</title>
</head>
<body>
    <h2>Hi {{ $user->firstName }},</h2>
    <p>Your shift on <strong>{{ \Carbon\Carbon::parse($shift->date)->format('l, d M Y') }}</strong> from <strong>{{ \Carbon\Carbon::parse($shift->startTime)->format('h:i A') }}</strong> to <strong>{{ \Carbon\Carbon::parse($shift->endTime)->format('h:i A') }}</strong> has been cancelled.</p>
    <p>If you have questions, please contact your manager.</p>
    <p>Thanks,<br>Office Rosterly Team</p>
</body>
</html>
