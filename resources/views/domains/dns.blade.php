@extends('layouts.app')

@section('title', $domain->domain.' — DNS')

@section('content')
    <h1>{{ $domain->domain }} — DNS</h1>
    @include('domains.partials.nav')

    <div class="card">
        <form method="POST" action="{{ route('domains.dns.update', $domain) }}">
            @csrf
            <table id="dns-records">
                <thead>
                <tr>
                    <th>Hostname</th>
                    <th>Type</th>
                    <th>Address</th>
                    <th>Priority</th>
                    <th>Record ID</th>
                </tr>
                </thead>
                <tbody>
                @php($rows = old('records', $records ?: [['hostname' => '@', 'type' => 'A', 'address' => '', 'priority' => 0, 'recid' => '']]))
                @foreach ($rows as $index => $record)
                    <tr>
                        <td><input type="text" name="records[{{ $index }}][hostname]" value="{{ $record['hostname'] ?? '' }}" required></td>
                        <td><input type="text" name="records[{{ $index }}][type]" value="{{ $record['type'] ?? 'A' }}" required></td>
                        <td><input type="text" name="records[{{ $index }}][address]" value="{{ $record['address'] ?? '' }}" required></td>
                        <td><input type="number" name="records[{{ $index }}][priority]" value="{{ $record['priority'] ?? 0 }}"></td>
                        <td><input type="text" name="records[{{ $index }}][recid]" value="{{ $record['recid'] ?? '' }}"></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <div class="actions" style="margin-top:1rem">
                <button type="submit">Save DNS records</button>
            </div>
        </form>
    </div>
@endsection
