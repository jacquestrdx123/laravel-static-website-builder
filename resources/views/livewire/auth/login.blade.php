<div class="auth-panel reveal">
    <div class="card">
        <p class="eyebrow">Welcome back</p>
        <h1 style="margin-bottom:.35rem">Log in to SiteForge</h1>
        <p class="muted" style="margin:0 0 1.25rem">Access your websites, credits, and marketing tools.</p>

        <form wire:submit="login">
            <label for="email">Email</label>
            <input id="email" type="email" wire:model="email" required autofocus autocomplete="email">
            @error('email')<div class="error">{{ $message }}</div>@enderror

            <label for="password">Password</label>
            <input id="password" type="password" wire:model="password" required autocomplete="current-password">
            @error('password')<div class="error">{{ $message }}</div>@enderror

            <label style="font-weight: normal; display:flex; align-items:center; gap:.45rem;">
                <input type="checkbox" wire:model="remember" value="1"> Remember me
            </label>

            <div class="actions" style="margin-top: 1.5rem;">
                <button type="submit" wire:loading.attr="disabled">Log in</button>
                <a class="btn secondary" href="{{ route('register') }}">Create an account</a>
            </div>
        </form>
    </div>
</div>
