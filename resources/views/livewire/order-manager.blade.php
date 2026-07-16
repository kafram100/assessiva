<div x-data="{ activeTab: 'customer', showToast: false, toastMsg: '', toastType: 'success' }"
     x-on:notify.window="toastMsg = $event.detail.message; toastType = $event.detail.type; showToast = true; setTimeout(() => showToast = false, 3000)">
    <style>
        .order-row { transition: all 0.15s ease; }
        .order-row:hover { background: rgba(15,159,149,0.04); }
        .order-status { transition: all 0.2s ease; }
        .expand-btn { transition: all 0.2s ease; }
        .expand-btn:hover { transform: scale(1.15); }
    </style>

    {{-- Toast Notification --}}
    <div x-show="showToast" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2" class="fixed top-4 right-4 z-[9999] max-w-sm" x-cloak>
        <div class="flex items-center gap-3 px-4 py-3 rounded-xl shadow-lg border" :class="toastType === 'success' ? 'bg-green-50 border-green-200 text-green-800 dark:bg-green-900/30 dark:border-green-700 dark:text-green-300' : 'bg-amber-50 border-amber-200 text-amber-800 dark:bg-amber-900/30 dark:border-amber-700 dark:text-amber-300'">
            <i class="fas" :class="toastType === 'success' ? 'fa-check-circle text-green-500' : 'fa-exclamation-triangle text-amber-500'"></i>
            <span class="text-sm font-medium" x-text="toastMsg"></span>
        </div>
    </div>

    {{-- Tabs Navigation --}}
    <div class="flex flex-wrap gap-2 mb-6 border-b border-gray-200 dark:border-gray-700">
        <button @@click="activeTab = 'customer'" 
            class="px-5 py-3 text-sm font-bold transition-colors border-b-2 outline-none"
            :class="activeTab === 'customer' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'">
            <i class="fas fa-shopping-bag mr-2"></i>Customer Orders
        </button>
        <button @@click="activeTab = 'agent'" 
            class="px-5 py-3 text-sm font-bold transition-colors border-b-2 outline-none"
            :class="activeTab === 'agent' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'">
            <i class="fas fa-users mr-2"></i>Agent, Dealer & Store Orders
        </button>
    </div>

    {{-- Customer Orders Section --}}
    <div x-show="activeTab === 'customer'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="bg-white/60 backdrop-blur-sm dark:bg-gray-800/80 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden mb-6">
        <div>
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg flex items-center justify-center" style="background:hsla(204,43%,55%,0.12)">
                        <i class="fas fa-shopping-bag" style="color:hsl(204,43%,55%)"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Customer Orders</h3>
                        <p class="text-gray-500 dark:text-gray-300 text-sm">Store orders from the public storefront</p>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <button wire:click="exportCustomerOrders" class="text-sm font-semibold text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 transition-colors flex items-center gap-2">
                        <i class="fas fa-download"></i> Export CSV
                    </button>
                    <span class="text-xs text-gray-400 dark:text-gray-400 font-medium">{{ count($customerOrders) }} orders</span>
                </div>
            </div>

            <div>
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700/50 flex flex-col lg:flex-row lg:items-center justify-between gap-4 overflow-x-auto">
                    <div class="flex items-center gap-3 shrink-0">
                        <label class="flex items-center gap-2 text-sm font-medium text-gray-600 dark:text-gray-300 cursor-pointer whitespace-nowrap">
                            <input type="checkbox" @@change="$wire.selectAllCustomer($event.target.checked)" class="w-5 h-5 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                            Select All
                        </label>
                        <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-primary-50 text-primary-600 dark:bg-primary-900/30 dark:text-primary-400" x-show="$wire.selectedCustomer.length" x-text="$wire.selectedCustomer.length + ' selected'" style="display: none;"></span>
                    </div>
                    <div class="flex items-center justify-end gap-3 flex-nowrap">
                        <div class="relative w-40 shrink-0">
                            <i class="fas fa-search absolute top-1/2 -translate-y-1/2 text-gray-400 text-sm" style="left: 16px;"></i>
                            <input type="text" wire:model.live.debounce.300ms="customerSearch" wire:change="loadCustomerOrders" placeholder="Search..." class="w-full h-11 text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-xl pr-3 outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-all shadow-sm" style="padding-left: 44px;">
                        </div>
                        <input type="date" wire:model.live="dateFrom" class="w-[130px] shrink-0 h-11 text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-xl px-3 shadow-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500" title="From Date">
                        <input type="date" wire:model.live="dateTo" class="w-[130px] shrink-0 h-11 text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-xl px-3 shadow-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500" title="To Date">
                        <select wire:model="customerNetworkFilter" wire:change="loadCustomerOrders" class="w-40 shrink-0 h-11 text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-xl px-4 shadow-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                            <option value="">All Networks</option>
                            <option value="MTN">MTN</option>
                            <option value="Vodafone">Vodafone</option>
                            <option value="AirtelTigo">AirtelTigo</option>
                        </select>
                        <select wire:model="customerStatusFilter" wire:change="loadCustomerOrders" class="w-40 shrink-0 h-11 text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-xl px-4 shadow-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                            <option value="">All Statuses</option>
                            <option value="unpaid">Not Paid</option>
                            <option value="pending">Pending</option>
                            <option value="processing">Processing</option>
                            <option value="completed">Completed</option>
                            <option value="delivered">Delivered</option>
                            <option value="failed">Failed</option>
                        </select>
                        <select id="customerBulkStatus" class="w-40 shrink-0 h-11 text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-xl px-4 shadow-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                            <option value="pending">Set Pending</option>
                            <option value="processing">Set Processing</option>
                            <option value="completed">Set Completed</option>
                        </select>
                        <button x-on:click="$wire.bulkUpdateCustomerStatus(document.getElementById('customerBulkStatus').value)" class="w-40 shrink-0 inline-flex items-center justify-center gap-2 h-11 px-4 text-sm font-semibold text-white rounded-xl transition-all shadow-sm hover:shadow-md active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed" style="background:hsl(176,75%,36%)">
                            <span wire:loading.remove wire:target="bulkUpdateCustomerStatus"><i class="fas fa-check"></i> Apply</span>
                            <span wire:loading wire:target="bulkUpdateCustomerStatus"><i class="fas fa-spinner fa-spin"></i> Updating...</span>
                        </button>
                    </div>
                </div>

                @if(count($customerOrders))
                <!-- Mobile Card View -->
                <div class="lg:hidden flex flex-col gap-4 p-4">
                    @foreach($customerOrders as $order)
                    @php 
                        $oId = $order['id'];
                        $s = $order['status'] ?? 'pending';
                        $sc = match($s) {
                            'pending' => ['hsla(45,93%,47%,0.1)', 'hsl(45,93%,47%)', 'bg-yellow-500'],
                            'processing', 'verified' => ['hsla(204,43%,55%,0.1)', 'hsl(204,43%,55%)', 'bg-tertiary-500'],
                            'completed', 'delivered' => ['hsla(142,71%,45%,0.1)', 'hsl(142,71%,45%)', 'bg-green-500'],
                            'failed', 'cancelled' => ['hsla(0,72%,51%,0.1)', 'hsl(0,72%,51%)', 'bg-red-500'],
                            'unpaid' => ['hsla(0,72%,51%,0.15)', 'hsl(0,72%,51%)', 'bg-red-500'],
                            default => ['hsla(0,0%,50%,0.1)', 'hsl(0,0%,50%)', 'bg-gray-500'],
                        };
                    @endphp
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl p-4 shadow-sm relative overflow-hidden flex flex-col gap-3 cursor-pointer" wire:click="openModal({{ $oId }}, 'customer')">
                        <div class="absolute left-0 top-0 bottom-0 w-1 {{ $sc[2] }}"></div>
                        
                        <div class="flex justify-between items-start">
                            <div class="flex items-center gap-3">
                                <input type="checkbox" value="{{ $oId }}" wire:model="selectedCustomer" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500" x-on:click="$event.stopPropagation()">
                                <div>
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="font-bold text-gray-900 dark:text-white">{{ $order['network_type'] ?? '-' }}</span>
                                        <span class="text-xs font-medium px-2 py-0.5 rounded-md bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">{{ $order['package_name'] ?? '-' }}</span>
                                    </div>
                                    <div class="text-sm text-gray-600 dark:text-gray-300 font-medium">
                                        {{ $order['customer_phone'] ?? '-' }}
                                    </div>
                                </div>
                            </div>
                            <div class="text-right flex flex-col items-end gap-1">
                                <div class="font-bold text-gray-900 dark:text-white text-base">
                                    GH₵ {{ number_format($order['sold_price'] ?? 0, 2) }}
                                </div>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider" style="background:{{ $sc[0] }};color:{{ $sc[1] }}">
                                    {{ $s === 'unpaid' ? 'Not Paid' : ucfirst($s) }}
                                </span>
                            </div>
                        </div>
                        
                        <div class="flex justify-between items-center text-xs text-gray-500 dark:text-gray-400 pt-3 border-t border-gray-100 dark:border-gray-700/50">
                            <span>#{{ $oId }}</span>
                            <span>{{ \Carbon\Carbon::parse($order['created_at'] ?? $order['created'])->format('M d, Y h:i A') }}</span>
                        </div>
                    </div>
                    @endforeach
                </div>

                <!-- Desktop Table View -->
                <div class="hidden lg:block overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-700">
                                <th class="px-3 py-3 text-left w-10"></th>
                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">ID</th>
                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Phone</th>
                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Network</th>
                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Package</th>
                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Amount</th>
                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Date</th>
                                <th class="px-3 py-3 text-center w-10"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                            @foreach($customerOrders as $order)
                            @php $oId = $order['id']; @endphp
                            <tr class="order-row">
                                <td class="px-3 py-2.5">
                                    <input type="checkbox" value="{{ $oId }}" wire:model="selectedCustomer" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                </td>
                                <td class="px-3 py-2.5 whitespace-nowrap text-xs font-mono text-gray-500 dark:text-gray-300">#{{ $oId }}</td>
                                <td class="px-3 py-2.5 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">{{ $order['customer_phone'] ?? '-' }}</td>
                                <td class="px-3 py-2.5 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium" style="background:hsla(204,43%,55%,0.1);color:hsl(204,43%,55%)">{{ $order['network_type'] ?? '-' }}</span>
                                </td>
                                <td class="px-3 py-2.5 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">{{ $order['package_name'] ?? '-' }}</td>
                                <td class="px-3 py-2.5 whitespace-nowrap text-sm font-semibold" style="color:hsl(176,75%,36%)">{{ number_format($order['sold_price'] ?? 0, 2) }}</td>
                                <td class="px-3 py-2.5 whitespace-nowrap">
                                    @php
                                        $s = $order['status'] ?? 'pending';
                                        $sc = match($s) {
                                            'pending' => ['hsla(45,93%,47%,0.1)', 'hsl(45,93%,47%)'],
                                            'processing', 'verified' => ['hsla(204,43%,55%,0.1)', 'hsl(204,43%,55%)'],
                                            'completed', 'delivered' => ['hsla(142,71%,45%,0.1)', 'hsl(142,71%,45%)'],
                                            'failed', 'cancelled' => ['hsla(0,72%,51%,0.1)', 'hsl(0,72%,51%)'],
                                            'unpaid' => ['hsla(0,72%,51%,0.15)', 'hsl(0,72%,51%)'],
                                            default => ['hsla(0,0%,50%,0.1)', 'hsl(0,0%,50%)'],
                                        };
                                    @endphp
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium order-status" style="background:{{ $sc[0] }};color:{{ $sc[1] }}">{{ $s === 'unpaid' ? 'Not Paid' : ucfirst($s) }}</span>
                                </td>
                                <td class="px-3 py-2.5 whitespace-nowrap text-xs text-gray-500 dark:text-gray-300">{{ \Carbon\Carbon::parse($order['created_at'] ?? $order['created'])->format('M d, Y h:i A') }}</td>
                                <td class="px-3 py-2.5 text-center">
                                    <button wire:click="openModal({{ $oId }}, 'customer')" class="expand-btn inline-flex items-center justify-center w-7 h-7 rounded-lg transition-all text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">
                                        <i class="fas fa-chevron-down text-xs"></i>
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="px-6 py-8 text-center text-gray-500 dark:text-gray-300">
                    <i class="fas fa-inbox text-2xl mb-2 opacity-50"></i>
                    <p>No customer orders found</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Agent & Dealer Orders Section --}}
    <div x-show="activeTab === 'agent'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-cloak class="bg-white/60 backdrop-blur-sm dark:bg-gray-800/80 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden mb-6">
        <div>
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg flex items-center justify-center" style="background:hsla(39,100%,39%,0.12)">
                        <i class="fas fa-users" style="color:hsl(39,100%,39%)"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Agent, Dealer &amp; Store Orders</h3>
                        <p class="text-gray-500 dark:text-gray-300 text-sm">Orders placed by agents, dealers, Sub-Agents &amp; storefronts</p>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <button wire:click="exportAgentDealerOrders" class="text-sm font-semibold text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 transition-colors flex items-center gap-2">
                        <i class="fas fa-download"></i> Export CSV
                    </button>
                    <span class="text-xs text-gray-400 dark:text-gray-400 font-medium">{{ count($agentDealerOrders) }} orders</span>
                </div>
            </div>

            <div>
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700/50 flex flex-col lg:flex-row lg:items-center justify-between gap-4 overflow-x-auto">
                    <div class="flex items-center gap-3 shrink-0">
                        <label class="flex items-center gap-2 text-sm font-medium text-gray-600 dark:text-gray-300 cursor-pointer whitespace-nowrap">
                            <input type="checkbox" @@change="$wire.selectAllAgentDealer($event.target.checked)" class="w-5 h-5 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                            Select All
                        </label>
                        <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-primary-50 text-primary-600 dark:bg-primary-900/30 dark:text-primary-400" x-show="$wire.selectedAgentDealer.length" x-text="$wire.selectedAgentDealer.length + ' selected'" style="display: none;"></span>
                    </div>
                    <div class="flex items-center justify-end gap-3 flex-nowrap">
                        <div class="relative w-40 shrink-0">
                            <i class="fas fa-search absolute top-1/2 -translate-y-1/2 text-gray-400 text-sm" style="left: 16px;"></i>
                            <input type="text" wire:model.live.debounce.300ms="agentDealerSearch" wire:change="loadAgentDealerOrders" placeholder="Search..." class="w-full h-11 text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-xl pr-3 outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-all shadow-sm" style="padding-left: 44px;">
                        </div>
                        <input type="date" wire:model.live="dateFrom" class="w-[130px] shrink-0 h-11 text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-xl px-3 shadow-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500" title="From Date">
                        <input type="date" wire:model.live="dateTo" class="w-[130px] shrink-0 h-11 text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-xl px-3 shadow-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500" title="To Date">
                        <select wire:model="agentNetworkFilter" wire:change="loadAgentDealerOrders" class="w-40 shrink-0 h-11 text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-xl px-4 shadow-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                            <option value="">All Networks</option>
                            <option value="MTN">MTN</option>
                            <option value="Vodafone">Vodafone</option>
                            <option value="AirtelTigo">AirtelTigo</option>
                        </select>
                        <select wire:model="agentStatusFilter" wire:change="loadAgentDealerOrders" class="w-40 shrink-0 h-11 text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-xl px-4 shadow-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                            <option value="">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="processing">Processing</option>
                            <option value="completed">Completed</option>
                            <option value="delivered">Delivered</option>
                            <option value="failed">Failed</option>
                        </select>
                        <select id="agentBulkStatus" class="w-40 shrink-0 h-11 text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-xl px-4 shadow-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                            <option value="pending">Set Pending</option>
                            <option value="processing">Set Processing</option>
                            <option value="completed">Set Completed</option>
                        </select>
                        <button x-on:click="$wire.bulkUpdateAgentDealerStatus(document.getElementById('agentBulkStatus').value)" class="w-40 shrink-0 inline-flex items-center justify-center gap-2 h-11 px-4 text-sm font-semibold text-white rounded-xl transition-all shadow-sm hover:shadow-md active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed" style="background:hsl(176,75%,36%)">
                            <span wire:loading.remove wire:target="bulkUpdateAgentDealerStatus"><i class="fas fa-check"></i> Apply</span>
                            <span wire:loading wire:target="bulkUpdateAgentDealerStatus"><i class="fas fa-spinner fa-spin"></i> Updating...</span>
                        </button>
                    </div>
                </div>

                @if(count($agentDealerOrders))
                <!-- Mobile Card View -->
                <div class="lg:hidden flex flex-col gap-4 p-4">
                    @foreach($agentDealerOrders as $order)
                    @php 
                        $key = $order['source_type'] . '-' . $order['id'];
                        $s = $order['status'] ?? 'pending';
                        $sc = match($s) {
                            'pending' => ['hsla(45,93%,47%,0.1)', 'hsl(45,93%,47%)', 'bg-yellow-500'],
                            'processing', 'verified' => ['hsla(204,43%,55%,0.1)', 'hsl(204,43%,55%)', 'bg-tertiary-500'],
                            'completed', 'delivered' => ['hsla(142,71%,45%,0.1)', 'hsl(142,71%,45%)', 'bg-green-500'],
                            'failed', 'cancelled' => ['hsla(0,72%,51%,0.1)', 'hsl(0,72%,51%)', 'bg-red-500'],
                            'unpaid' => ['hsla(0,72%,51%,0.15)', 'hsl(0,72%,51%)', 'bg-red-500'],
                            default => ['hsla(0,0%,50%,0.1)', 'hsl(0,0%,50%)', 'bg-gray-500'],
                        };
                        $tc = match($order['type']) {
                            'Agent Order' => ['hsla(270,50%,50%,0.1)', 'hsl(270,50%,50%)'],
                            'Sub-Agent' => ['hsla(176,75%,36%,0.1)', 'hsl(176,75%,36%)'],
                            'Storefront' => ['hsla(204,43%,55%,0.1)', 'hsl(204,43%,55%)'],
                            default => ['hsla(0,0%,50%,0.1)', 'hsl(0,0%,50%)'],
                        };
                    @endphp
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl p-4 shadow-sm relative overflow-hidden flex flex-col gap-3 cursor-pointer" wire:click="openModal({{ $order['id'] }}, 'agent')">
                        <div class="absolute left-0 top-0 bottom-0 w-1 {{ $sc[2] }}"></div>
                        
                        <div class="flex justify-between items-start">
                            <div class="flex items-center gap-3">
                                <input type="checkbox" value="{{ $key }}" wire:model="selectedAgentDealer" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500" x-on:click="$event.stopPropagation()">
                                <div>
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="font-bold text-gray-900 dark:text-white">{{ $order['network'] }}</span>
                                        <span class="text-xs font-medium px-2 py-0.5 rounded-md bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">{{ $order['package'] }}</span>
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded uppercase" style="background:{{ $tc[0] }};color:{{ $tc[1] }}">{{ $order['type'] }}</span>
                                    </div>
                                    <div class="text-sm text-gray-600 dark:text-gray-300 font-medium">
                                        {{ $order['beneficiary'] }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                        By {{ $order['user_name'] }} <span class="opacity-70">(#{{ $order['user_id'] }})</span>
                                    </div>
                                </div>
                            </div>
                            <div class="text-right flex flex-col items-end gap-1 shrink-0">
                                <div class="font-bold text-gray-900 dark:text-white text-base">
                                    GH₵ {{ number_format($order['amount'], 2) }}
                                </div>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider" style="background:{{ $sc[0] }};color:{{ $sc[1] }}">
                                    {{ $s === 'unpaid' ? 'Not Paid' : ucfirst($s) }}
                                </span>
                            </div>
                        </div>
                        
                        <div class="flex justify-between items-center text-xs text-gray-500 dark:text-gray-400 pt-3 border-t border-gray-100 dark:border-gray-700/50">
                            <span>#{{ $order['id'] }}</span>
                            <span>{{ $order['created'] ? \Carbon\Carbon::parse($order['created'])->format('M d, Y h:i A') : '-' }}</span>
                        </div>
                    </div>
                    @endforeach
                </div>

                <!-- Desktop Table View -->
                <div class="hidden lg:block overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-700">
                                <th class="px-3 py-3 text-left w-10"></th>
                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Type</th>
                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">User</th>
                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Phone</th>
                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Network</th>
                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Package</th>
                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Amount</th>
                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Date</th>
                                <th class="px-3 py-3 text-center w-10"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                            @foreach($agentDealerOrders as $order)
                            @php $key = $order['source_type'] . '-' . $order['id']; @endphp
                            <tr class="order-row">
                                <td class="px-3 py-2.5">
                                    <input type="checkbox" value="{{ $key }}" wire:model="selectedAgentDealer" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                </td>
                                <td class="px-3 py-2.5 whitespace-nowrap">
                                    @php
                                        $tc = match($order['type']) {
                                            'Agent Order' => ['hsla(270,50%,50%,0.1)', 'hsl(270,50%,50%)'],
                                            'Sub-Agent' => ['hsla(176,75%,36%,0.1)', 'hsl(176,75%,36%)'],
                                            'Storefront' => ['hsla(204,43%,55%,0.1)', 'hsl(204,43%,55%)'],
                                            default => ['hsla(0,0%,50%,0.1)', 'hsl(0,0%,50%)'],
                                        };
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium" style="background:{{ $tc[0] }};color:{{ $tc[1] }}">
                                        {{ $order['type'] }}
                                    </span>
                                </td>
                                <td class="px-3 py-2.5 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $order['user_name'] }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-300">#{{ $order['user_id'] }}</div>
                                </td>
                                <td class="px-3 py-2.5 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">{{ $order['beneficiary'] }}</td>
                                <td class="px-3 py-2.5 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium" style="background:hsla(204,43%,55%,0.1);color:hsl(204,43%,55%)">{{ $order['network'] }}</span>
                                </td>
                                <td class="px-3 py-2.5 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">{{ $order['package'] }}</td>
                                <td class="px-3 py-2.5 whitespace-nowrap text-sm font-semibold" style="color:hsl(176,75%,36%)">{{ number_format($order['amount'], 2) }}</td>
                                <td class="px-3 py-2.5 whitespace-nowrap">
                                    @php
                                        $s = $order['status'] ?? 'pending';
                                        $sc = match($s) {
                                            'pending' => ['hsla(45,93%,47%,0.1)', 'hsl(45,93%,47%)'],
                                            'processing', 'verified' => ['hsla(204,43%,55%,0.1)', 'hsl(204,43%,55%)'],
                                            'completed', 'delivered' => ['hsla(142,71%,45%,0.1)', 'hsl(142,71%,45%)'],
                                            'failed', 'cancelled' => ['hsla(0,72%,51%,0.1)', 'hsl(0,72%,51%)'],
                                            'unpaid' => ['hsla(0,72%,51%,0.15)', 'hsl(0,72%,51%)'],
                                            default => ['hsla(0,0%,50%,0.1)', 'hsl(0,0%,50%)'],
                                        };
                                    @endphp
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium order-status" style="background:{{ $sc[0] }};color:{{ $sc[1] }}">{{ $s === 'unpaid' ? 'Not Paid' : ucfirst($s) }}</span>
                                </td>
                                <td class="px-3 py-2.5 whitespace-nowrap text-xs text-gray-500 dark:text-gray-300">{{ $order['created'] ? \Carbon\Carbon::parse($order['created'])->format('M d, Y h:i A') : '-' }}</td>
                                <td class="px-3 py-2.5 text-center">
                                    <button wire:click="openModal({{ $order['id'] }}, 'agent')" class="expand-btn inline-flex items-center justify-center w-7 h-7 rounded-lg transition-all text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">
                                        <i class="fas fa-chevron-down text-xs"></i>
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="px-6 py-8 text-center text-gray-500 dark:text-gray-300">
                    <i class="fas fa-inbox text-2xl mb-2 opacity-50"></i>
                    <p>No agent, dealer or store orders found</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Order Details Modal --}}
    @if($showModal && $selectedOrder)
    <div class="fixed inset-0 z-50 flex items-center justify-center" x-data x-on:keydown.escape.window="$wire.closeModal()">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" wire:click="closeModal"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 w-full max-w-lg max-h-[90vh] overflow-y-auto z-10 mx-4">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Order #{{ $selectedOrder['id'] }}</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-300">{{ ($selectedOrder['type'] ?? 'Order') }} Details</p>
                </div>
                <button wire:click="closeModal" class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition-all">
                    <i class="fas fa-times text-sm"></i>
                </button>
            </div>
            <div class="px-6 py-4 space-y-3">
                @if(!empty($selectedOrder['base_price']))
                <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-700/50">
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Amount</span>
                    <span class="text-sm text-gray-900 dark:text-white">GH₵ {{ number_format($selectedOrder['base_price'], 2) }}</span>
                </div>
                @endif
                @if(!empty($selectedOrder['processing_fee']) && $selectedOrder['processing_fee'] > 0)
                <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-700/50">
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Processing Fee (2%)</span>
                    <span class="text-sm text-gray-900 dark:text-white">GH₵ {{ number_format($selectedOrder['processing_fee'], 2) }}</span>
                </div>
                @endif
                <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-700/50">
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Reference</span>
                    <span class="text-sm font-mono text-gray-900 dark:text-white text-right break-all max-w-[220px]">{{ $selectedOrder['order_ref'] ?? $selectedOrder['reference'] ?? '-' }}</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-700/50">
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Payment Method</span>
                    <span class="text-sm text-gray-900 dark:text-white">{{ $selectedOrder['payment_method'] ?? '-' }}</span>
                </div>
                @if(!empty($selectedOrder['tracking_key']))
                <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-700/50">
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tracking Key</span>
                    <span class="text-sm font-mono text-gray-900 dark:text-white">{{ $selectedOrder['tracking_key'] }}</span>
                </div>
                @endif
                <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-700/50">
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">External Transaction</span>
                    <span class="text-sm font-mono text-gray-900 dark:text-white text-xs break-all max-w-[200px] text-right">{{ $selectedOrder['external_transaction_id'] ?? $selectedOrder['external_txn'] ?? '-' }}</span>
                </div>
                @if(!empty($selectedOrder['user_name']))
                <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-700/50">
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ $selectedOrder['type'] === 'Agent Order' ? 'Agent' : 'User' }}</span>
                    <span class="text-sm text-gray-900 dark:text-white">{{ $selectedOrder['user_name'] }} (#{{ $selectedOrder['user_id'] }})</span>
                </div>
                @endif
                <div class="flex justify-between items-center py-2">
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Created</span>
                    <span class="text-sm text-gray-900 dark:text-white">{{ ($dt = $selectedOrder['created_at'] ?? $selectedOrder['created'] ?? null) ? \Carbon\Carbon::parse($dt)->format('M d, Y h:i A') : '-' }}</span>
                </div>
                @if(!empty($selectedOrder['api_data']))
                <div class="pt-2">
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">API Response</span>
                    <pre class="mt-1 text-xs text-gray-600 dark:text-gray-300 bg-gray-50 dark:bg-gray-900/30 rounded-lg p-3 overflow-x-auto max-h-32">{{ is_string($selectedOrder['api_data']) ? $selectedOrder['api_data'] : json_encode($selectedOrder['api_data'], JSON_PRETTY_PRINT) }}</pre>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('modal-opened', () => { document.body.style.overflow = 'hidden'; });
            Livewire.on('modal-closed', () => { document.body.style.overflow = ''; });
        });
    </script>
</div>
