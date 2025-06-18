<!DOCTYPE html>
<html>
<head>
    <title>Your Login Password</title>
</head>
<body>
    <p>Hello {{ $firstName }},</p>

    <p>Welcome to Rosterly! Please find your login credentials below:</p>

    <p><strong>Email:</strong> Your registered email address</p>
    <p><strong>Password:</strong> <span style="color: blue;">{{ $password }}</span></p>

    <p>Please login and change your password after the first login.</p>

    <p>
        <a href="https://rosterly.up.railway.app/">Login here</a>
    </p>

    <br><br>
    <p>Regards,</p>
    <p>Rosterly Team</p>
</body>
</html>
