<div>
    <h1>Generate newsletter</h1>
    <p class="muted">Costs {{ $creditCost }} credits. Content is saved to your website vault.</p>

    <div class="card">
        <form wire:submit="generate">
            <label>
                Topic
                <input type="text" wire:model="topic" required maxlength="255">
            </label>
            @error('topic')<div class="error">{{ $message }}</div>@enderror
            <label>
                Angle (optional)
                <input type="text" wire:model="angle" maxlength="500" placeholder="e.g. summer sale, new product launch">
            </label>
            <button type="submit" wire:loading.attr="disabled">Generate ({{ $creditCost }} credits)</button>
        </form>
    </div>

    <p><a href="{{ route('websites.newsletters.index', $website) }}">← Back</a></p>
</div>
