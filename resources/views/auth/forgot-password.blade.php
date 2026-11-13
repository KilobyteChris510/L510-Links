<x-layouts.guest title="Forgot password">
    <h1 class="text-xl font-semibold">Forgot password</h1>
    <p class="mt-1 text-sm text-slate-600">Enter your email and we'll send you a reset link.</p>

    <div class="mt-6">
        <x-auth-status />

        <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
            @csrf

            <x-input name="email" label="Email" type="email" :value="old('email')" required autofocus autocomplete="username" />

            <x-button>Email reset link</x-button>
        </form>

        <p class="mt-4 text-center text-sm">
            <a href="{{ route('login') }}" class="text-slate-600 underline hover:text-slate-900">Back to log in</a>
        </p>
    </div>
</x-layouts.guest>
