<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background: #f5f5f5; }
        .container { max-width: 600px; margin: 40px auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #6C5CE7, #a855f7); padding: 32px 24px; text-align: center; }
        .header h1 { color: #fff; margin: 0; font-size: 24px; }
        .body { padding: 32px 24px; }
        .amount { font-size: 36px; font-weight: 700; color: #6C5CE7; text-align: center; margin: 16px 0; }
        .detail { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #f0f0f0; }
        .detail:last-child { border-bottom: none; }
        .label { color: #888; }
        .value { font-weight: 600; color: #333; }
        .button { display: inline-block; padding: 12px 32px; background: #6C5CE7; color: #fff; text-decoration: none; border-radius: 8px; font-weight: 600; margin-top: 24px; }
        .footer { text-align: center; padding: 24px; color: #aaa; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Wallet Top-Up Confirmed</h1>
        </div>
        <div class="body">
            <p>Hi {{ $user->full_name ?? $user->username }},</p>
            <p>Your wallet has been topped up successfully.</p>

            <div class="amount">GH₵ {{ number_format($topup->base_amount, 2) }}</div>

            <div class="detail">
                <span class="label">Reference</span>
                <span class="value">{{ $topup->reference }}</span>
            </div>
            <div class="detail">
                <span class="label">Processing Fee</span>
                <span class="value">GH₵ {{ number_format($topup->processing_fee, 2) }}</span>
            </div>
            <div class="detail">
                <span class="label">Total Charged</span>
                <span class="value">GH₵ {{ number_format($topup->total_amount, 2) }}</span>
            </div>
            <div class="detail">
                <span class="label">Payment Method</span>
                <span class="value">Mobile Money</span>
            </div>
            <div class="detail">
                <span class="label">New Balance</span>
                <span class="value">GH₵ {{ number_format($newBalance, 2) }}</span>
            </div>

            <div style="text-align: center;">
                <a href="{{ url('/agent/wallet') }}" class="button">View Wallet</a>
            </div>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} Affordable Bundle. All rights reserved.
        </div>
    </div>
</body>
</html>
