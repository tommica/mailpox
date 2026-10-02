@forelse($messages as $message)
    @php($from = json_decode($message->from ?? json_encode($message['from'] ?? []), true) ?: [])
    <a class="message-row {{ ($message->read ?? $message['read'] ?? false) ? '' : 'unread' }} {{ $selected?->id === ($message->id ?? $message['id']) ? 'active' : '' }}" data-message-id="{{ $message->id ?? $message['id'] }}" href="{{ route('mailpox.show', ['message' => $message->id ?? $message['id'], 'search' => $search]) }}">
        <div class="message-head"><span>{{ $from[0]['name'] ?: ($from[0]['address'] ?? 'Unknown sender') }}</span><small>{{ \Illuminate\Support\Carbon::parse($message->created_at ?? $message['created_at'])->diffForHumans() }}</small></div>
        <div class="message-subject">{{ $message->subject ?? $message['subject'] ?? '(no subject)' }}</div>
        <div class="message-meta">{{ $from[0]['address'] ?? '' }}</div>
    </a>
@empty<div class="empty" style="height:300px">No captured mail.</div>@endforelse
@if($messages->hasPages())<div class="pagination"><span>{!! $messages->previousPageUrl() ? '<a href="'.e($messages->previousPageUrl()).'">Previous</a>' : '' !!}</span><span>{!! $messages->nextPageUrl() ? '<a href="'.e($messages->nextPageUrl()).'">Next</a>' : '' !!}</span></div>@endif
