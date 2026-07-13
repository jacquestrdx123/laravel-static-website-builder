@extends('layouts.app')

@section('title', 'Unsubscribed')

@section('content')
    <h1>Unsubscribed</h1>
    <p>You will no longer receive newsletters from <strong>{{ $website->name }}</strong>.</p>
@endsection
