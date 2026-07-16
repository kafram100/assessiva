<?php

namespace App\Mail;

use App\Models\User;
use App\Models\WalletTopup;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WalletTopupMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public WalletTopup $topup,
        public User $user,
        public float $newBalance,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Wallet Top-Up Confirmation - Affordable Bundle',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.wallet-topup',
        );
    }
}
