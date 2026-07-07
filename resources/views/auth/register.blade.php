@extends('layouts.app')

@section('title', 'Sign up')

@section('content')
    <div class="card" style="max-width: 460px; margin: 3rem auto;">
        <h1>Create your account</h1>
        <p class="muted">Sign up and get 1 free AI credit to generate your first website.</p>

        <form method="POST" action="{{ route('register') }}">
            @csrf

            <label for="name">Name</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus>
            @error('name')<div class="error">{{ $message }}</div>@enderror

            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required>
            @error('email')<div class="error">{{ $message }}</div>@enderror

            <label for="password">Password</label>
            <input id="password" type="password" name="password" required>
            @error('password')<div class="error">{{ $message }}</div>@enderror

            <label for="password_confirmation">Confirm password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required>

            <div style="margin-top: 1.5rem;">
                <button type="submit">Sign up</button>
                <a class="btn secondary" href="{{ route('login') }}">I have an account</a>
            </div>
        </form>
    </div>
@endsection
