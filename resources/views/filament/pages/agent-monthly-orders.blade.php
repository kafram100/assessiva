<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">
            Agent Monthly Orders
        </x-slot>

        <form method="GET" class="flex gap-3 items-end mb-4">
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">Year</label>
                <select name="year" class="rounded-lg border-gray-300 text-sm" onchange="this.form.submit()">
                    @foreach($years as $y)
                        <option value="{{ $y }}" {{ ($year ?: now()->year) == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">Agent</label>
                <select name="agentId" class="rounded-lg border-gray-300 text-sm" onchange="this.form.submit()">
                    <option value="">All Agents</option>
                    @foreach($agents as $id => $name)
                        <option value="{{ $id }}" {{ $agentId == $id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
        </form>

        @if($summary)
            <div class="grid grid-cols-4 gap-4 mb-6">
                <div class="bg-gray-50 rounded-lg p-3 text-center">
                    <div class="text-2xl font-bold">{{ $summary->total_orders ?? 0 }}</div>
                    <div class="text-xs text-gray-500">Total Orders</div>
                </div>
                <div class="bg-green-50 rounded-lg p-3 text-center">
                    <div class="text-2xl font-bold text-green-700">{{ $summary->completed_orders ?? 0 }}</div>
                    <div class="text-xs text-gray-500">Completed</div>
                </div>
                <div class="bg-red-50 rounded-lg p-3 text-center">
                    <div class="text-2xl font-bold text-red-700">{{ ($summary->total_orders ?? 0) - ($summary->completed_orders ?? 0) }}</div>
                    <div class="text-xs text-gray-500">Pending/Failed</div>
                </div>
                <div class="bg-blue-50 rounded-lg p-3 text-center">
                    <div class="text-2xl font-bold text-blue-700">GHS {{ number_format($summary->completed_amount ?? 0, 2) }}</div>
                    <div class="text-xs text-gray-500">Completed Revenue</div>
                </div>
            </div>
        @endif

        @if($monthlyOrders->isEmpty())
            <p class="text-gray-500 text-center py-8">No orders found for the selected filters.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="text-left py-2 px-2 font-medium text-gray-500">Month</th>
                            <th class="text-left py-2 px-2 font-medium text-gray-500">Agent</th>
                            <th class="text-right py-2 px-2 font-medium text-gray-500">Total</th>
                            <th class="text-right py-2 px-2 font-medium text-gray-500">Completed</th>
                            <th class="text-right py-2 px-2 font-medium text-gray-500">Pending/Failed</th>
                            <th class="text-right py-2 px-2 font-medium text-gray-500">Total Amount</th>
                            <th class="text-right py-2 px-2 font-medium text-gray-500">Completed Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($monthlyOrders as $o)
                            <tr class="border-b border-gray-100 hover:bg-gray-50">
                                <td class="py-2 px-2 whitespace-nowrap font-medium">{{ $o['month'] }} {{ $o['year'] }}</td>
                                <td class="py-2 px-2">
                                    <span class="font-medium">{{ $o['user'] }}</span>
                                    <span class="text-xs text-gray-400 ml-1">({{ $o['role'] }})</span>
                                </td>
                                <td class="py-2 px-2 text-right">{{ $o['total_orders'] }}</td>
                                <td class="py-2 px-2 text-right text-green-600">{{ $o['completed_orders'] }}</td>
                                <td class="py-2 px-2 text-right text-red-600">{{ $o['failed_orders'] }}</td>
                                <td class="py-2 px-2 text-right tabular-nums">GHS {{ number_format($o['total_amount'], 2) }}</td>
                                <td class="py-2 px-2 text-right tabular-nums font-medium">GHS {{ number_format($o['completed_amount'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>
