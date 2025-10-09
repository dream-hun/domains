@php use Illuminate\Contracts\Auth\MustVerifyEmail; @endphp
@section('styles')
    <style>
        body {
            background-color: #f8f9fa;
        }

        .profile-container {
            display: flex;
            align-items: center;
            justify-content: left;
            padding: 20px;
        }

        .profile-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            width: 100%;
        }


        .logo-section img {
            max-width: 80px;
            height: auto;
        }

        .form-section {
            padding: 20px 40px 40px;
        }

        .form-title {
            font-size: 24px;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
            text-align: left;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #555;
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }

        .form-control:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
        }

        select.form-control {
            background-color: white;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 12px center;
            background-repeat: no-repeat;
            background-size: 16px 12px;
            padding-right: 40px;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
        }


        .error-text {
            color: #dc3545;
            font-size: 14px;
            margin-top: 5px;
        }

        .status-message {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 14px;
        }
    </style>
@endsection

<div class="profile-container">
    <div class="profile-card">


        <div class="form-section">

            <h2 class="form-title">
                {{ __('Profile Information') }}
            </h2>

            <p style="text-align: left; margin-bottom: 20px;">
                {{ __("Update your account's profile information and email address.") }}
            </p>

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
                <div class="form-group">
                    <label for="preferred_currency" class="form-label">Choose Currency</label>
                    <select id="preferred_currency" name="preferred_currency"
                        class="form-control @error('preferred_currency') is-invalid @enderror">
                        <option value="">Select a currency...</option>
                        @foreach ($currencies as $currency)
                            <option value="{{ $currency->code }}"
                                {{ old('preferred_currency', $user->preferred_currency) == $currency->code ? 'selected' : '' }}>
                                {{ $currency->name }} ({{ $currency->code }})
                            </option>
                        @endforeach
                    </select>
                    @error('preferred_currency')
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

</div>
