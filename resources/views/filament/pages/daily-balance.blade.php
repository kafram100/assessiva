<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">
            Daily Balance
        </x-slot>

        <form method="GET" class="flex gap-3 items-end mb-4">
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">User</label>
                <select name="userId" class="rounded-lg border-gray-300 text-sm" onchange="this.form.submit()">
                    <option value="">All Users</option>
                    @foreach($agents as $id => $name)
                        <option value="{{ $id }}" {{ $userId == $id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">From</label>
                <input type="date" name="dateFrom" value="{{ $dateFrom }}" class="rounded-lg border-gray-300 text-sm" onchange="this.form.submit()">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">To</label>
                <input type="date" name="dateTo" value="{{ $dateTo }}" class="rounded-lg border-gray-300 text-sm" onchange="this.form.submit()">
            </div>
        </form>

        @if($dailyBalances->isEmpty())
            <p class="text-gray-500 text-center py-8">No balance history found for the selected filters.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="text-left py-2 px-2 font-medium text-gray-500">Date</th>
                            <th class="text-left py-2 px-2 font-medium text-gray-500">User</th>
                            <th class="text-right py-2 px-2 font-medium text-gray-500">Opening</th>
                            <th class="text-right py-2 px-2 font-medium text-gray-500">Credit</th>
                            <th class="text-right py-2 px-2 font-medium text-gray-500">Debit</th>
                            <th class="text-right py-2 px-2 font-medium text-gray-500">Change</th>
                            <th class="text-right py-2 px-2 font-medium text-gray-500">Closing</th>
                            <th class="text-right py-2 px-2 font-medium text-gray-500">Tx</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($dailyBalances as $b)
                            <tr class="border-b border-gray-100 hover:bg-gray-50">
                                <td class="py-2 px-2 whitespace-nowrap">{{ $b['date'] }}</td>
                                <td class="py-2 px-2">
                                    <span class="font-medium">{{ $b['user'] }}</span>
                                    <span class="text-xs text-gray-400 ml-1">({{ $b['role'] }})</span>
                                </td>
                                <td class="py-2 px-2 text-right tabular-nums">GHS {{ number_format($b['opening'], 2) }}</td>
                                <td class="py-2 px-2 text-right tabular-nums text-green-600">+ GHS {{ number_format($b['credit'], 2) }}</td>
                                <td class="py-2 px-2 text-right tabular-nums text-red-600">- GHS {{ number_format($b['debit'], 2) }}</td>
                                <td class="py-2 px-2 text-right tabular-nums {{ $b['change'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $b['change'] >= 0 ? '+' : '' }}GHS {{ number_format($b['change'], 2) }}
                                </td>
                                <td class="py-2 px-2 text-right tabular-nums font-medium">GHS {{ number_format($b['closing'], 2) }}</td>
                                <td class="py-2 px-2 text-right">{{ $b['tx_count'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>
