<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        Bu alan guvenlik korumalidir. Devam etmek icin lutfen sifrenizi dogrulayin.
    </div>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <!-- Password -->
        <div>
            <x-input-label for="password" :value="'Sifre'" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex justify-end mt-4">
            <x-primary-button>Dogrula</x-primary-button>
        </div>
    </form>
</x-guest-layout>
