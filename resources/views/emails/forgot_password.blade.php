<!-- resources/views/emails/send_password.blade.php -->
<!DOCTYPE html>
<html>
<head>
    <title>Your New Password</title>
</head>
<body>
    <p>Hello,</p>
    <p>Your new temporary password is: <strong>{{ $password }}</strong></p>
    <p>Please log in using this password and change it immediately from your account settings.</p>
    <p>Thanks,<br>Rosterly Team</p>
</body>
</html>
