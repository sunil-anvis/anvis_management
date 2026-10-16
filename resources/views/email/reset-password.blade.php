<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
</head>
<body>

    <h2>Reset Password</h2>

    <p>Hello,</p>

    <p>You requested to reset your password.</p>

    <p>
        <a href="{{ $resetLink }}">
            Click here to reset your password
        </a>
    </p>

    <p>This link will expire in <strong>10 minutes</strong>.</p>

    <p>If you didn't request this, please ignore this email.</p>

</body>
</html>
