@extends('layouts.app')

@section('title', $domain->domain.' — Nameservers')

@section('content')
    <h1>{{ $domain->domain }} — Nameservers</h1>
    @include('domains.partials.nav')

    <div class="card">
        <form method="POST" action="{{ route('domains.nameservers.update', $domain) }}">
            @csrf
            @foreach (['ns1', 'ns2', 'ns3', 'ns4', 'ns5'] as $ns)
                <label for="{{ $ns }}">{{ strtoupper($ns) }}</label>
                <input type="text" id="{{ $ns }}" name="{{ $ns }}" value="{{ old($ns, $nameservers[$ns] ?? '') }}" @if(in_array($ns, ['ns1','ns2'])) required @endif>
            @endforeach
            <div class="actions" style="margin-top:1rem">
                <button type="submit">Save nameservers</button>
            </div>
        </form>
    </div>

    <div class="card">
        <h2 style="margin-top:0">Child nameservers</h2>
        <h3>Register</h3>
        <form method="POST" action="{{ route('domains.nameservers.register-child', $domain) }}" class="grid-2">
            @csrf
            <div>
                <label>Nameserver</label>
                <input type="text" name="nameserver" required>
            </div>
            <div>
                <label>IP address</label>
                <input type="text" name="ipaddress" required>
            </div>
            <div class="actions"><button type="submit">Register</button></div>
        </form>

        <h3>Modify</h3>
        <form method="POST" action="{{ route('domains.nameservers.modify-child', $domain) }}">
            @csrf
            <label>Nameserver</label>
            <input type="text" name="nameserver" required>
            <div class="grid-2">
                <div>
                    <label>Current IP</label>
                    <input type="text" name="currentipaddress" required>
                </div>
                <div>
                    <label>New IP</label>
                    <input type="text" name="newipaddress" required>
                </div>
            </div>
            <div class="actions"><button type="submit">Modify</button></div>
        </form>

        <h3>Delete</h3>
        <form method="POST" action="{{ route('domains.nameservers.delete-child', $domain) }}">
            @csrf
            <label>Nameserver</label>
            <input type="text" name="nameserver" required>
            <div class="actions"><button type="submit" class="btn danger">Delete</button></div>
        </form>
    </div>
@endsection
