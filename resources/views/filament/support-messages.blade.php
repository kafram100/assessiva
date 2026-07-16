<div class="space-y-3 p-3 bg-gray-50 rounded-xl max-h-80 overflow-y-auto mb-3" style="scroll-behavior:smooth">
    @php $record = $getRecord(); @endphp
    @forelse($record->messages()->orderBy('created_at')->get() as $msg)
        <div class="flex {{ $msg->sender_type === 'user' ? 'justify-end' : 'justify-start' }}">
            <div class="max-w-[80%] rounded-2xl px-4 py-2.5 {{ $msg->sender_type === 'user' ? 'bg-primary-500 text-white rounded-br-md' : 'bg-white border border-gray-200 text-gray-800 rounded-bl-md shadow-sm' }}">
                @if($msg->sender_type === 'admin')
                    <p class="text-xs font-semibold text-primary-600 mb-0.5">Support</p>
                @elseif($msg->sender_type === 'bot')
                    <p class="text-xs font-semibold text-primary-600 mb-0.5">🤖 Bot</p>
                @endif
                <p class="text-sm leading-relaxed whitespace-pre-wrap">{{ $msg->message }}</p>
                <p class="text-xs mt-1 {{ $msg->sender_type === 'user' ? 'text-white/60' : 'text-gray-400' }}">
                    {{ $msg->created_at->diffForHumans() }}
                </p>
            </div>
        </div>
    @empty
        <p class="text-gray-400 text-sm text-center py-4">No messages yet</p>
    @endforelse
</div>

<style>
    .fi-fo-view-field .overflow-y-auto::-webkit-scrollbar {
        width: 4px;
    }
    .fi-fo-view-field .overflow-y-auto::-webkit-scrollbar-thumb {
        background: #d1d5db;
        border-radius: 2px;
    }
</style>
