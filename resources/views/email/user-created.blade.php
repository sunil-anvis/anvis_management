{{-- <!DOCTYPE html>
<html>
<head>
    <title>Account Created</title>
</head>
<body>

<h2>Welcome {{ $user->first_name }}</h2>

<p>Your account has been created successfully.</p>

<p><strong>Username:</strong> {{ $user->username }}</p>

<p><strong>Email:</strong> {{ $user->email }}</p>

<p><strong>Password:</strong> {{ $plainPassword }}</p>

<p>Please change your password after your first login.</p>

</body>
</html> --}}


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to RO Management</title>
</head>
<body style="margin:0;padding:0;background:#f4f7fb;font-family:Arial,Helvetica,sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f7fb;padding:40px 0;">
    <tr>
        <td align="center">

            <table width="650" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 8px 25px rgba(0,0,0,.08);">

                <!-- Header -->
                <tr>
                    <td align="center" style="background:#2563eb;padding:30px;">
                        <h1 style="margin:0;color:#ffffff;font-size:28px;">
                            RO Management
                        </h1>
                        <p style="margin-top:8px;color:#dbeafe;font-size:15px;">
                            Your account has been successfully created
                        </p>
                    </td>
                </tr>

                <!-- Greeting -->
                <tr>
                    <td style="padding:35px;">

                        <h2 style="margin-top:0;color:#1f2937;">
                            Hello {{ $user->first_name }}{{ $user->last_name }}
                        </h2>

                        <p style="color:#4b5563;font-size:15px;line-height:26px;">
                            Welcome to <strong>RO Management</strong>.
                            An administrator has created your account successfully.
                        </p>

                        <p style="color:#4b5563;font-size:15px;">
                            Below are your login credentials:
                        </p>

                        <!-- Credentials -->
                        <table width="100%" cellpadding="12" cellspacing="0" style="margin-top:20px;border:1px solid #e5e7eb;border-radius:8px;background:#f9fafb;">

                            <tr>
                                <td width="35%" style="font-weight:bold;color:#374151;">
                                    Username
                                </td>

                                <td style="color:#111827;">
                                    {{ $user->username }}
                                </td>
                            </tr>

                            <tr>
                                <td style="font-weight:bold;color:#374151;">
                                    Email
                                </td>

                                <td style="color:#111827;">
                                    {{ $user->email }}
                                </td>
                            </tr>

                            <tr>
                                <td style="font-weight:bold;color:#374151;">
                                    Password
                                </td>

                                <td style="color:#111827;">
                                    {{ $plainPassword }}
                                </td>
                            </tr>

                            <tr>
                                <td style="font-weight:bold;color:#374151;">
                                    Role
                                </td>

                                <td>
                                    {{ $user->roles->name ?? '-' }}
                                </td>
                            </tr>

                            <tr>
                                <td style="font-weight:bold;color:#374151;">
                                    Department
                                </td>

                                <td>
                                    {{ $user->departments->name ?? '-' }}
                                </td>
                            </tr>

                        </table>

                        <!-- Login Button -->
                        <div style="text-align:center;margin:40px 0;">

                            <a href="http://127.0.0.1:8001/login"
                               style="background:#2563eb;
                                      color:#ffffff;
                                      text-decoration:none;
                                      padding:14px 30px;
                                      border-radius:8px;
                                      font-size:16px;
                                      display:inline-block;
                                      font-weight:bold;">

                                Login to RO Management

                            </a>

                        </div>

                        <!-- Notice -->

                        <div style="background:#fff8e1;border-left:5px solid #f59e0b;padding:18px;border-radius:6px;">

                            <strong style="color:#92400e;">
                                Security Notice
                            </strong>

                            <p style="margin:8px 0 0;color:#78350f;line-height:24px;">
                                Please sign in using the above credentials and
                                change your password immediately after your first login.
                            </p>

                        </div>

                    </td>
                </tr>

                <!-- Footer -->

                <tr>

                    <td align="center"
                        style="background:#f3f4f6;padding:25px;font-size:13px;color:#6b7280;">

                        © {{ date('Y') }} RO Management System

                        <br><br>

                        This is an automated email. Please do not reply.

                    </td>

                </tr>

            </table>

        </td>
    </tr>
</table>

</body>
</html>
