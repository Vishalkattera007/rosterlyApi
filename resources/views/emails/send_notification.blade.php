<!DOCTYPE html>
<html>
<head>
    <title>Your Login Password</title>
</head>
<body>
    <p>Hello,</p>
    <p>You got notification from: <strong>{{ $notificationMessage }}</strong></p>
    <p>Login for Approve or Deny the request</p>
    <a href="{{ env('APP_LIVE_URL') }}">Login here</a>
</body>
</html>
