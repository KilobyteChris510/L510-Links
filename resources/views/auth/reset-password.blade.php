<x-layouts.guest title="Reset password">
    <h1 class="text-xl font-semibold">Reset password</h1>

    <form method="POST" action="{{ route('password.store') }}" class="mt-6 space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <x-input name="email" label="Email" type="email" :value="old('email', $email)" required autocomplete="username" />
        <x-input name="password" label="New password" type="password" required autofocus autocomplete="new-password" />
        <x-input name="password_confirmation" label="Confirm password" type="password" required autocomplete="new-password" />

        <x-button>Reset password</x-button>
    </form>
</x-layouts.guest>
