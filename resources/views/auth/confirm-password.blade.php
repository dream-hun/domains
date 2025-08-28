<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        {{ __('This is a secure area of the application. Please confirm your password before continuing.') }}
    </div>

    <form method="POST" action="{{ route('password.confirm') }}" id="confirmForm">
        @csrf
        <input type="hidden" name="recaptcha_token" id="recaptcha_token">
        <!-- Password -->
        <div>
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex justify-end mt-4">
            <x-primary-button>
                {{ __('Confirm') }}
            </x-primary-button>
        </div>
    </form>
    @push('scripts')
        <script>
            grecaptcha.ready(function () {
                document.getElementById('confirmForm').addEventListener("submit", function (event) {
                    event.preventDefault();
                    grecaptcha.execute('{{ config('services.recaptcha.site_key') }}', {action: 'confirm'})
                        .then(function (token) {
                            document.getElementById("recaptcha_token").value = token;
                            document.getElementById('confirmForm').submit();
                        });
                });
            });
        </script>
    @endpush
</x-guest-layout>
