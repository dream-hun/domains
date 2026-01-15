<style>
    .password-container {
        display: flex;
        align-items: center;
        justify-content: left;
        padding: 20px;
    }

    .password-card {
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        width: 100%;
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

    .invalid-feedback {
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

    .password-field {
        position: relative;
    }

    .password-toggle {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: #666;
        cursor: pointer;
        font-size: 16px;
        padding: 0;
    }

    .password-toggle:hover {
        color: #333;
    }

    .text-success {
        color: #28a745;
    }

    .gap-3 {
        gap: 12px;
    }

    .d-flex {
        display: flex;
        align-items: center;
    }

    .small {
        font-size: 14px;
    }
</style>


<div class="password-container">
    <div class="password-card">
        <div class="form-section">
            <h2 class="form-title">{{ __('Update Password') }}</h2>

            <!-- Session Status -->
            @if (session('status'))
                <div class="status-message">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.update') }}">
                @csrf
                @method('put')

                <!-- Current Password -->
                <div class="form-group" x-data="{ showPassword: false }">
                    <label for="update_password_current_password" class="form-label">
                        {{ __('Current Password') }}
                    </label>
                    <div class="password-field position-relative">
                        <input :type="showPassword ? 'text' : 'password'"
                               id="update_password_current_password"
                               name="current_password"
                               class="form-control @error('current_password', 'updatePassword') is-invalid @enderror"
                               autocomplete="current-password">
                        @error('current_password', 'updatePassword')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror

                        <button type="button"
                                class="password-toggle btn btn-sm position-absolute end-0 top-0 mt-1 me-2"
                                @click="showPassword = !showPassword"
                                :title="showPassword ? 'Hide password' : 'Show password'">
                            <span x-show="!showPassword" x-cloak><i class="bi bi-eye"></i></span>
                            <span x-show="showPassword" x-cloak><i class="bi bi-eye-slash"></i></span>
                        </button>
                    </div>
                </div>

                <!-- New Password -->
                <div class="form-group" x-data="{ showNewPassword: false }">
                    <label for="update_password_password" class="form-label">{{ __('New Password') }}</label>
                    <div class="password-field position-relative">
                        <input :type="showNewPassword ? 'text' : 'password'"
                               id="update_password_password"
                               name="password"
                               class="form-control @error('password', 'updatePassword') is-invalid @enderror"
                               autocomplete="new-password">
                        @error('password', 'updatePassword')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror

                        <button type="button"
                                class="password-toggle btn btn-sm position-absolute end-0 top-0 mt-1 me-2"
                                @click="showNewPassword = !showNewPassword"
                                :title="showNewPassword ? 'Hide password' : 'Show password'">
                            <span x-show="!showNewPassword" x-cloak><i class="bi bi-eye"></i></span>
                            <span x-show="showNewPassword" x-cloak><i class="bi bi-eye-slash"></i></span>
                        </button>
                    </div>
                </div>

                <!-- Confirm Password -->
                <div class="form-group" x-data="{ showConfirmPassword: false }">
                    <label for="update_password_password_confirmation"
                           class="form-label">{{ __('Confirm Password') }}</label>
                    <div class="password-field position-relative">
                        <input :type="showConfirmPassword ? 'text' : 'password'"
                               id="update_password_password_confirmation"
                               name="password_confirmation"
                               class="form-control @error('password_confirmation', 'updatePassword') is-invalid @enderror"
                               autocomplete="new-password">
                        @error('password_confirmation', 'updatePassword')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror

                        <button type="button"
                                class="password-toggle btn btn-sm position-absolute end-0 top-0 mt-1 me-2"
                                @click="showConfirmPassword = !showConfirmPassword"
                                :title="showConfirmPassword ? 'Hide password' : 'Show password'">
                            <span x-show="!showConfirmPassword" x-cloak><i class="bi bi-eye"></i></span>
                            <span x-show="showConfirmPassword" x-cloak><i class="bi bi-eye-slash"></i></span>
                        </button>
                    </div>
                </div>
                <!-- Submit -->
                <div class="d-flex gap-3">
                    <x-primary-button>{{ __('Update Password') }}</x-primary-button>

                    @if (session('status') === 'password-updated')
                        <span class="text-success small">{{ __('Saved.') }}</span>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>
