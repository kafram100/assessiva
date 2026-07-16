<x-filament-panels::page>
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <style>
        :root {
            --card-primary: hsla(176, 75%, 36%, 0.12);
            --card-primary-border: hsla(174, 61%, 62%, 0.25);
            --card-primary-icon: hsla(176, 75%, 36%, 0.25);
            --card-tertiary: hsla(204, 43%, 55%, 0.12);
            --card-tertiary-border: hsla(205, 86%, 77%, 0.25);
            --card-tertiary-icon: hsla(204, 43%, 55%, 0.25);
            --card-secondary: hsla(39, 100%, 39%, 0.12);
            --card-secondary-border: hsla(34, 100%, 68%, 0.25);
            --card-secondary-icon: hsla(39, 100%, 39%, 0.25);
        }
        .stat-card { transition: all 0.25s ease; }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 12px 30px -8px rgba(0,0,0,.15); }
        .stat-icon { transition: all 0.3s ease; }
        .stat-card:hover .stat-icon { transform: scale(1.1) rotate(-5deg); }
        .status-badge { transition: all 0.2s ease; }
        .status-badge:hover { transform: scale(1.05); }
        .agent-row { transition: all 0.2s ease; }
        .agent-row:hover { background: rgba(15,159,149,0.04); }
        .action-btn { transition: all 0.2s ease; background: transparent; }
        .action-btn:hover { background: rgba(0,0,0,0.05); transform: scale(1.1); }
        .dark .action-btn:hover { background: rgba(255,255,255,0.1); }
        .gradient-animate { background-size: 200% 200%; animation: gradientShift 6s ease infinite; }
        @keyframes gradientShift { 0%{background-position:0% 50%} 50%{background-position:100% 50%} 100%{background-position:0% 50%} }
        .fade-in { animation: fadeIn 0.5s ease both; }
        @keyframes fadeIn { from{opacity:0} to{opacity:1} }
        .tab-btn { transition: all 0.2s ease; cursor: pointer; }
        .tab-active { background: rgba(15,159,149,0.12); color: hsl(176, 75%, 30%); border-color: hsl(176, 75%, 36%); }
        .dark .tab-active { background: rgba(15,159,149,0.2); color: hsl(174, 61%, 62%); }
        [x-cloak] { display: none !important; }
        .chat-scroll::-webkit-scrollbar { width: 5px; }
        .chat-scroll::-webkit-scrollbar-track { background: transparent; }
        .chat-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
        .dark .chat-scroll::-webkit-scrollbar-thumb { background: #475569; }
        .chat-scroll { scrollbar-width: thin; scrollbar-color: #cbd5e1 transparent; }
        .dark .chat-scroll { scrollbar-color: #475569 transparent; }
    </style>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="mb-6 px-5 py-4 rounded-xl bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-400 flex items-center justify-between shadow-sm fade-in">
            <div class="flex items-center gap-3">
                <i class="fas fa-check-circle text-lg"></i>
                <span class="text-sm font-semibold">{{ session('success') }}</span>
            </div>
            <button onclick="this.parentElement.style.display='none'" class="text-green-500 hover:text-green-700 dark:hover:text-green-300">
                <i class="fas fa-times"></i>
            </button>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 px-5 py-4 rounded-xl bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-400 flex items-center justify-between shadow-sm fade-in">
            <div class="flex items-center gap-3">
                <i class="fas fa-exclamation-circle text-lg"></i>
                <span class="text-sm font-semibold">{{ session('error') }}</span>
            </div>
            <button onclick="this.parentElement.style.display='none'" class="text-red-500 hover:text-red-700 dark:hover:text-red-300">
                <i class="fas fa-times"></i>
            </button>
        </div>
    @endif

    {{-- Welcome Header --}}
    <div class="gradient-animate rounded-2xl overflow-hidden mb-6" style="background: linear-gradient(135deg, #0F9F95, #1B628C, #0F9F95);">
        <div class="p-6 md:p-8">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 bg-white/20 backdrop-blur rounded-2xl flex items-center justify-center shadow-lg shrink-0">
                        <span class="text-white font-bold text-3xl">{{ strtoupper(substr(auth()->user()->username, 0, 1)) }}</span>
                    </div>
                    <div>
                        <h1 class="text-2xl md:text-3xl font-bold text-white">Welcome back, {{ auth()->user()->username }}!</h1>
                        <p class="text-white/70 mt-1">Manage your data bundle platform from this central dashboard</p>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <div class="bg-white/10 backdrop-blur border border-white/20 rounded-xl px-6 py-4 text-center">
                        <p class="text-white/60 text-xs uppercase tracking-wider font-medium">Last Login</p>
                        <p class="text-white font-semibold text-sm mt-0.5">{{ $lastLogin }}</p>
                    </div>
                    <a href="{{ url('/') }}" target="_blank" class="bg-white/15 hover:bg-white/25 backdrop-blur border border-white/25 text-white px-4 py-2.5 rounded-xl font-semibold text-sm transition-all flex items-center gap-2 shrink-0">
                        <i class="fas fa-external-link-alt text-xs"></i>
                        Public Store
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Tab Navigation --}}
    <div x-data="{
        tab: 'overview',
        selectedMonth: '{{ now()->format('Y-m') }}',
        monthProfit: null,
        monthOrders: null,
        loading: false,
        monthLabel: 'This Month',
        fetchProfit() {
            this.loading = true;
            fetch('{{ route('admin.profit-month') }}?month=' + this.selectedMonth)
                .then(r => r.json())
                .then(d => {
                    this.monthProfit = d.profit;
                    this.monthOrders = d.orders;
                    var parts = this.selectedMonth.split('-');
                    var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
                    this.monthLabel = months[parseInt(parts[1]) - 1] + ' ' + parts[0];
                    this.loading = false;
                })
                .catch(() => { this.loading = false; });
        },
        // Chat tab state
        tickets: [],
        activeTicket: null,
        messages: [],
        replyInput: '',
        sending: false,
        chatLoading: false,
        chatSearch: '',
        chatFilter: 'all',
        chatView: 'list',
        fetchTickets() {
            this.chatLoading = true;
            fetch('{{ url('/admin/chat/tickets') }}')
                .then(r => r.json())
                .then(d => {
                    this.tickets = d.tickets;
                    if (this.activeTicket) {
                        var found = this.tickets.find(t => t.id === this.activeTicket.id);
                        if (found) this.activeTicket = found;
                    }
                    this.chatLoading = false;
                })
                .catch(() => { this.chatLoading = false; });
        },
        get openTicketCount() {
            return this.tickets.filter(t => t.status === 'open').length;
        },
        get filteredTickets() {
            var q = this.chatSearch.toLowerCase().trim();
            return this.tickets.filter(t => {
                if (this.chatFilter !== 'all' && t.status !== this.chatFilter) return false;
                if (!q) return true;
                return (t.phone && t.phone.toLowerCase().includes(q))
                    || (t.name && t.name.toLowerCase().includes(q))
                    || (t.email && t.email.toLowerCase().includes(q))
                    || (t.last_message && t.last_message.toLowerCase().includes(q));
            });
        },
        openTicket(ticket) {
            this.activeTicket = ticket;
            this.chatView = 'conversation';
            this.messages = [];
            fetch('{{ url('/admin/chat/tickets') }}/' + ticket.id)
                .then(r => r.json())
                .then(d => {
                    this.messages = d.messages;
                    this.$nextTick(() => {
                        var el = this.$refs.msgBox;
                        if (el) el.scrollTop = el.scrollHeight;
                    });
                })
                .catch(() => {});
        },
        backToList() {
            this.chatView = 'list';
            this.activeTicket = null;
            this.messages = [];
        },
        sendReply() {
            if (!this.replyInput.trim() || this.sending || !this.activeTicket) return;
            this.sending = true;
            fetch('{{ url('/admin/chat/tickets') }}/' + this.activeTicket.id + '/reply', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ message: this.replyInput.trim() })
            })
                .then(r => r.json())
                .then(d => {
                    if (d.success) {
                        this.messages.push(d.message);
                        this.replyInput = '';
                        var t = this.tickets.find(t => t.id === this.activeTicket.id);
                        if (t) {
                            t.status = 'replied';
                            t.last_message = d.message.message;
                            t.last_sender = 'admin';
                        }
                        if (this.activeTicket) this.activeTicket.status = 'replied';
                        this.$nextTick(() => {
                            var el = this.$refs.msgBox;
                            if (el) el.scrollTop = el.scrollHeight;
                        });
                    }
                    this.sending = false;
                })
                .catch(() => { this.sending = false; });
        },
        statusBadgeClass(status) {
            if (status === 'open') return 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400';
            if (status === 'replied') return 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400';
            return 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300';
        }
    }" class="space-y-6">
        <div class="flex flex-wrap gap-2 border-b border-gray-200 dark:border-gray-700 pb-3">
            <button @click="tab = 'overview'" :class="{ 'tab-active': tab === 'overview' }" class="tab-btn inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold border border-transparent text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700/50">
                <i class="fas fa-chart-pie text-xs"></i>
                Overview
            </button>
            <button @click="tab = 'agents'" :class="{ 'tab-active': tab === 'agents' }" class="tab-btn inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold border border-transparent text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700/50">
                <i class="fas fa-users text-xs"></i>
                Agents / Dealers
            </button>
            <button @click="tab = 'orders'" :class="{ 'tab-active': tab === 'orders' }" class="tab-btn inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold border border-transparent text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700/50">
                <i class="fas fa-shopping-cart text-xs"></i>
                Orders
            </button>
            <button @click="tab = 'profit'; if (monthProfit === null) fetchProfit()" :class="{ 'tab-active': tab === 'profit' }" class="tab-btn inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold border border-transparent text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700/50">
                <i class="fas fa-chart-line text-xs"></i>
                Profit
            </button>
            <button @click="tab = 'mashup'" :class="{ 'tab-active': tab === 'mashup' }" class="tab-btn inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold border border-transparent text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700/50">
                <i class="fas fa-bolt text-xs"></i>
                MTN Mashup
            </button>
            <button @click="tab = 'chat'; if (tickets.length === 0) fetchTickets()" :class="{ 'tab-active': tab === 'chat' }" class="tab-btn inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold border border-transparent text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700/50">
                <i class="fas fa-comments text-xs"></i>
                Chat
                <span x-show="openTicketCount > 0" x-text="openTicketCount" class="inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 rounded-full text-[10px] font-bold text-white" style="background:hsl(0,72%,51%)"></span>
            </button>
            <button @click="tab = 'refunds'" :class="{ 'tab-active': tab === 'refunds' }" class="tab-btn inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold border border-transparent text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700/50">
                <i class="fas fa-undo text-xs"></i>
                Refunds
            </button>
        </div>

        {{-- ===== TAB 1: OVERVIEW ===== --}}
        <div x-show="tab === 'overview'" x-cloak class="space-y-6">


            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                <div class="stat-card rounded-xl p-4 border-0 fade-in text-white" style="background:hsl(142,71%,45%)">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-wider text-white/80">Total Data Delivered</p>
                            <p class="text-3xl font-bold leading-tight mt-1 text-white">{{ number_format($totalData, 2) }} <span class="text-sm font-normal text-white/70">GB</span></p>
                        </div>
                        <div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0 stat-icon bg-white/20">
                            <i class="fas fa-database text-white"></i>
                        </div>
                    </div>
                </div>

                <div class="stat-card rounded-xl p-4 border-0 fade-in text-white" style="background:hsl(176,75%,36%)">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-wider text-white/80">API Wallet Balance</p>
                            <p class="text-3xl font-bold leading-tight mt-1 text-white">
                                @if($apiWalletBalance !== null)
                                    GHS {{ number_format($apiWalletBalance, 2) }}
                                @else
                                    <span class="text-sm font-normal text-white/60">{{ $apiBalanceError }}</span>
                                @endif
                            </p>
                        </div>
                        <div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0 stat-icon bg-white/20">
                            <i class="fas fa-cloud text-white"></i>
                        </div>
                    </div>
                </div>

                <div class="stat-card rounded-xl p-4 border-0 fade-in text-white" style="background:hsl(270,70%,60%)">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-wider text-white/80">Today's Profit</p>
                            <p class="text-3xl font-bold leading-tight mt-1 text-white">{{ number_format($profitToday['profit'], 2) }} <span class="text-sm font-normal text-white/70">GHS</span></p>
                            <p class="text-xs mt-0.5 text-white/70">{{ $profitToday['orders'] }} completed orders</p>
                        </div>
                        <div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0 stat-icon bg-white/20">
                            <i class="fas fa-chart-line text-white"></i>
                        </div>
                    </div>
                </div>

                <div class="stat-card rounded-xl p-4 border-0 fade-in text-white" style="background:hsl(39,80%,62%)">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-wider text-white/80">Wallet Health</p>
                            <p class="text-3xl font-bold leading-tight mt-1 text-white">GHS {{ number_format($agentDealerBalances + $SubAgentBalances, 2) }}</p>
                            <p class="text-xs mt-0.5 text-white/70">GHS {{ number_format($agentBalances, 2) }} agents + GHS {{ number_format($dealerBalances, 2) }} dealers + GHS {{ number_format($SubAgentBalances, 2) }} Sub Agents</p>
                        </div>
                        <div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0 stat-icon bg-white/20">
                            <i class="fas fa-wallet text-white"></i>
                        </div>
                    </div>
                </div>

                <div class="stat-card rounded-xl p-4 border-0 fade-in text-white" style="background:hsl(45,93%,47%)">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-wider text-white/80">Pending Orders</p>
                            <p class="text-3xl font-bold leading-tight mt-1 text-white">{{ $allPending }}</p>
                            <p class="text-xs mt-0.5 text-white/70">Awaiting attention</p>
                        </div>
                        <div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0 stat-icon bg-white/20">
                            <i class="fas fa-clock text-white"></i>
                        </div>
                    </div>
                </div>

                <div class="stat-card rounded-xl p-4 border-0 fade-in text-white" style="background:hsl(204,43%,55%)">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-wider text-white/80">Total Dealers, Agents &amp; Sub Agents</p>
                            <p class="text-3xl font-bold leading-tight mt-1 text-white">{{ $totalPeople }}</p>
                            <p class="text-xs mt-0.5 text-white/70">{{ $totalAgents }} agents, {{ $totalDealers }} dealers, {{ $SubAgents }} Sub Agents</p>
                        </div>
                        <div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0 stat-icon bg-white/20">
                            <i class="fas fa-users text-white"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== TAB 2: AGENTS / DEALERS ===== --}}
        <div x-show="tab === 'agents'" x-cloak class="space-y-6">
            <div class="bg-white/60 backdrop-blur-sm dark:bg-gray-800/80 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between flex-wrap gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg flex items-center justify-center" style="background:var(--card-secondary-icon)">
                            <i class="fas fa-users" style="color:hsl(39,100%,39%)"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Dealers &amp; Agents Management</h3>
                            <p class="text-gray-500 dark:text-gray-300 text-sm">Manage and monitor all dealers and agents</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="relative" style="display: flex; align-items: center;">
                            <i class="fas fa-search absolute text-gray-400 text-sm" style="left: 1rem; pointer-events: none;"></i>
                            <input type="text" id="agentSearch" placeholder="Search by ID or username..." oninput="filterAgents(this.value)" class="w-64 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-900 py-2.5 pr-4 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none transition-all" style="padding-left: 2.75rem;">
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm" id="agentTable">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-700">
                                <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Dealer/Agent</th>
                                <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Balance</th>
                                <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Role</th>
                                <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Orders</th>
                                <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Sub Agents</th>
                                <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Last Order</th>
                                <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Total Value</th>
                                <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                            @forelse($agents as $agent)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/20 transition-colors agent-row">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold shrink-0" style="background:{{ $agent['status'] === 'active' ? 'hsla(162,60%,40%,0.15)' : 'hsla(0,72%,51%,0.15)' }};color:{{ $agent['status'] === 'active' ? 'hsl(162,60%,40%)' : 'hsl(0,72%,51%)' }}">
                                                {{ strtoupper(substr($agent['username'], 0, 2)) }}
                                            </div>
                                            <div>
                                                <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ $agent['full_name'] }}</div>
                                                <div class="text-xs text-gray-500 dark:text-gray-300">@<span class="font-medium">{{ $agent['username'] }}</span> &middot; ID: {{ $agent['id'] }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm font-semibold" style="color:hsl(176,75%,36%)">{{ number_format($agent['balance'], 2) }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($agent['role'] === 'dealer')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-400">dealer</span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-teal-100 text-teal-700 dark:bg-teal-900/40 dark:text-teal-400">agent</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium status-badge 
                                            {{ $agent['status'] === 'active' ? 'bg-teal-100 text-teal-700 dark:bg-teal-900/40 dark:text-teal-400' : 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400' }}">
                                            <span class="w-1.5 h-1.5 rounded-full mr-1.5 {{ $agent['status'] === 'active' ? 'bg-teal-500' : 'bg-red-500' }}"></span>
                                            {{ ucfirst($agent['status']) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" style="background:hsla(204,43%,55%,0.1);color:hsl(204,43%,55%)">{{ $agent['total_orders'] }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm text-gray-600 dark:text-gray-300">{{ $agent['sub_agents_count'] }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($agent['last_order_date'])
                                            <div class="text-sm text-gray-600 dark:text-gray-300">{{ $agent['last_order_date']->diffForHumans() }}</div>
                                        @else
                                            <div class="text-sm text-gray-400">N/A</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm font-semibold" style="color:hsl(39,100%,39%)">{{ number_format($agent['total_value'], 2) }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-1">
                                            <button type="button"
                                                data-id="{{ $agent['id'] }}"
                                                data-username="{{ $agent['username'] }}"
                                                data-balance="{{ number_format($agent['balance'], 2) }}"
                                                onclick="openCreditModal(this)"
                                                class="action-btn inline-flex items-center justify-center w-8 h-8 rounded-lg" style="color:hsl(39,100%,39%)" title="Credit/Debit Balance">
                                                <i class="fas fa-coins text-sm"></i>
                                            </button>
                                            <button type="button" onclick="window.confirmAndSubmit('{{ $agent['role'] === 'agent' ? 'Upgrade this agent to dealer?' : 'Downgrade this dealer to agent?' }}', '{{ route('admin.toggle-role', $agent['id']) }}')" class="action-btn inline-flex items-center justify-center w-8 h-8 rounded-lg" style="color:{{ $agent['role'] === 'agent' ? 'hsl(142,71%,45%)' : 'hsl(204,43%,55%)' }}" title="{{ $agent['role'] === 'agent' ? 'Upgrade to Dealer' : 'Downgrade to Agent' }}">
                                                <i class="fas {{ $agent['role'] === 'agent' ? 'fa-arrow-up' : 'fa-arrow-down' }} text-sm"></i>
                                            </button>
                                            <button type="button" onclick="window.confirmAndSubmit('{{ $agent['status'] === 'active' ? 'Suspend this account?' : 'Unsuspend this account?' }}', '{{ route('admin.toggle-status', $agent['id']) }}')" class="action-btn inline-flex items-center justify-center w-8 h-8 rounded-lg" style="color:{{ $agent['status'] === 'active' ? 'hsl(0,72%,51%)' : 'hsl(142,71%,45%)' }}" title="{{ $agent['status'] === 'active' ? 'Suspend' : 'Unsuspend' }}">
                                                <i class="fas {{ $agent['status'] === 'active' ? 'fa-ban' : 'fa-check' }} text-sm"></i>
                                            </button>
                                            <button type="button"
                                                data-id="{{ $agent['id'] }}"
                                                data-username="{{ $agent['username'] }}"
                                                data-email="{{ $agent['email'] }}"
                                                data-balance="{{ number_format($agent['balance'], 2) }}"
                                                data-role="{{ $agent['role'] }}"
                                                data-status="{{ $agent['status'] }}"
                                                onclick="openEditModal(this)"
                                                class="action-btn inline-flex items-center justify-center w-8 h-8 rounded-lg" style="color:hsl(204,43%,55%)" title="Edit">
                                                <i class="fas fa-edit text-sm"></i>
                                            </button>
                                            <button type="button" onclick="window.confirmAndSubmit('Are you sure you want to delete this account? This cannot be undone.', '{{ route('admin.user-delete', $agent['id']) }}')" class="action-btn inline-flex items-center justify-center w-8 h-8 rounded-lg" style="color:hsl(0,72%,51%)" title="Delete Account">
                                                <i class="fas fa-trash-alt text-sm"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-6 py-8 text-center text-gray-500 dark:text-gray-300">
                                        <i class="fas fa-users-slash text-2xl mb-2 opacity-50"></i>
                                        <p>No dealers or agents found</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ===== TAB 3: ORDERS ===== --}}
        <div x-show="tab === 'orders'" x-cloak class="space-y-6"
            x-data="{ pending: {{ $allPending }}, processing: {{ $allProcessing }}, completed: {{ $allCompleted }}, failed: {{ $allFailed }} }"
            x-on:order-counts-updated.window="pending = $event.detail.counts.pending; processing = $event.detail.counts.processing; completed = $event.detail.counts.completed; failed = $event.detail.counts.failed">
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                <div class="stat-card rounded-xl p-4 border-0 fade-in text-white" style="background:hsl(45,93%,47%)">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-wider text-white/80">Pending</p>
                            <p class="text-3xl font-bold leading-tight mt-1 text-white" x-text="pending"></p>
                        </div>
                        <div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0 stat-icon bg-white/20">
                            <i class="fas fa-clock text-white"></i>
                        </div>
                    </div>
                </div>
                <div class="stat-card rounded-xl p-4 border-0 fade-in text-white" style="background:hsl(204,43%,55%)">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-wider text-white/80">Processing</p>
                            <p class="text-3xl font-bold leading-tight mt-1 text-white" x-text="processing"></p>
                        </div>
                        <div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0 stat-icon bg-white/20">
                            <i class="fas fa-spinner text-white" :class="{ 'fa-spin': processing > 0 }"></i>
                        </div>
                    </div>
                </div>
                <div class="stat-card rounded-xl p-4 border-0 fade-in text-white" style="background:hsl(142,71%,45%)">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-wider text-white/80">Completed</p>
                            <p class="text-3xl font-bold leading-tight mt-1 text-white" x-text="completed"></p>
                        </div>
                        <div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0 stat-icon bg-white/20">
                            <i class="fas fa-check-circle text-white"></i>
                        </div>
                    </div>
                </div>
                <div class="stat-card rounded-xl p-4 border-0 fade-in text-white" style="background:hsl(0,72%,51%)">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-wider text-white/80">Failed</p>
                            <p class="text-3xl font-bold leading-tight mt-1 text-white" x-text="failed"></p>
                        </div>
                        <div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0 stat-icon bg-white/20">
                            <i class="fas fa-exclamation-circle text-white"></i>
                        </div>
                    </div>
                </div>
            </div>

            <livewire:order-manager />
        </div>

        {{-- ===== TAB 4: PROFIT ===== --}}
        <div x-show="tab === 'profit'" x-cloak class="space-y-6">
            {{-- Month Picker --}}
            <div class="flex items-center gap-4 bg-white/60 dark:bg-gray-800/60 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-2">
                    <i class="fas fa-calendar-alt text-sm" style="color:hsl(176,75%,36%)"></i>
                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">Pick a month:</span>
                </div>
                <input type="month"
                    x-ref="monthPicker"
                    x-model="selectedMonth"
                    @change="fetchProfit()"
                    class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-1.5 text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none transition-all">
                <div x-show="loading" x-cloak class="text-sm text-gray-500">
                    <i class="fas fa-spinner fa-spin"></i> Loading...
                </div>
                <div class="ml-auto text-xs text-gray-400">Showing profit completed in selected month</div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 relative z-20">
                <div class="stat-card rounded-xl p-4 border-0 fade-in text-white relative" style="background:hsl(176,75%,36%)">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-wider text-white/80">
                                <span x-text="monthLabel"></span> Profit
                            </p>
                            <p class="text-3xl font-bold leading-tight mt-1 text-white">
                                <span x-text="monthProfit !== null ? 'GHS ' + monthProfit.toLocaleString('en', {minimumFractionDigits:2,maximumFractionDigits:2}) : '—'"></span>
                            </p>
                            <p class="text-xs mt-0.5 text-white/70">
                                <span x-text="monthOrders !== null ? monthOrders + ' completed orders' : ''"></span>
                            </p>
                        </div>
                        <div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0 stat-icon bg-white/20">
                            <i class="fas fa-calendar-alt text-white"></i>
                        </div>
                    </div>
                </div>

                <div class="stat-card rounded-xl p-4 border-0 fade-in text-white relative" style="background:hsl(270,70%,60%)" x-data="{ open: false }" :class="{ 'z-50': open, 'z-10': !open }">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <p class="text-xs font-semibold uppercase tracking-wider text-white/80">Today's Profit</p>
                                <button @click.prevent="open = !open" @click.outside="open = false" class="text-white/60 hover:text-white transition-colors bg-white/10 hover:bg-white/20 rounded-md w-5 h-5 flex items-center justify-center">
                                    <i class="fas fa-chevron-down text-[10px]" :class="{ 'fa-chevron-up': open, 'fa-chevron-down': !open }"></i>
                                </button>
                            </div>
                            <p class="text-3xl font-bold leading-tight mt-1 text-white">{{ number_format($profitToday['profit'], 2) }} <span class="text-sm font-normal text-white/70">GHS</span></p>
                            <p class="text-xs mt-0.5 text-white/70">{{ $profitToday['orders'] }} completed orders</p>
                        </div>
                        <div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0 stat-icon bg-white/20">
                            <i class="fas fa-calendar-day text-white"></i>
                        </div>
                    </div>
                    {{-- Dropdown --}}
                    <div x-show="open" x-cloak x-transition.opacity
                        class="absolute top-full left-0 mt-2 w-full bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-2 z-50 max-h-64 overflow-y-auto">
                        <div class="text-[10px] font-bold text-gray-600 dark:text-gray-300 uppercase px-2 pb-1.5 mb-1 border-b border-gray-100 dark:border-gray-700 tracking-wider">Current Week Breakdown</div>
                        @foreach($breakdowns['daily'] as $label => $amount)
                        <div class="flex justify-between items-center px-2 py-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700/50 transition-colors">
                            <span class="text-sm text-gray-900 dark:text-gray-100">{{ $label }}</span>
                            <span class="text-sm font-semibold {{ $amount > 0 ? 'text-green-600 dark:text-green-500' : 'text-gray-500 dark:text-gray-400' }}">{{ $amount > 0 ? 'GHS ' . number_format($amount, 2) : '-' }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>

                <div class="stat-card rounded-xl p-4 border-0 fade-in text-white relative" style="background:hsl(204,43%,55%)" x-data="{ open: false }" :class="{ 'z-50': open, 'z-10': !open }">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <p class="text-xs font-semibold uppercase tracking-wider text-white/80">This Week Profit</p>
                                <button @click.prevent="open = !open" @click.outside="open = false" class="text-white/60 hover:text-white transition-colors bg-white/10 hover:bg-white/20 rounded-md w-5 h-5 flex items-center justify-center">
                                    <i class="fas fa-chevron-down text-[10px]" :class="{ 'fa-chevron-up': open, 'fa-chevron-down': !open }"></i>
                                </button>
                            </div>
                            <p class="text-3xl font-bold leading-tight mt-1 text-white">{{ number_format($profitWeek['profit'], 2) }} <span class="text-sm font-normal text-white/70">GHS</span></p>
                            <p class="text-xs mt-0.5 text-white/70">{{ $profitWeek['orders'] }} completed orders</p>
                        </div>
                        <div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0 stat-icon bg-white/20">
                            <i class="fas fa-calendar-week text-white"></i>
                        </div>
                    </div>
                    {{-- Dropdown --}}
                    <div x-show="open" x-cloak x-transition.opacity
                        class="absolute top-full left-0 mt-2 w-full bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-2 z-50 max-h-64 overflow-y-auto">
                        <div class="text-[10px] font-bold text-gray-600 dark:text-gray-300 uppercase px-2 pb-1.5 mb-1 border-b border-gray-100 dark:border-gray-700 tracking-wider">Current Month Breakdown</div>
                        @foreach($breakdowns['weekly'] as $label => $amount)
                        <div class="flex justify-between items-center px-2 py-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700/50 transition-colors">
                            <span class="text-sm text-gray-900 dark:text-gray-100">{{ $label }}</span>
                            <span class="text-sm font-semibold {{ $amount > 0 ? 'text-green-600 dark:text-green-500' : 'text-gray-400 dark:text-gray-500' }}">{{ $amount > 0 ? 'GHS ' . number_format($amount, 2) : '-' }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>

                <div class="stat-card rounded-xl p-4 border-0 fade-in text-white relative" style="background:hsl(39,80%,62%)" x-data="{ open: false }" :class="{ 'z-50': open, 'z-10': !open }">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <p class="text-xs font-semibold uppercase tracking-wider text-white/80">This Year Profit</p>
                                <button @click.prevent="open = !open" @click.outside="open = false" class="text-white/60 hover:text-white transition-colors bg-white/10 hover:bg-white/20 rounded-md w-5 h-5 flex items-center justify-center">
                                    <i class="fas fa-chevron-down text-[10px]" :class="{ 'fa-chevron-up': open, 'fa-chevron-down': !open }"></i>
                                </button>
                            </div>
                            <p class="text-3xl font-bold leading-tight mt-1 text-white">{{ number_format($profitYear['profit'], 2) }} <span class="text-sm font-normal text-white/70">GHS</span></p>
                            <p class="text-xs mt-0.5 text-white/70">{{ $profitYear['orders'] }} completed orders</p>
                        </div>
                        <div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0 stat-icon bg-white/20">
                            <i class="fas fa-calendar-check text-white"></i>
                        </div>
                    </div>
                    {{-- Dropdown --}}
                    <div x-show="open" x-cloak x-transition.opacity
                        class="absolute top-full left-0 mt-2 w-full bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-2 z-50 max-h-64 overflow-y-auto">
                        <div class="text-[10px] font-bold text-gray-600 dark:text-gray-300 uppercase px-2 pb-1.5 mb-1 border-b border-gray-100 dark:border-gray-700 tracking-wider">All Time Breakdown</div>
                        @foreach($breakdowns['yearly'] as $label => $amount)
                        <div class="flex justify-between items-center px-2 py-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700/50 transition-colors">
                            <span class="text-sm text-gray-900 dark:text-gray-100">{{ $label }}</span>
                            <span class="text-sm font-semibold {{ $amount > 0 ? 'text-green-600 dark:text-green-500' : 'text-gray-400 dark:text-gray-500' }}">{{ $amount > 0 ? 'GHS ' . number_format($amount, 2) : '-' }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="stat-card rounded-xl p-4 border-0 fade-in text-white" style="background:hsl(176,75%,36%)">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-wider text-white/80">Agents &amp; Dealers Balance</p>
                            <p class="text-3xl font-bold leading-tight mt-1 text-white">GHS {{ number_format($agentDealerBalances, 2) }}</p>
                            <p class="text-xs mt-0.5 text-white/70">GHS {{ number_format($agentBalances, 2) }} agents + GHS {{ number_format($dealerBalances, 2) }} dealers</p>
                        </div>
                        <div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0 stat-icon bg-white/20">
                            <i class="fas fa-piggy-bank text-white"></i>
                        </div>
                    </div>
                </div>
                <div class="stat-card rounded-xl p-4 border-0 fade-in text-white" style="background:hsl(0,72%,51%)">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-wider text-white/80">Balance Used Today</p>
                            <p class="text-3xl font-bold leading-tight mt-1 text-white">GHS {{ number_format($todayAgentDealerSpend, 2) }}</p>
                            <p class="text-xs mt-0.5 text-white/70">{{ $todayDebitCount }} debit records</p>
                        </div>
                        <div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0 stat-icon bg-white/20">
                            <i class="fas fa-shopping-cart text-white"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== TAB 5: MTN MASHUP ===== --}}
        <div x-show="tab === 'mashup'" x-cloak
             x-data="{
                selected: [],
                bulkStatus: 'processing',
                bulkUpdating: false,
                bulkMsg: null,
                toggleAll() {
                    const allIds = JSON.parse(this.$refs.orderData.dataset.orders).map(o => o.key);
                    this.selected = this.selected.length === allIds.length ? [] : [...allIds];
                },
                toggleOrder(key) {
                    const idx = this.selected.indexOf(key);
                    idx === -1 ? this.selected.push(key) : this.selected.splice(idx, 1);
                },
                async applyBulkStatus() {
                    if (this.selected.length === 0) return;
                    this.bulkUpdating = true;
                    this.bulkMsg = null;
                    const orders = JSON.parse(this.$refs.orderData.dataset.orders);
                    const selectedOrders = orders.filter(o => this.selected.includes(o.key)).map(o => ({ id: o.id, source: o.source }));
                    try {
                        const res = await fetch('{{ route('admin.mashup.bulk-status') }}', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                            body: JSON.stringify({ selected: selectedOrders, status: this.bulkStatus })
                        });
                        const data = await res.json();
                        if (data.success) {
                            this.bulkMsg = data.updated + ' order(s) updated to ' + this.bulkStatus;
                            this.selected = [];
                            setTimeout(() => { location.reload(); }, 1200);
                        }
                    } catch(e) {
                        this.bulkMsg = 'Error updating orders';
                    } finally {
                        this.bulkUpdating = false;
                    }
                }
             }"
             class="space-y-4">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background:hsl(39,80%,62%)">
                    <i class="fas fa-bolt text-white"></i>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">MTN Mashup Orders</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">All MTN Mashup orders from agents, dealers, Sub-Agents, and public customers</p>
                </div>
            </div>

            {{-- Bulk action bar --}}
            <div x-show="selected.length > 0" x-cloak
                 class="flex items-center gap-3 px-4 py-3 rounded-xl bg-primary-50 dark:bg-primary-900/20 border border-primary-200 dark:border-primary-800 transition-all">
                <span class="text-sm font-semibold text-gray-700 dark:text-gray-200" x-text="selected.length + ' order(s) selected'"></span>
                <select x-model="bulkStatus"
                    class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-1.5 text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 outline-none">
                    <option value="processing">Processing</option>
                    <option value="delivered">Delivered</option>
                    <option value="failed">Failed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
                <button @click="applyBulkStatus()" :disabled="bulkUpdating"
                    class="px-4 py-1.5 rounded-lg text-sm font-semibold text-white transition-all disabled:opacity-50"
                    style="background:hsl(176,75%,36%)"
                    onmouseover="this.style.background='hsl(176,75%,30%)'"
                    onmouseout="this.style.background='hsl(176,75%,36%)'">
                    <i class="fas fa-sync-alt text-xs" :class="{ 'fa-spin': bulkUpdating }"></i>
                    <span x-text="bulkUpdating ? 'Updating...' : 'Update Status'"></span>
                </button>
                <button @click="selected = []; bulkMsg = null"
                    class="px-3 py-1.5 rounded-lg text-sm font-semibold text-gray-600 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition-all">
                    Clear
                </button>
                <template x-if="bulkMsg">
                    <span class="text-sm font-medium" :class="bulkMsg.includes('Error') ? 'text-red-600' : 'text-emerald-600'" x-text="bulkMsg"></span>
                </template>
            </div>

            <div class="overflow-x-auto rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-sm">
                <table class="min-w-max w-full whitespace-nowrap text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                            <th class="text-center px-3 py-3 w-10">
                                <input type="checkbox" @change="toggleAll()"
                                    :checked="selected.length > 0 && selected.length === JSON.parse($refs.orderData.dataset.orders).length"
                                    class="rounded border-gray-300 text-primary-600 focus:ring-primary-500 cursor-pointer">
                            </th>
                            <th class="text-left px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">#</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Source</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">ID</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Customer</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Package</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Beneficiary</th>
                            <th class="text-right px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Amount</th>
                            <th class="text-center px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Status</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($mtnMashupOrders as $i => $order)
                            @php $orderKey = $order['source'] . '-' . $order['id']; @endphp
                            <tr class="border-b border-gray-100 dark:border-gray-700/50 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors"
                                :class="{ 'bg-primary-50/50 dark:bg-primary-900/10': selected.includes('{{ $orderKey }}') }">
                                <td class="text-center px-3 py-3">
                                    <input type="checkbox" :checked="selected.includes('{{ $orderKey }}')" @change="toggleOrder('{{ $orderKey }}')"
                                        class="rounded border-gray-300 text-primary-600 focus:ring-primary-500 cursor-pointer">
                                </td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs">{{ $i + 1 }}</td>
                                <td class="px-4 py-3">
                                    @php
                                        $sourceColors = [
                                            'Agent Order' => 'hsl(270,70%,60%)',
                                            'Store Order' => 'hsl(204,43%,55%)',
                                            'Public Customer' => 'hsl(176,75%,36%)',
                                        ];
                                        $color = $sourceColors[$order['source']] ?? 'hsl(0,0%,50%)';
                                    @endphp
                                    <span class="inline-block px-2.5 py-0.5 rounded-full text-[11px] font-semibold text-white" style="background:{{ $color }}">
                                        {{ $order['source'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 font-mono text-xs text-gray-700 dark:text-gray-300">#{{ $order['id'] }}</td>
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $order['agent_name'] }}</td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $order['package_name'] }}</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $order['beneficiary'] }}</td>
                                <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-white">GHS {{ number_format($order['amount'], 2) }}</td>
                                <td class="px-4 py-3 text-center">
                                    @php
                                        $statusColors = [
                                            'pending' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400',
                                            'processing' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400',
                                            'completed' => 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400',
                                            'delivered' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400',
                                            'failed' => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400',
                                            'cancelled' => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400',
                                        ];
                                    @endphp
                                    <span class="inline-block px-2.5 py-0.5 rounded-full text-[11px] font-semibold {{ $statusColors[$order['status']] ?? 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300' }}">
                                        {{ ucfirst($order['status']) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400">{{ $order['created_at'] instanceof \Carbon\Carbon ? $order['created_at']->format('M d, Y h:i A') : $order['created_at'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                    <i class="fas fa-inbox text-2xl mb-2 block opacity-50"></i>
                                    No MTN Mashup orders yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                {{-- Hidden JSON dataset for Alpine --}}
                <div x-ref="orderData" data-orders='@json($mtnMashupOrderKeys)' class="hidden"></div>
            </div>
        </div>

        {{-- ===== TAB 6: CHAT ===== --}}
        <div x-show="tab === 'chat'" x-cloak>
            <div style="background:#fff;border:1px solid #e5e7eb;border-radius:16px;box-shadow:0 4px 20px rgba(0,0,0,.06);overflow:hidden;height:calc(100vh - 260px);min-height:520px;display:flex;flex-direction:column;">

                {{-- Header bar --}}
                <div style="padding:14px 20px;border-bottom:1px solid #e5e7eb;display:flex;align-items:center;justify-content:space-between;background:#f9fafb;flex-shrink:0;">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <div style="width:36px;height:36px;border-radius:10px;background:hsl(176,75%,36%);display:flex;align-items:center;justify-content:center;">
                            <i class="fas fa-comments" style="color:#fff;font-size:15px;"></i>
                        </div>
                        <div>
                            <p style="font-size:15px;font-weight:700;color:#111827;margin:0;">Support Chat</p>
                            <p style="font-size:11px;color:#6b7280;margin:0;" x-text="tickets.length + ' conversations'"></p>
                        </div>
                    </div>
                    <button @click="fetchTickets()" style="padding:6px 14px;border-radius:8px;background:hsl(176,75%,36%);color:#fff;font-size:12px;font-weight:600;border:none;cursor:pointer;display:flex;align-items:center;gap:6px;">
                        <i class="fas fa-sync-alt" :class="{'fa-spin': chatLoading}" style="font-size:11px;"></i>
                        Refresh
                    </button>
                </div>

                {{-- Body: two-pane --}}
                <div style="display:flex;flex:1;min-height:0;overflow:hidden;">

                    {{-- LEFT: Ticket list --}}
                    <div style="width:320px;min-width:280px;border-right:1px solid #e5e7eb;display:flex;flex-direction:column;flex-shrink:0;background:#fafafa;"
                        :class="{ 'hidden': chatView === 'conversation' }"
                        style="display: flex !important;">
                        <div class="md:block" :style="chatView === 'conversation' ? 'display:none' : 'display:flex;flex-direction:column;flex-shrink:0;width:320px;min-width:280px;border-right:1px solid #e5e7eb;background:#fafafa;'">

                        {{-- Search --}}
                        <div style="padding:12px 14px;border-bottom:1px solid #e5e7eb;">
                            <div style="position:relative;">
                                <i class="fas fa-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#9ca3af;font-size:12px;"></i>
                                <input type="text" x-model="chatSearch" placeholder="Search phone or name..."
                                    style="width:100%;border:1px solid #e5e7eb;border-radius:10px;padding:8px 12px 8px 34px;font-size:13px;color:#111827;background:#fff;outline:none;box-sizing:border-box;">
                            </div>
                            {{-- Filters --}}
                            <div style="display:flex;gap:6px;margin-top:8px;flex-wrap:wrap;">
                                <template x-for="f in [{v:'all',l:'All'},{v:'open',l:'Open'},{v:'replied',l:'Replied'},{v:'closed',l:'Closed'}]" :key="f.v">
                                    <button @click="chatFilter = f.v"
                                        :style="chatFilter === f.v
                                            ? 'background:hsl(176,75%,36%);color:#fff;border-color:transparent;'
                                            : 'background:#fff;color:#374151;border-color:#e5e7eb;'"
                                        style="padding:4px 12px;border-radius:8px;font-size:11px;font-weight:600;border:1px solid;cursor:pointer;transition:all .15s;">
                                        <span x-text="f.l"></span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        {{-- List --}}
                        <div style="flex:1;overflow-y:auto;">
                            <template x-if="chatLoading && tickets.length === 0">
                                <div style="display:flex;align-items:center;justify-content:center;padding:48px 0;">
                                    <div style="width:24px;height:24px;border:2px solid hsl(176,75%,36%);border-top-color:transparent;border-radius:50%;animation:spin 0.7s linear infinite;"></div>
                                </div>
                            </template>
                            <template x-if="!chatLoading && filteredTickets.length === 0">
                                <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;padding:48px 0;color:#9ca3af;">
                                    <i class="fas fa-inbox" style="font-size:28px;margin-bottom:10px;opacity:.5;"></i>
                                    <p style="font-size:13px;font-weight:500;margin:0;">No conversations found</p>
                                </div>
                            </template>
                            <template x-for="ticket in filteredTickets" :key="ticket.id">
                                <div @click="openTicket(ticket)"
                                    :style="activeTicket && activeTicket.id === ticket.id
                                        ? 'background:#ecfdf5;border-left:3px solid hsl(176,75%,36%);'
                                        : 'background:transparent;border-left:3px solid transparent;'"
                                    style="padding:14px 16px;cursor:pointer;border-bottom:1px solid #f3f4f6;transition:background .15s;"
                                    onmouseover="if(!this.classList.contains('active-ticket')) this.style.background='#f9fafb'"
                                    onmouseout="if(!this.classList.contains('active-ticket')) this.style.background='transparent'">
                                    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;">
                                        <div style="display:flex;align-items:flex-start;gap:10px;min-width:0;flex:1;">
                                            {{-- Avatar --}}
                                            <div :style="'width:38px;height:38px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:700;color:#fff;flex-shrink:0;background:hsl(' + (ticket.id * 50 % 360) + ',55%,50%)'">
                                                <span x-text="(ticket.name || ticket.phone || '?').charAt(0).toUpperCase()"></span>
                                            </div>
                                            <div style="min-width:0;flex:1;">
                                                <div style="display:flex;align-items:center;gap:6px;">
                                                    <span style="font-size:13px;font-weight:700;color:#111827;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" x-text="ticket.name || ticket.phone || 'Unknown'"></span>
                                                    <span x-show="ticket.status === 'open'" style="width:7px;height:7px;border-radius:50%;background:#ef4444;flex-shrink:0;"></span>
                                                </div>
                                                <p style="font-size:12px;color:#6b7280;margin:2px 0 0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" x-text="ticket.last_message || 'No messages yet'"></p>
                                            </div>
                                        </div>
                                        <div style="display:flex;flex-direction:column;align-items:flex-end;gap:4px;flex-shrink:0;">
                                            <span :style="ticket.status === 'open'
                                                    ? 'background:#fef3c7;color:#92400e;'
                                                    : ticket.status === 'replied'
                                                        ? 'background:#d1fae5;color:#065f46;'
                                                        : 'background:#f3f4f6;color:#374151;'"
                                                style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;text-transform:capitalize;"
                                                x-text="ticket.status"></span>
                                            <span style="font-size:10px;color:#9ca3af;" x-text="ticket.last_activity"></span>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                        </div>
                    </div>

                    {{-- RIGHT: Conversation --}}
                    <div style="flex:1;display:flex;flex-direction:column;min-width:0;"
                        :style="chatView === 'list' ? 'display:none' : 'flex:1;display:flex;flex-direction:column;min-width:0;'"
                        class="md:flex">

                        {{-- Empty state --}}
                        <template x-if="!activeTicket">
                            <div style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;background:#f9fafb;color:#9ca3af;">
                                <div style="width:72px;height:72px;border-radius:50%;background:#f3f4f6;display:flex;align-items:center;justify-content:center;margin-bottom:16px;">
                                    <i class="fas fa-comment-dots" style="font-size:30px;opacity:.4;"></i>
                                </div>
                                <p style="font-size:14px;font-weight:600;color:#374151;margin:0;">Select a conversation</p>
                                <p style="font-size:12px;color:#9ca3af;margin:6px 0 0;">Choose a ticket from the left panel to read messages</p>
                            </div>
                        </template>

                        {{-- Active conversation --}}
                        <template x-if="activeTicket">
                            <div style="flex:1;display:flex;flex-direction:column;min-height:0;">

                                {{-- Convo header --}}
                                <div style="padding:12px 18px;border-bottom:1px solid #e5e7eb;display:flex;align-items:center;justify-content:space-between;background:#fff;flex-shrink:0;">
                                    <div style="display:flex;align-items:center;gap:12px;min-width:0;">
                                        <button @click="backToList()" class="md:hidden" style="width:32px;height:32px;border-radius:8px;border:1px solid #e5e7eb;background:#fff;display:flex;align-items:center;justify-content:center;cursor:pointer;color:#6b7280;">
                                            <i class="fas fa-arrow-left" style="font-size:12px;"></i>
                                        </button>
                                        <div :style="'width:40px;height:40px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:15px;font-weight:700;color:#fff;flex-shrink:0;background:hsl(' + (activeTicket.id * 50 % 360) + ',55%,50%)'">
                                            <span x-text="(activeTicket.name || activeTicket.phone || '?').charAt(0).toUpperCase()"></span>
                                        </div>
                                        <div style="min-width:0;">
                                            <p style="font-size:14px;font-weight:700;color:#111827;margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" x-text="activeTicket.name || activeTicket.phone || 'Unknown'"></p>
                                            <p style="font-size:11px;color:#6b7280;margin:0;" x-text="activeTicket.email || activeTicket.phone || ''"></p>
                                        </div>
                                    </div>
                                    <span :style="activeTicket.status === 'open'
                                            ? 'background:#fef3c7;color:#92400e;'
                                            : activeTicket.status === 'replied'
                                                ? 'background:#d1fae5;color:#065f46;'
                                                : 'background:#f3f4f6;color:#374151;'"
                                        style="font-size:11px;font-weight:700;padding:4px 12px;border-radius:20px;text-transform:capitalize;flex-shrink:0;"
                                        x-text="activeTicket.status"></span>
                                </div>

                                {{-- Messages area --}}
                                <div x-ref="msgBox" style="flex:1;overflow-y:auto;padding:20px 18px;background:#f9fafb;display:flex;flex-direction:column;gap:12px;scroll-behavior:smooth;">
                                    <template x-for="msg in messages" :key="msg.id">
                                        <div :style="msg.sender_type === 'user' ? 'display:flex;justify-content:flex-end;' : 'display:flex;justify-content:flex-start;'">
                                            {{-- Sender label for non-user --}}
                                            <div :style="msg.sender_type === 'user' ? 'max-width:72%;' : 'max-width:72%;display:flex;flex-direction:column;gap:2px;'">
                                                <p x-show="msg.sender_type !== 'user'"
                                                    :style="msg.sender_type === 'admin' ? 'font-size:10px;font-weight:700;color:hsl(176,75%,30%);margin:0 0 2px 10px;' : 'font-size:10px;font-weight:700;color:#d97706;margin:0 0 2px 10px;'"
                                                    x-text="msg.sender_type === 'admin' ? 'Support (You)' : '🤖 Bot'"></p>
                                                {{-- Bubble --}}
                                                <div :style="msg.sender_type === 'user'
                                                        ? 'background:hsl(176,75%,36%);color:#fff;border-radius:18px 18px 4px 18px;padding:10px 16px;'
                                                        : msg.sender_type === 'admin'
                                                            ? 'background:#fff;border:1.5px solid hsl(176,75%,80%);color:#111827;border-radius:18px 18px 18px 4px;padding:10px 16px;box-shadow:0 1px 4px rgba(0,0,0,.06);'
                                                            : 'background:#fff;border:1.5px solid #e5e7eb;color:#111827;border-radius:18px 18px 18px 4px;padding:10px 16px;box-shadow:0 1px 4px rgba(0,0,0,.06);'">
                                                    <p style="font-size:13px;line-height:1.6;margin:0;word-break:break-word;" x-text="msg.message"></p>
                                                    <p :style="msg.sender_type === 'user' ? 'color:rgba(255,255,255,.6);' : 'color:#9ca3af;'"
                                                        style="font-size:10px;margin:4px 0 0;text-align:right;" x-text="msg.created_at"></p>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>

                                {{-- Reply box --}}
                                <div style="padding:12px 18px;border-top:1px solid #e5e7eb;background:#fff;flex-shrink:0;">
                                    <form @submit.prevent="sendReply()" style="display:flex;gap:10px;align-items:flex-end;">
                                        <input type="text" x-model="replyInput" placeholder="Type your reply here..."
                                            :disabled="sending"
                                            style="flex:1;border:1.5px solid #e5e7eb;border-radius:12px;padding:10px 16px;font-size:13px;color:#111827;background:#f9fafb;outline:none;transition:border-color .15s;"
                                            onfocus="this.style.borderColor='hsl(176,75%,36%)'"
                                            onblur="this.style.borderColor='#e5e7eb'">
                                        <button type="submit" :disabled="sending || !replyInput.trim()"
                                            style="padding:10px 22px;border-radius:12px;background:hsl(176,75%,36%);color:#fff;font-size:13px;font-weight:600;border:none;cursor:pointer;display:flex;align-items:center;gap:8px;flex-shrink:0;opacity:1;transition:opacity .15s;"
                                            :style="(sending || !replyInput.trim()) ? 'opacity:.4;cursor:not-allowed;' : 'opacity:1;cursor:pointer;'"
                                            onmouseover="if(!this.disabled) this.style.background='hsl(176,75%,30%)'"
                                            onmouseout="this.style.background='hsl(176,75%,36%)'">
                                            <i class="fas fa-paper-plane" :class="{'fa-spin': sending}" style="font-size:12px;"></i>
                                            <span>Send</span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </template>
                    </div>

                </div>
            </div>
        </div>

        {{-- ===== TAB 7: REFUNDS ===== --}}
        <div x-show="tab === 'refunds'" x-cloak class="space-y-6">
            <livewire:dashboard-refund-manager />
        </div>

        {{-- Credit/Debit Modal --}}
        <div id="creditModal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-md mx-4 overflow-hidden" onclick="event.stopPropagation()">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Credit / Debit Balance</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-300" id="creditModalSubtitle">Adjust account balance</p>
                    </div>
                    <button type="button" onclick="closeCreditModal()" class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition-all">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="px-6 py-5 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Username</label>
                            <p class="text-sm font-medium text-gray-900 dark:text-white" id="creditModalUsername">-</p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Current Balance</label>
                            <p class="text-sm font-medium text-gray-900 dark:text-white" id="creditModalBalance">-</p>
                        </div>
                    </div>
                    <hr class="border-gray-200 dark:border-gray-700">
                    <div>
                        <label for="creditAmount" class="block text-sm font-semibold text-gray-900 dark:text-white mb-1">Amount (GHS)</label>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Positive value = credit, negative value = debit.</p>
                        <div class="flex items-center rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 focus-within:ring-2 focus-within:ring-primary-500 focus-within:border-primary-500 transition-all">
                            <input type="number" step="0.01" name="creditAmount" id="creditAmount" placeholder="0.00" class="flex-1 bg-transparent border-0 px-4 py-4 text-sm text-gray-900 dark:text-white outline-none">
                        </div>
                    </div>
                </div>
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3">
                    <button type="button" onclick="closeCreditModal()" class="px-4 py-2 text-sm font-semibold text-gray-700 dark:text-white bg-gray-100 dark:bg-white/10 hover:bg-gray-200 dark:hover:bg-white/20 rounded-lg transition-all">Cancel</button>
                    <button type="button" onclick="submitCredit()" class="px-4 py-2 text-sm font-semibold text-white rounded-lg transition-all" style="background:hsl(39,100%,39%)" onmouseover="this.style.background='hsl(39,100%,33%)'" onmouseout="this.style.background='hsl(39,100%,39%)'">Submit</button>
                </div>
            </div>
        </div>

        <div id="editUserModal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg mx-4 overflow-hidden" onclick="event.stopPropagation()">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Edit User</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-300" id="editModalSubtitle">Update user details</p>
                    </div>
                    <button type="button" onclick="closeEditModal()" class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition-all">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form action="{{ route('admin.user-update', 0) }}" method="POST" id="editUserForm">
                    @csrf
                    <input type="hidden" name="_method" value="POST">
                    <div class="px-6 py-5 space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Username</label>
                                <p class="text-sm font-medium text-gray-900 dark:text-white" id="modalUsername">-</p>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Email</label>
                                <p class="text-sm font-medium text-gray-900 dark:text-white" id="modalEmail">-</p>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Role</label>
                                <p class="text-sm font-medium text-gray-900 dark:text-white" id="modalRole">-</p>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Balance</label>
                                <p class="text-sm font-medium text-gray-900 dark:text-white" id="modalBalance">-</p>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Status</label>
                                <p class="text-sm font-medium text-gray-900 dark:text-white" id="modalStatus">-</p>
                            </div>
                        </div>
                        <hr class="border-gray-200 dark:border-gray-700">
                        <div class="pt-2">
                            <label for="modalPassword" class="block text-sm font-semibold text-gray-900 dark:text-white mb-2">New Password</label>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Leave blank to keep unchanged.</p>
                            <div class="flex items-center rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 focus-within:ring-2 focus-within:ring-primary-500 focus-within:border-primary-500 transition-all mb-4">
                                <input type="password" name="password" id="modalPassword" placeholder="New password" minlength="6" maxlength="12" class="flex-1 bg-transparent border-transparent focus:border-transparent focus:ring-0 px-4 py-4 text-sm text-gray-900 dark:text-white outline-none">
                                <button type="button" onclick="togglePassword(this)" class="flex items-center justify-center w-10 h-10 mr-1 rounded-md text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 hover:text-gray-600 dark:hover:text-gray-300 transition-all shrink-0" tabindex="-1">
                                    <i class="fas fa-eye text-sm"></i>
                                </button>
                            </div>
                            <div class="flex items-center rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 focus-within:ring-2 focus-within:ring-primary-500 focus-within:border-primary-500 transition-all">
                                <input type="password" name="password_confirmation" id="modalPasswordConfirmation" placeholder="Confirm new password" minlength="6" maxlength="12" class="flex-1 bg-transparent border-transparent focus:border-transparent focus:ring-0 px-4 py-4 text-sm text-gray-900 dark:text-white outline-none">
                                <button type="button" onclick="togglePassword(this)" class="flex items-center justify-center w-10 h-10 mr-1 rounded-md text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 hover:text-gray-600 dark:hover:text-gray-300 transition-all shrink-0" tabindex="-1">
                                    <i class="fas fa-eye text-sm"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3">
                        <button type="button" onclick="closeEditModal()" class="px-4 py-2 text-sm font-semibold text-gray-700 dark:text-white bg-gray-100 dark:bg-white/10 hover:bg-gray-200 dark:hover:bg-white/20 rounded-lg transition-all">Cancel</button>
                        <button type="submit" class="px-4 py-2 text-sm font-semibold text-white rounded-lg transition-all" style="background:hsl(176,75%,36%)" onmouseover="this.style.background='hsl(176,75%,30%)'" onmouseout="this.style.background='hsl(176,75%,36%)'">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Create Package Modal --}}
        <div id="createPackageModal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg mx-4 overflow-hidden" onclick="event.stopPropagation()">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Create Customer Package</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-300">Add a new package for public customers</p>
                    </div>
                    <button type="button" onclick="closeCreatePackageModal()" class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition-all">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form id="createPackageForm" onsubmit="submitCreatePackage(event)">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <div class="px-6 py-5 space-y-4">
                        <div>
                            <label for="cpNetwork" class="block text-sm font-semibold text-gray-900 dark:text-white mb-1">Network Type</label>
                            <select id="cpNetwork" name="network_type" required
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-4 py-3 text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none transition-all">
                                <option value="">Select network</option>
                                <option value="MTN">MTN</option>
                                <option value="VODAFONE">Telecel</option>
                                <option value="AIRTELTIGO">AirtelTigo</option>
                                <option value="ALL">All Networks</option>
                            </select>
                        </div>
                        <div>
                            <label for="cpName" class="block text-sm font-semibold text-gray-900 dark:text-white mb-1">Package Name</label>
                            <input type="text" id="cpName" name="package_name" required maxlength="100" placeholder="e.g. Daily Bundle 1GB"
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-4 py-3 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none transition-all">
                        </div>
                        <div>
                            <label for="cpSizeGb" class="block text-sm font-semibold text-gray-900 dark:text-white mb-1">Package Size (GB)</label>
                            <input type="number" id="cpSizeGb" step="0.1" min="0" required placeholder="e.g. 1"
                                oninput="document.getElementById('cpSizeMb').value = this.value ? Math.round(parseFloat(this.value) * 1024) + ' MB' : ''"
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-4 py-3 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none transition-all">
                            <input type="hidden" id="cpSizeMb" name="package_size" value="">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Enter size in GB. Converts to MB automatically.</p>
                        </div>
                        <div>
                            <label for="cpPrice" class="block text-sm font-semibold text-gray-900 dark:text-white mb-1">Price (GHS)</label>
                            <input type="number" id="cpPrice" name="price" step="0.01" min="0" required placeholder="e.g. 10.00"
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-4 py-3 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none transition-all">
                        </div>
                        <div>
                            <label class="inline-flex items-center gap-3 cursor-pointer">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" id="cpActive" name="is_active" value="1" checked
                                    class="w-5 h-5 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                <span class="text-sm font-semibold text-gray-900 dark:text-white">Active</span>
                            </label>
                        </div>
                    </div>
                    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3">
                        <button type="button" onclick="closeCreatePackageModal()" class="px-4 py-2 text-sm font-semibold text-gray-700 dark:text-white bg-gray-100 dark:bg-white/10 hover:bg-gray-200 dark:hover:bg-white/20 rounded-lg transition-all">Cancel</button>
                        <button type="submit" id="cpSubmitBtn" class="px-5 py-2 text-sm font-semibold text-white rounded-lg transition-all inline-flex items-center gap-2" style="background:hsl(176,75%,36%)" onmouseover="this.style.background='hsl(176,75%,30%)'" onmouseout="this.style.background='hsl(176,75%,36%)'">
                            <i class="fas fa-save text-xs"></i>
                            Create Package
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            window.togglePassword = function(btn) {
                var input = btn.parentElement.querySelector('input');
                var icon = btn.querySelector('i');
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.className = 'fas fa-eye-slash';
                } else {
                    input.type = 'password';
                    icon.className = 'fas fa-eye';
                }
            };

            window.openEditModal = function(btn) {
                var id = btn.getAttribute('data-id');
                document.getElementById('modalUsername').textContent = btn.getAttribute('data-username');
                document.getElementById('modalEmail').textContent = btn.getAttribute('data-email');
                document.getElementById('modalRole').textContent = btn.getAttribute('data-role');
                document.getElementById('modalBalance').textContent = 'GHS ' + btn.getAttribute('data-balance');
                document.getElementById('modalStatus').textContent = btn.getAttribute('data-status');
                document.getElementById('editModalSubtitle').textContent = 'ID: ' + id + ' \u2014 ' + btn.getAttribute('data-role');
                document.getElementById('editUserForm').action = '{{ url('/admin/user-update') }}/' + id;
                document.getElementById('modalPassword').value = '';
                document.getElementById('modalPasswordConfirmation').value = '';
                document.getElementById('editUserModal').classList.remove('hidden');
            };

            window.closeEditModal = function() {
                document.getElementById('editUserModal').classList.add('hidden');
            };

            window.openCreditModal = function(btn) {
                var id = btn.getAttribute('data-id');
                document.getElementById('creditModalUsername').textContent = btn.getAttribute('data-username');
                document.getElementById('creditModalBalance').textContent = 'GHS ' + btn.getAttribute('data-balance');
                document.getElementById('creditModalSubtitle').textContent = 'ID: ' + id;
                document.getElementById('creditAmount').value = '';
                document.getElementById('creditModal').dataset.userId = id;
                document.getElementById('creditModal').classList.remove('hidden');
            };

            window.closeCreditModal = function() {
                document.getElementById('creditModal').classList.add('hidden');
            };

            window.submitCredit = function() {
                var id = document.getElementById('creditModal').dataset.userId;
                var amount = document.getElementById('creditAmount').value;
                if (amount === '' || isNaN(amount)) {
                    alert('Please enter a valid amount.');
                    return;
                }
                submitPostAction('{{ url('/admin/credit-debit') }}/' + id, { amount: amount });
            };

            window.openCreatePackageModal = function() {
                document.getElementById('createPackageForm').reset();
                document.getElementById('cpSizeMb').value = '';
                document.getElementById('createPackageModal').classList.remove('hidden');
            };

            window.closeCreatePackageModal = function() {
                document.getElementById('createPackageModal').classList.add('hidden');
            };

            window.submitCreatePackage = function(event) {
                event.preventDefault();
                var form = document.getElementById('createPackageForm');
                var btn = document.getElementById('cpSubmitBtn');
                var gb = document.getElementById('cpSizeGb').value;
                if (!gb || parseFloat(gb) <= 0) {
                    alert('Please enter a valid package size.');
                    return;
                }
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';
                var data = new FormData(form);
                fetch('{{ url('/admin/customer-packages') }}', {
                    method: 'POST',
                    body: data,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(function(r) { return r.json(); })
                .then(function(d) {
                    if (d.success) {
                        window.closeCreatePackageModal();
                        location.reload();
                    } else {
                        alert('Error: ' + (d.message || 'Unknown error'));
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fas fa-save text-xs"></i> Create Package';
                    }
                })
                .catch(function() {
                    alert('Network error. Please try again.');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-save text-xs"></i> Create Package';
                });
            };

            window.confirmAndSubmit = function(message, url) {
                try {
                    if (confirm(message)) {
                        window.submitPostAction(url, {});
                    }
                } catch (e) {
                    console.error(e);
                    alert("Action failed: " + e.message);
                }
            };

            window.submitPostAction = function(url, data) {
                data = data || {};
                var form = document.createElement('form');
                form.method = 'POST';
                form.action = url;
                form.style.display = 'none';

                var csrf = document.createElement('input');
                csrf.type = 'hidden';
                csrf.name = '_token';
                csrf.value = '{{ csrf_token() }}';
                form.appendChild(csrf);

                for (var key in data) {
                    if (data.hasOwnProperty(key)) {
                        var input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = key;
                        input.value = data[key];
                        form.appendChild(input);
                    }
                }

                document.body.appendChild(form);
                form.submit();
            }

            document.getElementById('editUserForm').addEventListener('submit', function(e) {
                e.preventDefault();
                window.submitPostAction(this.action, {
                    password: document.getElementById('modalPassword').value,
                    password_confirmation: document.getElementById('modalPasswordConfirmation').value
                });
            });

            window.filterAgents = function(query) {
                var q = query.toLowerCase().trim();
                var table = document.getElementById('agentTable');
                var rows = table.querySelectorAll('tbody tr');
                for (var i = 0; i < rows.length; i++) {
                    var row = rows[i];
                    if (row.classList.contains('agent-row')) {
                        var text = row.textContent.toLowerCase();
                        var match = text.indexOf(q) !== -1;
                        row.style.display = match ? '' : 'none';
                    }
                }
            };

            document.addEventListener('click', function(e) {
                if (e.target === document.getElementById('editUserModal')) {
                    closeEditModal();
                }
                if (e.target === document.getElementById('creditModal')) {
                    closeCreditModal();
                }
                if (e.target === document.getElementById('createPackageModal')) {
                    closeCreatePackageModal();
                }
            });
        </script>
    </div>
</x-filament-panels::page>
