<div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Public Orders Card -->
        <div wire:click="openPublicModal" class="stat-card cursor-pointer rounded-2xl p-6 border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm hover:shadow-md transition-all">
            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <p class="text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Public Buyers Pending & Processing</p>
                    <p class="text-4xl font-bold leading-tight mt-2 text-gray-900 dark:text-white">GHS {{ number_format($this->publicTotalAmount, 2) }}</p>
                    <p class="text-sm mt-1 text-gray-500 dark:text-gray-400">{{ $this->publicOrders->count() }} orders await refund</p>
                </div>
                <div class="w-14 h-14 rounded-xl flex items-center justify-center shrink-0 stat-icon" style="background:hsla(204,43%,55%,0.1);color:hsl(204,43%,55%)">
                    <i class="fas fa-globe text-2xl"></i>
                </div>
            </div>
            <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                <span class="text-sm font-medium text-primary-600 dark:text-primary-400 flex items-center gap-1">
                    View Orders & Refund <i class="fas fa-arrow-right text-xs"></i>
                </span>
            </div>
        </div>

        <!-- Agents & Dealers Card -->
        <div wire:click="openAgentModal" class="stat-card cursor-pointer rounded-2xl p-6 border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm hover:shadow-md transition-all">
            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <p class="text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Agents/Dealers Pending & Processing</p>
                    <p class="text-4xl font-bold leading-tight mt-2 text-gray-900 dark:text-white">GHS {{ number_format($this->agentTotalAmount, 2) }}</p>
                    <p class="text-sm mt-1 text-gray-500 dark:text-gray-400">{{ $this->agentOrders->count() }} orders await refund</p>
                </div>
                <div class="w-14 h-14 rounded-xl flex items-center justify-center shrink-0 stat-icon" style="background:hsla(176,75%,36%,0.1);color:hsl(176,75%,36%)">
                    <i class="fas fa-users text-2xl"></i>
                </div>
            </div>
            <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                <span class="text-sm font-medium text-primary-600 dark:text-primary-400 flex items-center gap-1">
                    View Orders & Bulk Credit <i class="fas fa-arrow-right text-xs"></i>
                </span>
            </div>
        </div>
    </div>

    <!-- Public Orders Modal -->
    @if($showPublicModal)
    <div class="fixed inset-0 z-50 flex justify-center overflow-y-auto py-10 px-4">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" wire:click="closePublicModal"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 w-full max-w-4xl flex flex-col z-10 m-auto h-fit max-h-[90vh] overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Public Buyers Orders</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-300">Pending and processing orders</p>
                </div>
                
                <div class="flex-1 flex justify-center px-4 gap-2">
                    <input type="date" wire:model.live="dateFrom" class="w-full max-w-[140px] text-sm rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500" title="From Date">
                    <input type="date" wire:model.live="dateTo" class="w-full max-w-[140px] text-sm rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500" title="To Date">
                </div>

                <div class="flex-1 flex justify-end">
                    <button wire:click="closePublicModal" class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition-all">
                        <i class="fas fa-times text-sm"></i>
                    </button>
                </div>
            </div>
            
            <div class="overflow-y-auto flex-1 min-h-0 p-6">
                @if($this->publicOrders->isEmpty())
                    <div class="text-center py-8 text-gray-500 dark:text-gray-400">No pending/processing public orders found.</div>
                @else
                    <!-- Mobile Card View -->
                    <div class="lg:hidden flex flex-col gap-4">
                        @foreach($this->publicOrders as $order)
                        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl p-4 shadow-sm relative overflow-hidden flex flex-col gap-3">
                            <div class="flex justify-between items-start">
                                <div>
                                    <div class="font-bold text-gray-900 dark:text-white mb-1">{{ $order->customer_name ?? 'N/A' }}</div>
                                    <div class="text-sm text-gray-600 dark:text-gray-300">{{ $order->phone ?? $order->beneficiary_number }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="font-bold text-gray-900 dark:text-white text-base">GHS {{ number_format($order->amount, 2) }}</div>
                                    <div class="text-xs font-medium px-2 py-0.5 rounded-md bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 mt-1 inline-block">
                                        {{ $order->package_name ?? $order->package_size }}
                                    </div>
                                </div>
                            </div>
                            <div class="flex justify-between items-center text-xs text-gray-500 dark:text-gray-400 pt-3 border-t border-gray-100 dark:border-gray-700/50 mt-1">
                                <span>{{ $order->created_at->format('M d, Y h:i A') }}</span>
                                <button wire:click="refundSinglePublicOrder({{ $order->id }})" wire:confirm="Refund this order?" class="px-3 py-1.5 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 font-medium rounded-md transition-colors">
                                    Refund
                                </button>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <!-- Desktop Table View -->
                    <div class="hidden lg:block overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
                        <table class="w-full text-left text-sm min-w-max whitespace-nowrap">
                            <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th class="px-4 py-3 font-semibold text-gray-600 dark:text-gray-300">Customer</th>
                                    <th class="px-4 py-3 font-semibold text-gray-600 dark:text-gray-300">Date</th>
                                    <th class="px-4 py-3 font-semibold text-gray-600 dark:text-gray-300">GB Purchase</th>
                                    <th class="px-4 py-3 font-semibold text-gray-600 dark:text-gray-300">Amount Paid</th>
                                    <th class="px-4 py-3 font-semibold text-gray-600 dark:text-gray-300 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @foreach($this->publicOrders as $order)
                                <tr class="bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-4 py-3 text-gray-900 dark:text-white">
                                        {{ $order->customer_name ?? 'N/A' }}<br>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $order->phone ?? $order->beneficiary_number }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $order->created_at->format('M d, Y h:i A') }}</td>
                                    <td class="px-4 py-3 text-gray-900 dark:text-white">{{ $order->package_name ?? $order->package_size }}</td>
                                    <td class="px-4 py-3 font-semibold" style="color:hsl(176,75%,36%)">GHS {{ number_format($order->amount, 2) }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <button wire:click="refundSinglePublicOrder({{ $order->id }})" wire:confirm="Refund this order?" class="px-3 py-1.5 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 text-xs font-medium rounded-md transition-colors">
                                            Refund
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 flex justify-between items-center">
                <div class="text-sm text-gray-600 dark:text-gray-300">
                    Total to refund: <span class="font-bold text-gray-900 dark:text-white">GHS {{ number_format($this->publicTotalAmount, 2) }}</span>
                </div>
                <div>
                    @if($this->publicOrders->isNotEmpty())
                    <button wire:click="refundPublicOrders" wire:confirm="Are you sure you want to refund ALL these public orders? This action cannot be undone." class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
                        <i class="fas fa-undo-alt mr-2"></i> Refund All Public Orders
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Agent/Dealer Orders Modal -->
    @if($showAgentModal)
    <div class="fixed inset-0 z-50 flex justify-center overflow-y-auto py-10 px-4">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" wire:click="closeAgentModal"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 w-full max-w-4xl flex flex-col z-10 m-auto h-fit max-h-[90vh] overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
                <div class="flex-1">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Agents & Dealers Orders</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-300">Pending and processing orders</p>
                </div>
                
                <div class="flex-1 flex justify-center px-4 gap-2">
                    <select wire:model.live="agentFilter" class="w-full max-w-[160px] text-sm rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <option value="">All Agents & Dealers</option>
                        @foreach($this->filterableAgents as $agent)
                            <option value="{{ $agent->id }}">{{ $agent->full_name ?? $agent->username }}</option>
                        @endforeach
                    </select>
                    <input type="date" wire:model.live="dateFrom" class="w-full max-w-[130px] text-sm rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500" title="From Date">
                    <input type="date" wire:model.live="dateTo" class="w-full max-w-[130px] text-sm rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500" title="To Date">
                </div>

                <div class="flex-1 flex justify-end">
                    <button wire:click="closeAgentModal" class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition-all">
                        <i class="fas fa-times text-sm"></i>
                    </button>
                </div>
            </div>
            
            <div class="overflow-y-auto flex-1 min-h-0 p-6">
                @if($this->agentOrders->isEmpty())
                    <div class="text-center py-8 text-gray-500 dark:text-gray-400">No pending/processing agent or dealer orders found.</div>
                @else
                    <!-- Mobile Card View -->
                    <div class="lg:hidden flex flex-col gap-4">
                        @foreach($this->agentOrders as $order)
                        @php
                            $isStore = isset($order->sub_agent_id) || $order instanceof \App\Models\StoreOrder;
                            $amount = $isStore ? $order->sold_price : $order->amount;
                            $name = $isStore 
                                ? ($order->subAgent->full_name ?? 'Sub Agent') 
                                : ($order->agent->full_name ?? $order->agent->username ?? 'Agent');
                        @endphp
                        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl p-4 shadow-sm relative overflow-hidden flex flex-col gap-3">
                            <div class="flex justify-between items-start">
                                <div>
                                    <div class="font-bold text-gray-900 dark:text-white mb-1">
                                        {{ $name }}
                                        <span class="inline-block ml-2 px-2 py-0.5 rounded text-[10px] font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                                            {{ $isStore ? 'Store/Sub-Agent' : 'Agent/Dealer' }}
                                        </span>
                                    </div>
                                    <div class="text-sm text-gray-600 dark:text-gray-300">{{ $order->package_name ?? ($isStore ? $order->package_size_gb . ' GB' : $order->package_size) }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="font-bold text-gray-900 dark:text-white text-base">GHS {{ number_format($amount, 2) }}</div>
                                </div>
                            </div>
                            <div class="flex justify-between items-center text-xs text-gray-500 dark:text-gray-400 pt-3 border-t border-gray-100 dark:border-gray-700/50 mt-1">
                                <span>{{ $order->created_at->format('M d, Y h:i A') }}</span>
                                <button wire:click="refundSingleAgentOrder({{ $order->id }}, {{ $isStore ? 'true' : 'false' }})" wire:confirm="Refund this order?" class="px-3 py-1.5 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 font-medium rounded-md transition-colors">
                                    Refund
                                </button>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <!-- Desktop Table View -->
                    <div class="hidden lg:block overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
                        <table class="w-full text-left text-sm min-w-max whitespace-nowrap">
                            <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th class="px-4 py-3 font-semibold text-gray-600 dark:text-gray-300">Agent/Dealer</th>
                                    <th class="px-4 py-3 font-semibold text-gray-600 dark:text-gray-300">Date</th>
                                    <th class="px-4 py-3 font-semibold text-gray-600 dark:text-gray-300">GB Purchase</th>
                                    <th class="px-4 py-3 font-semibold text-gray-600 dark:text-gray-300">Amount Paid</th>
                                    <th class="px-4 py-3 font-semibold text-gray-600 dark:text-gray-300 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @foreach($this->agentOrders as $order)
                                @php
                                    $isStore = isset($order->sub_agent_id) || $order instanceof \App\Models\StoreOrder;
                                    $amount = $isStore ? $order->sold_price : $order->amount;
                                    $name = $isStore 
                                        ? ($order->subAgent->full_name ?? 'Sub Agent') 
                                        : ($order->agent->full_name ?? $order->agent->username ?? 'Agent');
                                @endphp
                                <tr class="bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-4 py-3 text-gray-900 dark:text-white">
                                        {{ $name }}
                                        <span class="inline-block ml-2 px-2 py-0.5 rounded text-[10px] font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                                            {{ $isStore ? 'Store/Sub-Agent' : 'Agent/Dealer' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $order->created_at->format('M d, Y h:i A') }}</td>
                                    <td class="px-4 py-3 text-gray-900 dark:text-white">{{ $order->package_name ?? ($isStore ? $order->package_size_gb . ' GB' : $order->package_size) }}</td>
                                    <td class="px-4 py-3 font-semibold" style="color:hsl(176,75%,36%)">GHS {{ number_format($amount, 2) }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <button wire:click="refundSingleAgentOrder({{ $order->id }}, {{ $isStore ? 'true' : 'false' }})" wire:confirm="Refund this order?" class="px-3 py-1.5 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 text-xs font-medium rounded-md transition-colors">
                                            Refund
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 flex justify-between items-center">
                <div class="text-sm text-gray-600 dark:text-gray-300">
                    Total to credit: <span class="font-bold text-gray-900 dark:text-white">GHS {{ number_format($this->agentTotalAmount, 2) }}</span>
                </div>
                <div>
                    @if($this->agentOrders->isNotEmpty())
                    <button wire:click="refundAgentOrders" wire:confirm="Are you sure you want to credit these amounts back to all respective agents and dealers? This action cannot be undone." class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
                        <i class="fas fa-undo-alt mr-2"></i> Bulk Credit & Refund
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
