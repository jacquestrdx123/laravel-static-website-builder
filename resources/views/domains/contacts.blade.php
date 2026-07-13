@extends('layouts.app')

@section('title', $domain->domain.' — Contacts')

@section('content')
    <h1>{{ $domain->domain }} — Contacts</h1>
    @include('domains.partials.nav')

    <div class="card">
        <form method="POST" action="{{ route('domains.contacts.update', $domain) }}">
            @csrf
            @include('domains.partials.contact-fields', ['contact' => $contact, 'prefix' => ''])
            <div class="actions" style="margin-top:1rem">
                <button type="submit">Save contact details</button>
            </div>
        </form>
    </div>
@endsection
