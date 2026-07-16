<x-filament-panels::page>
    <style>
        .stat-card { transition: all 0.25s ease; }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 12px 30px -8px rgba(0,0,0,.15); }
        .stat-icon { transition: all 0.3s ease; }
        .stat-card:hover .stat-icon { transform: scale(1.1) rotate(-5deg); }
        .fade-in { animation: fadeIn 0.5s ease both; }
        @keyframes fadeIn { from{opacity:0} to{opacity:1} }
        .chart-bar { transition: height 0.6s ease; }
    </style>

    {{-- Header --}}
    <div class="rounded-2xl overflow-hidden mb-6" style="background: linear-gradient(135deg, #0F9F95, #1B628C);">
        <div class="p-6 md:p-8">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 bg-white/20 backdrop-blur rounded-2xl flex items-center justify-center shadow-lg shrink-0">
                    <i class="fas fa-chart-pie text-white text-2xl"></i>
                </div>
                <div>
                    <h1 class="text-2xl md:text-3xl font-bold text-white">Sales Analytics</h1>
                    <p class="text-white/70 mt-1">Comprehensive overview of your data bundle sales performance</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Period Profit Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
        <div class="stat-card rounded-xl p-4 border fade-in" style="background:hsla(270,70%,60%,0.06);border-color:hsla(270,70%,60%,0.18)">
            <div class="flex items-start justify-between gap-2">
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-wider" style="color:hsl(270,70%,55%)">Today</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white leading-tight mt-1">GHS {{ number_format($todayStats['profit'], 2) }}</p>
                    <p class="text-xs mt-0.5" style="color:hsl(270,60%,65%)">{{ $todayStats['orders'] }} orders &middot; GHS {{ number_format($todayStats['revenue'], 2) }} revenue</p>
                </div>
                <div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0 stat-icon" style="background:hsla(270,70%,60%,0.12)">
                    <i class="fas fa-chart-line" style="color:hsl(270,50%,50%)"></i>
                </div>
            </div>
        </div>

        <div class="stat-card rounded-xl p-4 border fade-in" style="background:hsla(204,43%,55%,0.08);border-color:hsla(204,43%,55%,0.2)">
            <div class="flex items-start justify-between gap-2">
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-wider" style="color:hsl(204,60%,60%)">This Week</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white leading-tight mt-1">GHS {{ number_format($weekStats['profit'], 2) }}</p>
                    <p class="text-xs mt-0.5" style="color:hsl(204,50%,68%)">{{ $weekStats['orders'] }} orders &middot; GHS {{ number_format($weekStats['revenue'], 2) }} revenue</p>
                </div>
                <div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0 stat-icon" style="background:hsla(204,43%,55%,0.12)">
                    <i class="fas fa-calendar-week" style="color:hsl(204,43%,55%)"></i>
                </div>
            </div>
        </div>

        <div class="stat-card rounded-xl p-4 border fade-in" style="background:hsla(39,80%,62%,0.08);border-color:hsla(39,80%,62%,0.2)">
            <div class="flex items-start justify-between gap-2">
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-wider" style="color:hsl(39,80%,58%)">This Month</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white leading-tight mt-1">GHS {{ number_format($monthStats['profit'], 2) }}</p>
                    <p class="text-xs mt-0.5" style="color:hsl(39,60%,64%)">{{ $monthStats['orders'] }} orders &middot; GHS {{ number_format($monthStats['revenue'], 2) }} revenue</p>
                </div>
                <div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0 stat-icon" style="background:hsla(39,80%,62%,0.12)">
                    <i class="fas fa-calendar-alt" style="color:hsl(39,100%,39%)"></i>
                </div>
            </div>
        </div>

        <div class="stat-card rounded-xl p-4 border fade-in" style="background:hsla(142,71%,45%,0.06);border-color:hsla(142,71%,45%,0.18)">
            <div class="flex items-start justify-between gap-2">
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-wider" style="color:hsl(142,60%,50%)">This Year</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white leading-tight mt-1">GHS {{ number_format($yearStats['profit'], 2) }}</p>
                    <p class="text-xs mt-0.5" style="color:hsl(142,60%,65%)">{{ $yearStats['orders'] }} orders &middot; GHS {{ number_format($yearStats['revenue'], 2) }} revenue</p>
                </div>
                <div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0 stat-icon" style="background:hsla(142,71%,45%,0.12)">
                    <i class="fas fa-calendar-check" style="color:hsl(142,71%,45%)"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Summary Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
        <div class="stat-card rounded-xl p-4 border fade-in" style="background:hsla(204,43%,55%,0.06);border-color:hsla(204,43%,55%,0.18)">
            <div class="flex items-start justify-between gap-2">
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-wider" style="color:hsl(204,60%,60%)">Total Orders</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white leading-tight mt-1">{{ $totalOrders }}</p>
                    <p class="text-xs mt-0.5" style="color:hsl(204,50%,68%)">{{ $completedOrders }} completed, {{ $pendingOrders }} pending</p>
                </div>
                <div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0" style="background:hsla(204,43%,55%,0.12)">
                    <i class="fas fa-shopping-bag" style="color:hsl(204,43%,55%)"></i>
                </div>
            </div>
        </div>
        <div class="stat-card rounded-xl p-4 border fade-in" style="background:hsla(142,71%,45%,0.06);border-color:hsla(142,71%,45%,0.18)">
            <div class="flex items-start justify-between gap-2">
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-wider" style="color:hsl(142,60%,50%)">Agents</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white leading-tight mt-1">{{ $totalAgents }}</p>
                    <p class="text-xs mt-0.5" style="color:hsl(142,60%,65%)">GHS {{ number_format($agentBalances, 2) }} combined balance</p>
                </div>
                <div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0" style="background:hsla(142,71%,45%,0.12)">
                    <i class="fas fa-users" style="color:hsl(142,71%,45%)"></i>
                </div>
            </div>
        </div>
        <div class="stat-card rounded-xl p-4 border fade-in" style="background:hsla(39,80%,62%,0.06);border-color:hsla(39,80%,62%,0.18)">
            <div class="flex items-start justify-between gap-2">
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-wider" style="color:hsl(39,80%,58%)">Dealers</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white leading-tight mt-1">{{ $totalDealers }}</p>
                    <p class="text-xs mt-0.5" style="color:hsl(39,60%,64%)">GHS {{ number_format($dealerBalances, 2) }} combined balance</p>
                </div>
                <div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0" style="background:hsla(39,80%,62%,0.12)">
                    <i class="fas fa-user-tie" style="color:hsl(39,100%,39%)"></i>
                </div>
            </div>
        </div>
        <div class="stat-card rounded-xl p-4 border fade-in" style="background:hsla(0,72%,51%,0.05);border-color:hsla(0,72%,51%,0.15)">
            <div class="flex items-start justify-between gap-2">
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-wider" style="color:hsl(0,70%,55%)">Total Revenue (This Year)</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white leading-tight mt-1">GHS {{ number_format($yearStats['revenue'], 2) }}</p>
                    <p class="text-xs mt-0.5" style="color:hsl(0,60%,62%)">GHS {{ number_format($yearStats['profit'], 2) }} net profit</p>
                </div>
                <div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0" style="background:hsla(0,72%,51%,0.12)">
                    <i class="fas fa-coins" style="color:hsl(0,72%,51%)"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Daily Chart --}}
    <div class="bg-white/60 backdrop-blur-sm dark:bg-gray-800/80 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">30-Day Revenue & Profit</h3>
        </div>
        <div class="p-6">
            <div class="flex items-center gap-6 mb-4 text-sm">
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full" style="background:hsl(176,75%,36%)"></span>
                    <span class="text-gray-600 dark:text-gray-300">Revenue</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full" style="background:hsl(39,100%,39%)"></span>
                    <span class="text-gray-600 dark:text-gray-300">Profit</span>
                </div>
            </div>
            <div class="flex items-end gap-1.5" style="height:200px">
                @php $maxVal = max($dailyStats->max('revenue'), 1); @endphp
                @foreach($dailyStats as $day)
                    <div class="flex-1 flex flex-col items-center gap-0.5 h-full justify-end">
                        <div class="w-full rounded-t" style="height:{{ max(($day['revenue'] / $maxVal) * 100, 1) }}%;background:hsl(176,75%,36%);min-height:2px;opacity:0.8"></div>
                        <div class="w-full rounded-t" style="height:{{ max(($day['profit'] / $maxVal) * 100, 0) }}%;background:hsl(39,100%,39%);min-height:1px;opacity:0.9"></div>
                        @if($loop->index % 5 == 0)
                            <span class="text-[8px] text-gray-400 mt-0.5 -rotate-45 origin-left whitespace-nowrap">{{ $day['date'] }}</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Recent Orders & Top Agents --}}
    <div class="grid lg:grid-cols-2 gap-6 mb-6">
        {{-- Recent Orders --}}
        <div class="bg-white/60 backdrop-blur-sm dark:bg-gray-800/80 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Recent Orders</h3>
            </div>
            <div class="divide-y divide-gray-100 dark:divide-gray-700/50">
                @forelse($recentOrders as $order)
                    <div class="px-6 py-3 flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $order['network_type'] }} - {{ $order['package_size'] }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $order['agent'] }} &middot; {{ $order['created_at'] }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold" style="color:hsl(176,75%,36%)">GHS {{ number_format($order['amount'], 2) }}</p>
                            <span class="inline-block text-xs px-2 py-0.5 rounded-full font-medium
                                @if($order['status'] === 'completed' || $order['status'] === 'delivered') bg-green-100 text-green-700
                                @elseif($order['status'] === 'pending') bg-yellow-100 text-yellow-700
                                @elseif($order['status'] === 'failed') bg-red-100 text-red-700
                                @else bg-blue-100 text-blue-700 @endif">
                                {{ $order['status'] }}
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="px-6 py-8 text-center text-gray-400">No orders yet</div>
                @endforelse
            </div>
        </div>

        {{-- Top Performing Agents --}}
        <div class="bg-white/60 backdrop-blur-sm dark:bg-gray-800/80 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Top Performing Agents</h3>
            </div>
            <div class="divide-y divide-gray-100 dark:divide-gray-700/50">
                @forelse($topAgents as $i => $agent)
                    <div class="px-6 py-3 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <span class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold shrink-0
                                {{ $i === 0 ? 'bg-yellow-100 text-yellow-700' : ($i === 1 ? 'bg-gray-100 text-gray-600' : ($i === 2 ? 'bg-orange-100 text-orange-700' : 'bg-blue-50 text-blue-600')) }}">
                                {{ $i + 1 }}
                            </span>
                            <div>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $agent['username'] }}</p>
                                <span class="inline-block text-xs px-2 py-0.5 rounded-full font-medium
                                    {{ $agent['role'] === 'dealer' ? 'bg-orange-100 text-orange-700' : 'bg-teal-100 text-teal-700' }}">
                                    {{ $agent['role'] }}
                                </span>
                            </div>
                        </div>
                        <p class="text-sm font-bold" style="color:hsl(176,75%,36%)">GHS {{ number_format($agent['total_sales'], 2) }}</p>
                    </div>
                @empty
                    <div class="px-6 py-8 text-center text-gray-400">No sales data yet</div>
                @endforelse
            </div>
        </div>
    </div>
</x-filament-panels::page>
