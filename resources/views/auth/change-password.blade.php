<x-guest-layout>
    <form method="POST" action="{{ route('password.change.update') }}">
        @csrf

        <div class="mb-6 text-center">
            <h2 class="text-xl font-bold text-gray-800">Set New Password</h2>
            <p class="text-sm text-gray-500 mt-1">You must change your password before continuing.</p>
        </div>

        <div class="mb-4">
            <x-input-label for="password" value="New Password" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autofocus />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mb-6">
            <x-input-label for="password_confirmation" value="Confirm New Password" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required />
        </div>

        <x-primary-button class="w-full justify-center">
            Set Password
        </x-primary-button>
    </form>
</x-guest-layout>
