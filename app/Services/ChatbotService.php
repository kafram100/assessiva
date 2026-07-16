<?php

namespace App\Services;

use App\Models\AgentPackage;
use App\Models\Order;
use App\Models\AgentOrder;
use App\Models\StoreOrder;

class ChatbotService
{
    public function respond(string $message): string
    {
        $message = strtolower(trim($message));

        if ($this->isGreeting($message)) {
            return $this->greetingResponse();
        }

        if ($this->isHelpIntent($message)) {
            return $this->helpResponse();
        }

        if ($this->isBuyIntent($message)) {
            return $this->buyResponse();
        }

        if ($this->isTrackIntent($message)) {
            return $this->trackResponse();
        }

        if ($this->isPaymentIntent($message)) {
            return $this->paymentResponse();
        }

        if ($this->isHowToIntent($message)) {
            return $this->howToResponse();
        }

        $network = $this->detectNetwork($message);

        if ($network) {
            return $this->networkPricesResponse($network);
        }

        return $this->fallbackResponse();
    }

    private function isGreeting(string $message): bool
    {
        $greetings = ['hello', 'hi', 'hey', 'good morning', 'good afternoon', 'good evening', 'yo', 'sup', 'hola'];
        foreach ($greetings as $g) {
            if ($message === $g || str_starts_with($message, $g)) return true;
        }
        return false;
    }

    private function isHelpIntent(string $message): bool
    {
        return in_array($message, ['help', 'support', 'what can you do', 'options', 'menu', 'commands']);
    }

    private function isBuyIntent(string $message): bool
    {
        return str_contains($message, 'buy') || str_contains($message, 'purchase') || $message === 'order';
    }

    private function isTrackIntent(string $message): bool
    {
        return str_contains($message, 'track') || str_contains($message, 'status') || str_contains($message, 'my order');
    }

    private function isPaymentIntent(string $message): bool
    {
        return str_contains($message, 'payment') || str_contains($message, 'pay') || str_contains($message, 'wallet') || str_contains($message, 'top up') || str_contains($message, 'topup') || str_contains($message, 'deposit');
    }

    private function isHowToIntent(string $message): bool
    {
        return str_contains($message, 'how to') || str_contains($message, 'how do') || str_contains($message, 'how can');
    }

    private function greetingResponse(): string
    {
        return "Hello! 👋 Welcome to AirLinks.\n\nI can help you with:\n• 📊 Data prices — type the network name\n• 💰 How to buy data\n• 📦 Order tracking info\n• 💳 Payment & wallet info\n\nWhat would you like to know?";
    }

    private function helpResponse(): string
    {
        return "Here's what I can help with:\n\n📊 Data prices — type \"MTN\", \"Telecel\", or \"AirtelTigo\"\n💰 How to buy — type \"how to buy\"\n📦 Track order — type \"track order\"\n💳 Payments — type \"payment info\"\n\nOr just ask your question!";
    }

    private function buyResponse(): string
    {
        return "How to buy data on AirLinks:\n\n1️⃣ Create an account or log in\n2️⃣ Top up your wallet (Paystack or MoMo)\n3️⃣ Go to Buy Data\n4️⃣ Enter phone number and select a package\n5️⃣ Confirm your order\n\nFor public purchases, visit the homepage and buy directly with Paystack.\n\nWant to see prices? Type a network name (MTN, Telecel, or AirtelTigo).";
    }

    private function trackResponse(): string
    {
        return "To track your order:\n\n1️⃣ Go to the Track page from the menu\n2️⃣ Enter your order reference or tracking key\n\nOrders typically process within 1–5 minutes. If your order is stuck, contact support with your order ID.";
    }

    private function paymentResponse(): string
    {
        return "Payment info:\n\n💳 We accept Paystack (card/bank) and Mobile Money\n💰 2% processing fee applies for Paystack\n🆓 No fee for Moolre payments\n\nTo top up your wallet:\n1. Go to Wallet in your dashboard\n2. Enter amount and choose a payment method\n3. Complete the payment\n\nNeed help? Contact our support team.";
    }

    private function howToResponse(): string
    {
        return "To get started:\n\n1️⃣ Sign up for a free account\n2️⃣ Top up your wallet\n3️⃣ Go to Buy Data and select a package\n4️⃣ Enter the beneficiary phone number\n5️⃣ Confirm — data is delivered instantly!\n\nOr visit our public store for quick purchases without an account.";
    }

    private function networkPricesResponse(string $network): string
    {
        $packages = AgentPackage::where('is_active', true)
            ->where('network_type', $network)
            ->where(function ($q) {
                $q->where('role', 'all')->orWhere('role', 'agent');
            })
            ->orderBy('sort_order')
            ->get();

        if ($packages->isEmpty()) {
            return "Sorry, no packages found for {$network}. Please check back later or try another network.";
        }

        $response = "📦 {$network} Data Prices:\n\n";
        foreach ($packages as $pkg) {
            $size = $pkg->package_size_gb
                ? $pkg->package_size_gb . ' GB'
                : $pkg->package_size_mb . ' MB';
            $extras = [];
            if ($pkg->voice_minutes) $extras[] = "{$pkg->voice_minutes} min calls";
            if ($pkg->non_expiring) $extras[] = "non-expiring";
            $extraStr = $extras ? ' (' . implode(', ', $extras) . ')' : '';
            $response .= "• {$size}{$extraStr} — GH₵ " . number_format($pkg->price, 2) . "\n";
        }

        $response .= "\nWant to buy? Go to Buy Data or type \"how to buy\".";

        return $response;
    }

    private function fallbackResponse(): string
    {
        return "I'm not sure I understand. Here's what I can help with:\n\n📊 Type a network name — \"MTN\", \"Telecel\", or \"AirtelTigo\" for prices\n💰 Type \"how to buy\" for instructions\n📦 Type \"track order\" for tracking info\n💳 Type \"payment info\" for wallet details\n❓ Type \"help\" for all options";
    }

    private function detectNetwork(string $message): ?string
    {
        if (str_contains($message, 'mtn')) return 'MTN';
        if (str_contains($message, 'telecel') || str_contains($message, 'vodafone')) return 'VODAFONE';
        if (str_contains($message, 'airtel') || str_contains($message, 'tigo')) return 'AIRTELTIGO';

        return null;
    }
}
