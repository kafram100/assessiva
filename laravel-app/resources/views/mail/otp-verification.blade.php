<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Your Assessiva Verification Code</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f4f6fb; font-family: 'Segoe UI', Arial, sans-serif; color: #1a1a2e; }
        .wrapper { max-width: 560px; margin: 40px auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.08); }
        .header { background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); padding: 36px 40px; text-align: center; }
        .header img { height: 36px; margin-bottom: 12px; }
        .header h1 { color: #ffffff; font-size: 22px; font-weight: 700; letter-spacing: -0.3px; }
        .header p { color: rgba(255,255,255,0.80); font-size: 14px; margin-top: 4px; }
        .body { padding: 40px; }
        .greeting { font-size: 16px; color: #374151; margin-bottom: 16px; }
        .greeting strong { color: #1a1a2e; }
        .info { font-size: 14px; color: #6b7280; line-height: 1.6; margin-bottom: 28px; }
        .otp-box { background: #f0eeff; border: 2px dashed #7c3aed; border-radius: 12px; padding: 24px; text-align: center; margin-bottom: 24px; }
        .otp-label { font-size: 12px; font-weight: 600; color: #7c3aed; text-transform: uppercase; letter-spacing: 1.2px; margin-bottom: 10px; }
        .otp-code { font-size: 42px; font-weight: 800; letter-spacing: 10px; color: #4f46e5; font-family: 'Courier New', monospace; }
        .expiry { font-size: 13px; color: #ef4444; font-weight: 600; margin-top: 10px; }
        .divider { border: none; border-top: 1px solid #e5e7eb; margin: 28px 0; }
        .warning { font-size: 13px; color: #9ca3af; line-height: 1.6; }
        .warning strong { color: #374151; }
        .footer { background: #f9fafb; padding: 20px 40px; text-align: center; border-top: 1px solid #e5e7eb; }
        .footer p { font-size: 12px; color: #9ca3af; line-height: 1.5; }
        .footer a { color: #4f46e5; text-decoration: none; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>Assessiva</h1>
            <p>AI-Powered Assessment Platform</p>
        </div>
        <div class="body">
            <p class="greeting">Hello, <strong>{{ $user->name }}</strong> 👋</p>
            <p class="info">
                Thank you for creating an Assessiva account. To complete your registration and verify your
                email address, please use the one-time code below.
            </p>

            <div class="otp-box">
                <div class="otp-label">Your Verification Code</div>
                <div class="otp-code">{{ $otp }}</div>
                <div class="expiry">⏱ Expires in 10 minutes</div>
            </div>

            <hr class="divider" />

            <p class="warning">
                <strong>Didn't create an account?</strong><br />
                If you didn't sign up for Assessiva, please ignore this email. No action is needed — your
                email address will not be used without verification.
            </p>
        </div>
        <div class="footer">
            <p>
                This is an automated message from <a href="{{ config('app.url') }}">Assessiva</a>.<br />
                Please do not reply to this email.
            </p>
        </div>
    </div>
</body>
</html>
