<x-filament-panels::page>
    <style>
        .stat-card { transition: all 0.25s ease; }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 12px 30px -8px rgba(0,0,0,.15); }
        .stat-icon { transition: all 0.3s ease; }
        .stat-card:hover .stat-icon { transform: scale(1.1) rotate(-5deg); }
        .fade-in { animation: fadeIn 0.5s ease both; }
        @keyframes fadeIn { from{opacity:0} to{opacity:1} }
    </style>

    {{-- Header --}}
    <div class="rounded-2xl overflow-hidden mb-6" style="background: linear-gradient(135deg, #1B628C, #0F9F95);">
        <div class="p-6 md:p-8">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 bg-white/20 backdrop-blur rounded-2xl flex items-center justify-center shadow-lg shrink-0">
                    <i class="fas fa-currency-dollar text-white text-2xl"></i>
                </div>
                <div>
                    <h1 class="text-2xl md:text-3xl font-bold text-white">Profit Report</h1>
                    <p class="text-white/70 mt-1">Detailed profit breakdown by order type and period</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Period Tabs --}}
    @php
        $periods = [
            'Today' => $todayByType,
            'This Week' => $weekByType,
            'This Month' => $monthByType,
            'This Year' => $yearByType,
            'All Time' => $allTime,
        ];
        $types = ['store' => 'Store Orders', 'agent' => 'Agent Orders', 'public' => 'Public Orders'];
        $typeColors = ['store' => 'hsl(204,43%,55%)', 'agent' => 'hsl(176,75%,36%)', 'public' => 'hsl(39,100%,39%)'];
    @endphp

    @foreach($periods as $periodLabel => $periodData)
        <div class="mb-6">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-3">{{ $periodLabel }}</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach($types as $key => $label)
                    @php $data = $periodData[$key]; @endphp
                    <div class="stat-card rounded-xl p-4 border fade-in" style="background:hsla(0,0%,0%,0.02);border-color:{{ $typeColors[$key] }}33">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-xs font-semibold uppercase tracking-wider" style="color:{{ $typeColors[$key] }}">{{ $label }}</p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white leading-tight mt-1">GHS {{ number_format($data['profit'], 2) }}</p>
                                <p class="text-xs mt-0.5 text-gray-500 dark:text-gray-400">
                                    GHS {{ number_format($data['revenue'], 2) }} revenue &middot; {{ $data['orders'] }} orders
                                </p>
                            </div>
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 stat-icon" style="background:{{ $typeColors[$key] }}15">
                                <i class="fas fa-shopping-bag" style="color:{{ $typeColors[$key] }};font-size:14px"></i>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach

    {{-- Monthly Trend --}}
    <div class="bg-white/60 backdrop-blur-sm dark:bg-gray-800/80 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Monthly Profit Trend (12 Months)</h3>
        </div>
        <div class="p-6">
            <div class="flex items-center gap-6 mb-4 text-sm">
                @foreach($types as $key => $label)
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded" style="background:{{ $typeColors[$key] }}"></span>
                        <span class="text-gray-600 dark:text-gray-300">{{ $label }}</span>
                    </div>
                @endforeach
            </div>
            <div class="flex items-end gap-2" style="height:200px">
                @php $maxProfit = max($monthlyProfits->max('profit'), 1); @endphp
                @foreach($monthlyProfits as $month)
                    <div class="flex-1 flex flex-col items-center gap-0.5 h-full justify-end">
                        <div class="w-full rounded-t flex flex-col-reverse" style="height:{{ max(($month['profit'] / $maxProfit) * 100, 1) }}%;min-height:4px">
                            @php $p = max($month['profit'], 0.01); @endphp
                            <div class="w-full" style="height:{{ ($month['store'] / $p) * 100 }}%;background:hsl(204,43%,55%)"></div>
                            <div class="w-full" style="height:{{ ($month['agent'] / $p) * 100 }}%;background:hsl(176,75%,36%)"></div>
                            <div class="w-full" style="height:{{ ($month['public'] / $p) * 100 }}%;background:hsl(39,100%,39%)"></div>
                        </div>
                        <span class="text-[8px] text-gray-400 mt-1 whitespace-nowrap">{{ $month['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Agent Rankings --}}
    <div class="bg-white/60 backdrop-blur-sm dark:bg-gray-800/80 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Agent / Dealer Rankings</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-700">
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">#</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">User</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Role</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Orders</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Total Sales</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                    @forelse($agentRankings as $i => $agent)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/20 transition-colors">
                            <td class="px-6 py-3 whitespace-nowrap">
                                <span class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold
                                    {{ $i === 0 ? 'bg-yellow-100 text-yellow-700' : ($i === 1 ? 'bg-gray-100 text-gray-600' : ($i === 2 ? 'bg-orange-100 text-orange-700' : 'bg-blue-50 text-blue-600')) }}">
                                    {{ $i + 1 }}
                                </span>
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap font-semibold text-gray-900 dark:text-white">{{ $agent['username'] }}</td>
                            <td class="px-6 py-3 whitespace-nowrap">
                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium
                                    {{ $agent['role'] === 'dealer' ? 'bg-orange-100 text-orange-700' : 'bg-teal-100 text-teal-700' }}">
                                    {{ $agent['role'] }}
                                </span>
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-gray-600 dark:text-gray-300">{{ $agent['order_count'] }}</td>
                            <td class="px-6 py-3 whitespace-nowrap text-right font-bold" style="color:hsl(176,75%,36%)">GHS {{ number_format($agent['total_sales'], 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-gray-400">No sales data yet</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
