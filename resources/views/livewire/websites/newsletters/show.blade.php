<div>
    <h1>{{ $newsletter['subject'] }}</h1>
    <p class="muted">Topic: {{ $newsletter['topic'] }} · Status: {{ $newsletter['status'] }}
        @if ($newsletter['sent_at'])
            · Sent to {{ $newsletter['recipient_count'] }} recipient(s)
        @endif
    </p>

    @if ($newsletter['status'] !== 'sent')
        <button type="button" class="btn" style="margin-bottom: 1rem;" wire:click="send"
                wire:confirm="Send this newsletter to all subscribers?">Send to all subscribers</button>
    @endif

    <div class="card">
        <iframe class="preview" style="min-height: 480px;" srcdoc="{{ e($html) }}" title="Newsletter preview"></iframe>
    </div>

    <p><a href="{{ route('websites.newsletters.index', $website) }}">← Back</a></p>
</div>
