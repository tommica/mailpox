@extends('mailpox::layouts.app')
@section('content')
<div class="layout">
    <aside class="sidebar">
        <div class="toolbar">
            <form method="POST" action="{{ route('mailpox.destroy-all') }}" data-confirm="Delete every captured email? This cannot be undone.">@csrf @method('DELETE')<button class="button button-danger" type="submit">Delete all</button></form>
            <span id="mailpox-message-count" style="margin-left:auto;color:var(--muted);align-self:center">{{ $messages->total() }} messages</span>
        </div>
        <div id="mailpox-message-list">
            @include('mailpox::messages._rows')
        </div>
    </aside>
    <main class="content">
        @if($selected)
            <header class="detail-header">
                <div class="detail-actions"><form method="POST" action="{{ route('mailpox.destroy', $selected->id) }}" data-confirm="Delete this email?">@csrf @method('DELETE')<button class="button button-danger">Delete</button></form></div>
                <h2>{{ $selected->subject ?: '(no subject)' }}</h2>
                <div class="detail-grid"><strong>From</strong><span>{{ collect($selected->from)->map(fn($a) => ($a['name'] ? $a['name'].' ' : '').'<'.$a['address'].'>')->implode(', ') }}</span><strong>To</strong><span>{{ collect($selected->to)->pluck('address')->implode(', ') }}</span><strong>Date</strong><span>{{ $selected->createdAt->format('Y-m-d H:i:s') }}</span></div>
                @if($selected->attachments)<div class="attachments">@foreach($selected->attachments as $attachment)<a class="attachment" href="{{ route('mailpox.attachments.show', [$selected->id, $attachment['id']]) }}">⬇ {{ $attachment['filename'] }} ({{ number_format($attachment['size']/1024, 1) }} KB)</a>@endforeach</div>@endif
            </header>
            <div class="tabs"><button class="tab active" data-tab="html">HTML</button><button class="tab" data-tab="text">Text</button><button class="tab" data-tab="headers">Headers</button><button class="tab" data-tab="raw">Raw</button></div>
            <section id="html" class="panel active"><iframe class="preview" sandbox src="{{ route('mailpox.html', $selected->id) }}"></iframe></section>
            <section id="text" class="panel"><div class="source">{{ $selected->text }}</div></section>
            <section id="headers" class="panel"><div class="source">@foreach($selected->headers as $name=>$value){{ $name }}: {{ $value }}
@endforeach</div></section>
            <section id="raw" class="panel"><div class="source">{{ $selected->raw }}</div></section>
        @else<div class="empty"><div><h2>Mailpox</h2><p>Select an email to inspect it.</p></div></div>@endif
    </main>
</div>
@endsection
