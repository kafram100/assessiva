<?php

namespace App\Filament\Pages;

use App\Models\AgentOrder;
use App\Models\AgentPackage;
use App\Models\ApiConfig;
use App\Models\Order;
use App\Models\StoreOrder;
use App\Models\SubAgent;
use App\Models\User;
use App\Services\DataBundle\DataBundleRouter;
use App\Services\ExternalAPIService;
use Carbon\Carbon;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class Dashboard extends BaseDashboard
{
    protected static string $view = 'filament.pages.dashboard';

    public static function parseSizeToGB(string $size): float
    {
        $size = strtoupper(trim($size));
        if (str_contains($size, 'GB')) {
            return (float) str_replace('GB', '', $size);
        }
        if (str_contains($size, 'MB')) {
            return (float) str_replace('MB', '', $size) / 1024;
        }
        return 0;
    }

    const BASE_PRICE_PER_GB = 3.8;

    public static function calcProfit(Carbon $start, Carbon $end): array
    {
        $profit = 0;
        $orderCount = 0;

        // StoreOrder — use base_cost from provider response when available
        $storeOrders = StoreOrder::whereIn('status', ['completed', 'delivered'])
            ->whereBetween('created_at', [$start, $end])
            ->select('sold_price', 'package_size_gb', 'base_cost');
        foreach ($storeOrders->cursor() as $o) {
            if ($o->base_cost !== null) {
                $profit += (float) $o->sold_price - (float) $o->base_cost;
            } else {
                $cost = (float) $o->package_size_gb * self::BASE_PRICE_PER_GB;
                $profit += (float) $o->sold_price - $cost;
            }
            $orderCount++;
        }

        // AgentOrder — use base_cost from provider response when available
        $agentOrders = AgentOrder::whereIn('status', ['completed', 'delivered'])
            ->whereBetween('created_at', [$start, $end])
            ->select('amount', 'package_size', 'base_cost');
        foreach ($agentOrders->cursor() as $o) {
            if ($o->base_cost !== null) {
                $profit += (float) $o->amount - (float) $o->base_cost;
            } else {
                $gb = self::parseSizeToGB($o->package_size);
                $cost = $gb * self::BASE_PRICE_PER_GB;
                $profit += (float) $o->amount - $cost;
            }
            $orderCount++;
        }

        // Order (public/customer) — use base_cost from provider response when available
        $orders = Order::whereIn('status', ['completed', 'delivered'])
            ->whereBetween('created_at', [$start, $end])
            ->select('amount', 'package_size', 'base_cost');
        foreach ($orders->cursor() as $o) {
            if ($o->base_cost !== null) {
                $profit += (float) $o->amount - (float) $o->base_cost;
            } else {
                $gb = self::parseSizeToGB($o->package_size);
                $cost = $gb * self::BASE_PRICE_PER_GB;
                $profit += (float) $o->amount - $cost;
            }
            $orderCount++;
        }

        return ['profit' => round($profit, 2), 'orders' => $orderCount];
    }

    public static function getProfitBreakdowns(): array
    {
        return Cache::remember('profit_breakdowns', 60, function () {
            $daily   = array_fill_keys(['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'], 0);
            $monthly = array_fill_keys(['January','February','March','April','May','June','July','August','September','October','November','December'], 0);
            $yearly  = [];

            $now          = now();
            $startOfWeek  = $now->copy()->startOfWeek();
            $startOfMonth = $now->copy()->startOfMonth();
            $startOfYear  = $now->copy()->startOfYear();

            // Build week slots dynamically — covers 4-week AND 5-week months
            $weekly       = [];
            $weeklyRanges = [];
            $weekSlot     = 1;
            $cursor       = $startOfMonth->copy();
            $endOfMonth   = $startOfMonth->copy()->endOfMonth();

            while ($cursor->lte($endOfMonth)) {
                $weekStart = $cursor->copy();
                $weekEnd   = $cursor->copy()->endOfWeek()->min($endOfMonth);
                $label     = 'Week ' . $weekSlot . ' (' . $weekStart->format('M j') . '–' . $weekEnd->format('j') . ')';
                $weekly[$label]       = 0;
                $weeklyRanges[$label] = [$weekStart->copy(), $weekEnd->copy()];
                $cursor = $weekEnd->copy()->addDay()->startOfDay();
                $weekSlot++;
            }

            $processOrder = function ($o, $profit) use (&$daily, &$weekly, &$monthly, &$yearly, $weeklyRanges, $startOfWeek, $startOfMonth, $startOfYear) {
                $date = $o->created_at;
                if (!$date) return;

                // Yearly (all time)
                $year = $date->format('Y');
                if (!isset($yearly[$year])) $yearly[$year] = 0;
                $yearly[$year] += $profit;

                // Monthly (this year)
                if ($date->gte($startOfYear)) {
                    $month = $date->format('F');
                    if (isset($monthly[$month])) {
                        $monthly[$month] += $profit;
                    }
                }

                // Weekly (this month) — match by actual date range
                if ($date->gte($startOfMonth)) {
                    foreach ($weeklyRanges as $label => [$wStart, $wEnd]) {
                        if ($date->between($wStart, $wEnd)) {
                            $weekly[$label] += $profit;
                            break;
                        }
                    }
                }

                // Daily (this week)
                if ($date->gte($startOfWeek)) {
                    $day = $date->format('l');
                    if (isset($daily[$day])) {
                        $daily[$day] += $profit;
                    }
                }
            };


            foreach (StoreOrder::whereIn('status', ['completed', 'delivered'])->select('sold_price', 'package_size_gb', 'base_cost', 'created_at')->cursor() as $o) {
                $profit = 0;
                if ($o->base_cost !== null) {
                    $profit = (float) $o->sold_price - (float) $o->base_cost;
                } else {
                    $cost = (float) $o->package_size_gb * self::BASE_PRICE_PER_GB;
                    $profit = (float) $o->sold_price - $cost;
                }
                $processOrder($o, $profit);
            }

            foreach (AgentOrder::whereIn('status', ['completed', 'delivered'])->select('amount', 'package_size', 'base_cost', 'created_at')->cursor() as $o) {
                $profit = 0;
                if ($o->base_cost !== null) {
                    $profit = (float) $o->amount - (float) $o->base_cost;
                } else {
                    $gb = self::parseSizeToGB((string)$o->package_size);
                    $cost = $gb * self::BASE_PRICE_PER_GB;
                    $profit = (float) $o->amount - $cost;
                }
                $processOrder($o, $profit);
            }

            foreach (Order::whereIn('status', ['completed', 'delivered'])->select('amount', 'package_size', 'base_cost', 'created_at')->cursor() as $o) {
                $profit = 0;
                if ($o->base_cost !== null) {
                    $profit = (float) $o->amount - (float) $o->base_cost;
                } else {
                    $gb = self::parseSizeToGB((string)$o->package_size);
                    $cost = $gb * self::BASE_PRICE_PER_GB;
                    $profit = (float) $o->amount - $cost;
                }
                $processOrder($o, $profit);
            }

            // Format outputs
            foreach ($daily as $k => $v) $daily[$k] = round($v, 2);
            foreach ($weekly as $k => $v) $weekly[$k] = round($v, 2);
            foreach ($monthly as $k => $v) $monthly[$k] = round($v, 2);
            foreach ($yearly as $k => $v) $yearly[$k] = round($v, 2);

            krsort($yearly);

            return [
                'daily' => $daily,
                'weekly' => $weekly,
                'monthly' => $monthly,
                'yearly' => $yearly,
            ];
        });
    }

    protected function getViewData(): array
    {
        $totalData = (float) Order::whereIn('status', ['completed', 'delivered'])->sum('package_size');
        $agentData = (float) AgentOrder::whereIn('status', ['completed', 'delivered'])->sum('package_size');

        $totalAgents = User::where('role', 'agent')->count();
        $totalDealers = User::where('role', 'dealer')->count();
        try {
            $SubAgents = SubAgent::count();
            $SubAgentBalances = (float) SubAgent::sum('balance');
        } catch (\Throwable $e) {
            $SubAgents = 0;
            $SubAgentBalances = 0;
        }
        $agentBalances = (float) User::where('role', 'agent')->sum('balance');
        $dealerBalances = (float) User::where('role', 'dealer')->sum('balance');
        $todayUsage = (float) AgentOrder::whereDate('created_at', today())->sum('amount');
        $todayDebitCount = AgentOrder::whereDate('created_at', today())->count();

        $allPending = Order::where('status', 'pending')->count()
            + AgentOrder::where('status', 'pending')->count()
            + StoreOrder::where('status', 'pending')->count();
        $allProcessing = Order::whereIn('status', ['processing', 'verified'])->count()
            + AgentOrder::whereIn('status', ['processing', 'verified'])->count()
            + StoreOrder::whereIn('status', ['processing', 'verified'])->count();
        $allCompleted = Order::whereIn('status', ['completed', 'delivered'])->count()
            + AgentOrder::whereIn('status', ['completed', 'delivered'])->count()
            + StoreOrder::whereIn('status', ['completed', 'delivered'])->count();
        $allFailed = Order::whereIn('status', ['failed', 'cancelled'])->count()
            + AgentOrder::whereIn('status', ['failed', 'cancelled'])->count()
            + StoreOrder::whereIn('status', ['failed', 'cancelled'])->count();

        $agents = User::whereIn('role', ['agent', 'dealer'])
            ->withCount('agentOrders')
            ->withSum('agentOrders', 'amount')
            ->latest()
            ->get()
            ->map(fn ($user) => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'full_name' => $user->full_name ?? $user->username,
                'balance' => (float) $user->balance,
                'role' => $user->role,
                'status' => $user->status,
                'total_orders' => $user->agent_orders_count,
                'sub_agents_count' => 0,
                'total_value' => (float) $user->agent_orders_sum_amount,
                'last_order_date' => optional($user->agentOrders()->latest()->first())->created_at,
            ]);

        $today = now()->startOfDay();
        $weekStart = now()->startOfWeek();
        $monthStart = now()->startOfMonth();
        $yearStart = now()->startOfYear();
        $dayEnd = now()->endOfDay();

        $apiBalance = Cache::remember('api_wallet_balance', 60, function () {
            // Try the active data provider via DataBundleRouter first
            try {
                $router = app(DataBundleRouter::class);
                $provider = $router->getActiveProvider();
                if ($provider !== DataBundleRouter::PROVIDER_LEGACY && $router->isCredentialsConfigured()) {
                    $result = $router->fetchBalance();
                    if (!empty($result['success']) && $result['balance'] !== null) {
                        return $result;
                    }
                }
            } catch (\Throwable $e) {
                // Fall through to legacy approach
            }

            // Fallback: legacy ApiConfig-based balance endpoints
            $configs = ApiConfig::where('is_active', true)
                ->whereNotNull('balance_endpoint')
                ->get();
            foreach ($configs as $config) {
                $service = new ExternalAPIService($config->network_type);
                $result = $service->fetchBalance();
                if ($result['success']) {
                    return $result;
                }
            }
            return ['success' => false, 'balance' => null, 'error' => 'API wallet balance unavailable'];
        });
        $apiWalletBalance = $apiBalance['success'] ? $apiBalance['balance'] : null;
        $apiBalanceError = $apiBalance['success'] ? null : ($apiBalance['error'] ?? 'Unavailable');

        $totalPeople = $totalAgents + $totalDealers + $SubAgents;
        $agentDealerBalances = $agentBalances + $dealerBalances;
        $todayAgentDealerSpend = $todayUsage;
        $agentDealerLeft = $agentDealerBalances - $todayAgentDealerSpend;

        $todaySubAgentSpend = (float) StoreOrder::whereDate('created_at', today())
            ->whereNotNull('sub_agent_id')
            ->sum('sold_price');
        $SubAgentLeft = $SubAgentBalances - $todaySubAgentSpend;

        // MTN Mashup orders from all sources
        $mtnMashupOrders = collect();

        // AgentOrders with manual_fulfillment package
        AgentOrder::with('agent')
            ->whereHas('package', fn($q) => $q->where('manual_fulfillment', true))
            ->latest()
            ->limit(50)
            ->get()
            ->each(function ($o) use ($mtnMashupOrders) {
                $mtnMashupOrders->push([
                    'id' => $o->id,
                    'source' => ($o->agent?->role ?? 'agent') === 'dealer' ? 'Dealer Order' : 'Agent Order',
                    'agent_name' => $o->agent?->username ?? 'N/A',
                    'package_name' => $o->package?->package_name ?? $o->package_size,
                    'amount' => (float) $o->amount,
                    'beneficiary' => $o->beneficiary_number,
                    'status' => $o->status,
                    'created_at' => $o->created_at,
                ]);
            });

        // Public Orders with package_name = 'MTN Mashup'
        Order::where('package_name', 'MTN Mashup')
            ->latest()
            ->limit(50)
            ->get()
            ->each(function ($o) use ($mtnMashupOrders) {
                $mtnMashupOrders->push([
                    'id' => $o->id,
                    'source' => 'Public Customer',
                    'agent_name' => $o->customer_name ?? 'N/A',
                    'package_name' => $o->package_name,
                    'amount' => (float) $o->amount,
                    'beneficiary' => $o->beneficiary_number ?? $o->phone ?? 'N/A',
                    'status' => $o->status,
                    'created_at' => $o->created_at,
                ]);
            });

        $mtnMashupOrders = $mtnMashupOrders->sortByDesc('created_at')->take(50)->values();

        $mtnMashupOrderKeys = $mtnMashupOrders->map(fn($o) => [
            'key' => $o['source'] . '-' . $o['id'],
            'id' => $o['id'],
            'source' => $o['source'],
        ]);

        return [
            'totalData' => $totalData + $agentData,
            'totalAgents' => $totalAgents,
            'SubAgents' => $SubAgents,
            'totalBalance' => $agentBalances + $SubAgentBalances,
            'todayUsage' => $todayUsage,
            'todayDebitCount' => $todayDebitCount,
            'allPending' => $allPending,
            'allProcessing' => $allProcessing,
            'allCompleted' => $allCompleted,
            'allFailed' => $allFailed,
            'agents' => $agents,
            'lastLogin' => now()->format('M d, Y h:i A'),
            'profitToday' => self::calcProfit($today, $dayEnd),
            'profitWeek' => self::calcProfit($weekStart, $dayEnd),
            'profitMonth' => self::calcProfit($monthStart, $dayEnd),
            'profitYear' => self::calcProfit($yearStart, $dayEnd),
            'apiWalletBalance' => $apiWalletBalance,
            'apiBalanceError' => $apiBalanceError,
            'totalDealers' => $totalDealers,
            'totalPeople' => $totalPeople,
            'agentDealerBalances' => $agentDealerBalances,
            'todayAgentDealerSpend' => $todayAgentDealerSpend,
            'agentDealerLeft' => $agentDealerLeft,
            'SubAgentBalances' => $SubAgentBalances,
            'todaySubAgentSpend' => $todaySubAgentSpend,
            'SubAgentLeft' => $SubAgentLeft,
            'agentBalances' => $agentBalances,
            'dealerBalances' => $dealerBalances,
            'mtnMashupOrders' => $mtnMashupOrders,
            'mtnMashupOrderKeys' => $mtnMashupOrderKeys,
            'breakdowns' => self::getProfitBreakdowns(),
        ];
    }
}
