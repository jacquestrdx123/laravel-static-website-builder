<div>
    <h1>{{ $domain->domain }} — Settings</h1>
    @include('domains.partials.nav')

    <div class="card">
        <h2 style="margin-top:0">Registrar lock</h2>
        <form method="POST" action="{{ route('domains.settings.lock', $domain) }}" class="actions">
            @csrf
            <select name="lockstatus">
                <option value="locked" @selected(($lock['lockstatus'] ?? ($domain->registrar_locked ? 'locked' : 'unlocked')) === 'locked')>Locked</option>
                <option value="unlocked" @selected(($lock['lockstatus'] ?? ($domain->registrar_locked ? 'locked' : 'unlocked')) === 'unlocked')>Unlocked</option>
            </select>
            <button type="submit">Update lock</button>
        </form>
    </div>

    <div class="card">
        <h2 style="margin-top:0">ID protection</h2>
        <form method="POST" action="{{ route('domains.settings.id-protection', $domain) }}" class="actions">
            @csrf
            <label><input type="checkbox" name="status" value="1" @checked($domain->id_protection)> Enable ID protection</label>
            <button type="submit">Save</button>
        </form>
    </div>

    <div class="card">
        <h2 style="margin-top:0">Email forwarding</h2>
        @php($prefixes = old('prefix', $emailForwarding['prefix'] ?? ['info']))
        @php($forwardTo = old('forwardto', $emailForwarding['forwardto'] ?? ['']))
        <form method="POST" action="{{ route('domains.settings.email', $domain) }}">
            @csrf
            @foreach ($prefixes as $index => $prefix)
                <div class="grid-2">
                    <div>
                        <label>Prefix</label>
                        <input type="text" name="prefix[]" value="{{ $prefix }}">
                    </div>
                    <div>
                        <label>Forward to</label>
                        <input type="email" name="forwardto[]" value="{{ $forwardTo[$index] ?? '' }}">
                    </div>
                </div>
            @endforeach
            <div class="actions"><button type="submit">Save forwarding</button></div>
        </form>
    </div>

    <div class="card">
        <h2 style="margin-top:0">EPP code</h2>
        <form method="POST" action="{{ route('domains.settings.epp', $domain) }}">
            @csrf
            <button type="submit" class="btn secondary">Show EPP code</button>
        </form>
    </div>

    <div class="card">
        <h2 style="margin-top:0">Release domain</h2>
        <form method="POST" action="{{ route('domains.settings.release', $domain) }}">
            @csrf
            <label>Transfer tag</label>
            <input type="text" name="transfertag" required>
            <div class="actions"><button type="submit" class="btn danger">Release domain</button></div>
        </form>
    </div>

    <div class="card">
        <h2 style="margin-top:0">Request deletion</h2>
        <form method="POST" action="{{ route('domains.settings.delete', $domain) }}">
            @csrf
            <button type="submit" class="btn danger">Request deletion</button>
        </form>
    </div>
</div>
