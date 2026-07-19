<div>
    <h1>Generate poster</h1>
    <p class="muted">Costs {{ $creditCost }} credits. HTML and PNG are saved to your website vault.</p>

    <div class="card">
        <form wire:submit="generate">
            <label>
                Brief
                <textarea wire:model="brief" rows="4" required maxlength="1000"></textarea>
            </label>
            @error('brief')<div class="error">{{ $message }}</div>@enderror
            <label>
                Format
                <select wire:model="format" required>
                    @foreach ($formats as $key => $formatOption)
                        <option value="{{ $key }}">{{ $formatOption['label'] }} ({{ $formatOption['width'] }}×{{ $formatOption['height'] }})</option>
                    @endforeach
                </select>
            </label>
            @error('format')<div class="error">{{ $message }}</div>@enderror
            <button type="submit" wire:loading.attr="disabled">Generate ({{ $creditCost }} credits)</button>
        </form>
    </div>

    <p><a href="{{ route('websites.posters.index', $website) }}">← Back</a></p>
</div>
