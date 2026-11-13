<x-layouts.guest title="Log in">
    <h1 class="text-xl font-semibold">Log in</h1>
    <p class="mt-1 text-sm text-slate-600">Sign in to manage your links.</p>

    <div class="mt-6">
        <x-status-message />

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf

            <x-input name="email" label="Email" type="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input name="password" label="Password" type="password" required autocomplete="current-password" />

            <div class="flex items-center justify-between">
                <label for="remember" class="inline-flex items-center gap-2 text-sm text-slate-600">
                    <input id="remember" name="remember" type="checkbox" class="rounded border-slate-300">
                    Remember me
                </label>

                <a href="{{ route('password.request') }}" class="text-sm text-slate-600 underline hover:text-slate-900">
                    Forgot password?
                </a>
            </div>

            <x-button>Log in</x-button>
        </form>
    </div>
</x-layouts.guest>
