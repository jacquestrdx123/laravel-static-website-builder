@extends('layouts.app')

@section('title', $mode === 'transfer' ? 'Transfer domain' : 'Register domain')

@section('content')
    <h1>{{ $mode === 'transfer' ? 'Transfer domain' : 'Register domain' }}</h1>
    <p class="muted">You have <strong>{{ auth()->user()->ai_credits }}</strong> credit{{ auth()->user()->ai_credits === 1 ? '' : 's' }}.</p>
    @if ($creditCost)
        <p class="muted">This order costs <strong>{{ $creditCost }} credit{{ $creditCost === 1 ? '' : 's' }}</strong> for 1 year (add-ons and extra years may increase the total).</p>
    @endif

    <div class="card">
        <form method="POST" action="{{ $mode === 'transfer' ? route('domains.transfer.store') : route('domains.register.store') }}">
            @csrf

            <label for="domain">Domain</label>
            <input type="text" id="domain" name="domain" value="{{ old('domain', $domain) }}" required>
            @error('domain')<div class="error">{{ $message }}</div>@enderror

            @if ($mode === 'transfer')
                <label for="eppcode">EPP / auth code</label>
                <input type="text" id="eppcode" name="eppcode" value="{{ old('eppcode') }}" required>
                @error('eppcode')<div class="error">{{ $message }}</div>@enderror
            @endif

            <label for="regperiod">Registration period (years)</label>
            <select id="regperiod" name="regperiod">
                @for ($year = 1; $year <= 10; $year++)
                    <option value="{{ $year }}" @selected(old('regperiod', 1) == $year)>{{ $year }}</option>
                @endfor
            </select>

            <label for="website_id">Link to website <span class="hint">(optional)</span></label>
            <select id="website_id" name="website_id">
                <option value="">— None —</option>
                @foreach ($websites as $website)
                    <option value="{{ $website->id }}" @selected(old('website_id') == $website->id)>{{ $website->name }}</option>
                @endforeach
            </select>

            <h2>Registrant contact</h2>
            @include('domains.partials.contact-fields', ['contact' => $defaultContact, 'prefix' => 'contact'])

            <h2>Nameservers</h2>
            @foreach (['ns1', 'ns2', 'ns3', 'ns4', 'ns5'] as $ns)
                <label for="{{ $ns }}">{{ strtoupper($ns) }} @if(in_array($ns, ['ns1','ns2']))<span class="hint">(required)</span>@endif</label>
                <input type="text" id="{{ $ns }}" name="nameservers[{{ $ns }}]" value="{{ old('nameservers.'.$ns, $defaultNameservers[$ns] ?? '') }}" @if(in_array($ns, ['ns1','ns2'])) required @endif>
            @endforeach

            <h2>Add-ons</h2>
            <div class="choices">
                <label><input type="checkbox" name="addons[dnsmanagement]" value="1" @checked(old('addons.dnsmanagement'))> DNS management</label>
                <label><input type="checkbox" name="addons[emailforwarding]" value="1" @checked(old('addons.emailforwarding'))> Email forwarding</label>
                <label><input type="checkbox" name="addons[idprotection]" value="1" @checked(old('addons.idprotection'))> ID protection</label>
            </div>

            <div class="actions" style="margin-top:1.5rem">
                <button type="submit">{{ $mode === 'transfer' ? 'Start transfer' : 'Register domain' }}@if($creditCost) (from {{ $creditCost }} credits)@endif</button>
                <a class="btn secondary" href="{{ route('domains.search') }}">Back to search</a>
            </div>
        </form>
    </div>
@endsection
