@extends('layouts.app')

@section('title', 'Log in')

@section('content')
    <div class="card" style="max-width: 460px; margin: 3rem auto;">
        <h1>Welcome back</h1>

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>
            @error('email')<div class="error">{{ $message }}</div>@enderror

            <label for="password">Password</label>
            <input id="password" type="password" name="password" required>

            <label style="font-weight: normal;">
                <input type="checkbox" name="remember" value="1"> Remember me
            </label>

            <div style="margin-top: 1.5rem;">
                <button type="submit">Log in</button>
                <a class="btn secondary" href="{{ route('register') }}">Create an account</a>
            </div>
        </form>
    </div>
@endsection
