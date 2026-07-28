<div>
    <h1>Newsletter subscribers</h1>
    <p class="muted">People who signed up from your published site or were added manually.</p>

    <div class="card">
        <h2 style="margin-top:0">Add subscriber</h2>
        <form wire:submit="add" class="actions">
            <input type="email" wire:model="email" placeholder="Email" required>
            <input type="text" wire:model="name" placeholder="Name (optional)">
            <button type="submit" wire:loading.attr="disabled">Add</button>
        </form>
        @error('email')<div class="error">{{ $message }}</div>@enderror
    </div>

    <div class="card">
        @if ($subscribers->isEmpty())
            <p class="muted">No subscribers yet.</p>
        @else
            <table>
                <thead><tr><th>Email</th><th>Name</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @foreach ($subscribers as $subscriber)
                    <tr wire:key="sub-{{ $subscriber->id }}">
                        <td>{{ $subscriber->email }}</td>
                        <td>{{ $subscriber->name ?? '—' }}</td>
                        <td>{{ $subscriber->status }}</td>
                        <td>
                            <button type="button" class="btn danger" wire:click="remove({{ $subscriber->id }})"
                                    wire:confirm="Remove this subscriber?">Remove</button>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <p><a href="{{ route('websites.show', $website) }}">← Back to website</a></p>
</div>
