<!DOCTYPE html>
<html>
<head>
    <title>Password Reset</title>
</head>
<body>
    <h1>Hello {{ $user->name ?? $user->email }},</h1>

    <p>You are receiving this email because we received a password reset request for your account.</p>

    @php
        $frontendUrl = config('app.frontend_url') 
        . '/user/reset-password?token=' . $token 
        . '&email=' . urlencode($user->email);
    @endphp

    <a href="{{ $frontendUrl }}" style="background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">
        Reset Password
    </a>

    <p>This password reset link will expire in {{ config('auth.passwords.users.expire') }} minutes.</p>

    <p>If you did not request a password reset, no further action is required.</p>

    <p>Regards,<br>{{ config('app.name') }}</p>
</body>
</html>
