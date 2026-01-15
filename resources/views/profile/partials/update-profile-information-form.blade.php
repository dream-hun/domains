@php use Illuminate\Contracts\Auth\MustVerifyEmail; @endphp
<div class="card mt-4" style=" margin-left: 1.2rem; margin-right:1.2rem;">
    <div class="card-header" style="border: none !important;">

        <h4 class="h4">
            {{ __('Profile Information') }}
        </h4>
    </div>
    <div class="card-body">
        <!-- Session Status -->
        @if (session('profile_status'))
            <div class="status-message">
                {{ session('profile_status') }}
            </div>
        @endif

        <form id="send-verification" method="post" action="{{ route('verification.send') }}">
            @csrf
        </form>

        <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
            @csrf
            @method('patch')

            <div class="form-group">
                <label for="first_name" class="form-label">First Name</label>
                <input id="first_name" type="text" name="first_name"
                       class="form-control @error('first_name') is-invalid @enderror"
                       value="{{ old('first_name', $user->first_name) }}" required autofocus>
                @error('first_name')
                <div class="error-text">{{ $message }}</div>
                @enderror
            </div>
            <div class="form-group">
                <label for="last_name" class="form-label">Last Name</label>
                <input id="last_name" type="text" name="last_name"
                       class="form-control @error('last_name') is-invalid @enderror"
                       value="{{ old('last_name', $user->last_name) }}" required autofocus>
                @error('last_name')
                <div class="error-text">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="email" class="form-label">Email</label>
                <input id="email" type="email" name="email"
                       class="form-control @error('email') is-invalid @enderror"
                       value="{{ old('email', $user->email) }}" required>
                @error('email')
                <div class="error-text">{{ $message }}</div>
                @enderror
            </div>

            @if ($user instanceof MustVerifyEmail && !$user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800">
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification"
                                class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
            <div class="flex items-center gap-4">
                <x-primary-button>{{ __('Update Profile') }}</x-primary-button>
                @if (session('status') === 'profile-updated')
                    <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)"
                       class="text-sm text-gray-600">{{ __('Saved.') }}</p>
                @endif
            </div>
        </form>
    </div>
</div>
