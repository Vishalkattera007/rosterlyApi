<!DOCTYPE html>
<html>
<head>
    <title>Your Login Credentials - Rosterly</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6;">
    <p>Dear {{ $firstName }},</p>

    <p>Welcome to <strong>Rosterly</strong>!</p>

    <p>Your login has been successfully created. You can log in using your registered email address. Below are your login credentials:</p>

    <p><strong>Password:</strong> {{ $password }}</p>

    <p>For security reasons, we recommend that you change your password after logging in for the first time.</p>

    <p>Click the link below to access your account:</p>
    
    <p><a href="https://rosterly.up.railway.app/" target="_blank">👉 Login to Rosterly</a></p>

    <p>Regards, <br>Team Rosterly</p>
</body>
</html>
