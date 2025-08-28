<x-guest-layout>
    @section('page-title')
        Reset Password
    @endsection
        @push('styles')
            <style>
                .reset-password-page body {
                    background-color: #f8f9fa !important;
                }

                .reset-password-page .reset-container {
                    display: flex !important;
                    align-items: center !important;
                    justify-content: center !important;
                    padding: 20px !important;
                    margin-top: 180px;
                }

                .reset-password-page .reset-card {
                    background: white !important;
                    border-radius: 8px !important;
                    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1) !important;
                    max-width: 450px !important;
                    width: 100% !important;
                }

                .reset-password-page .logo-section {
                    text-align: center !important;
                    padding: 40px 40px 20px !important;
                }

                .reset-password-page .logo-section img {
                    max-width: 80px !important;
                    height: auto !important;
                }

                .reset-password-page .form-section {
                    padding: 20px 40px 40px !important;
                }

                .reset-password-page .form-title {
                    font-size: 24px !important;
                    font-weight: 600 !important;
                    color: #333 !important;
                    margin-bottom: 30px !important;
                    text-align: center !important;
                }

                .reset-password-page .form-group {
                    margin-bottom: 20px !important;
                }

                .reset-password-page .form-label {
                    display: block !important;
                    margin-bottom: 5px !important;
                    font-weight: 500 !important;
                    color: #555 !important;
                }

                .reset-password-page .form-control {
                    width: 100% !important;
                    padding: 12px !important;
                    border: 1px solid #ddd !important;
                    border-radius: 4px !important;
                    font-size: 16px !important;
                }

                .reset-password-page .form-control:focus {
                    outline: none !important;
                    border-color: #007bff !important;
                    box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25) !important;
                }

                .reset-password-page .btn-primary {
                    width: 100% !important;
                    padding: 12px !important;
                    background-color: #007bff !important;
                    color: white !important;
                    border: none !important;
                    border-radius: 4px !important;
                    font-size: 16px !important;
                    font-weight: 500 !important;
                    cursor: pointer !important;
                }

                .reset-password-page .btn-primary:hover {
                    background-color: #0056b3 !important;
                }

                .reset-password-page .footer {
                    background-color: #f8f9fa !important;
                    padding: 15px !important;
                    text-align: center !important;
                    font-size: 12px !important;
                    color: #666 !important;
                    border-top: 1px solid #eee !important;
                }

                .reset-password-page .error-text {
                    color: #dc3545 !important;
                    font-size: 14px !important;
                    margin-top: 5px !important;
                }

                .reset-password-page .status-message {
                    background-color: #d4edda !important;
                    color: #155724 !important;
                    padding: 10px !important;
                    border-radius: 4px !important;
                    margin-bottom: 20px !important;
                    font-size: 14px !important;
                }

                .reset-password-page .password-field {
                    position: relative !important;
                }

                .reset-password-page .password-toggle {
                    position: absolute !important;
                    right: 12px !important;
                    top: 50% !important;
                    transform: translateY(-50%) !important;
                    background: none !important;
                    border: none !important;
                    color: #666 !important;
                    cursor: pointer !important;
                    font-size: 16px !important;
                    padding: 0 !important;
                }

                .reset-password-page .password-toggle:hover {
                    color: #333 !important;
                }
            </style>

        @endpush

    <div class="reset-container">
        <div class="reset-card">
            <!-- Form Section -->
            <div class="form-section">
                @if (session('status'))
                    <div class="status-message">
                        {{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('password.store') }}" id="resetForm">
                    @csrf

                    <!-- Token -->
                    <input type="hidden" name="token" value="{{ $request->route('token') }}">
                    <input type="hidden" name="recaptcha_token" id="recaptcha_token">

                    <h2 class="form-title">Reset Password</h2>

                    <!-- Email -->
                    <div class="form-group">
                        <label for="email" class="form-label">Email</label>
                        <input id="email"
                               type="email"
                               name="email"
                               class="form-control"
                               value="{{ old('email', $request->email) }}"
                               required
                               autofocus
                               autocomplete="username">
                        @error('email')
                        <div class="error-text">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Password -->
                    <div class="form-group" x-data="{ showPassword: false }">
                        <label for="password" class="form-label">Password</label>
                        <div class="password-field">
                            <input id="password"
                                   :type="showPassword ? 'text' : 'password'"
                                   name="password"
                                   class="form-control"
                                   required
                                   autocomplete="new-password">
                            <button type="button"
                                    class="password-toggle"
                                    @click="showPassword = !showPassword"
                                    :title="showPassword ? 'Hide password' : 'Show password'" style="margin-right: -160px">
                                <span x-show="!showPassword"><i class="bi bi-eye"></i></span>
                                <span x-show="showPassword"><i class="bi bi-eye-slash"></i></span>
                            </button>
                        </div>
                        @error('password')
                        <div class="error-text">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Confirm Password -->
                    <div class="form-group" x-data="{ showConfirmPassword: false }">
                        <label for="password_confirmation" class="form-label">Confirm Password</label>
                        <div class="password-field">
                            <input id="password_confirmation"
                                   :type="showConfirmPassword ? 'text' : 'password'"
                                   name="password_confirmation"
                                   class="form-control"
                                   required
                                   autocomplete="new-password">
                            <button type="button"
                                    class="password-toggle"
                                    @click="showConfirmPassword = !showConfirmPassword"
                                    :title="showConfirmPassword ? 'Hide password' : 'Show password'" style="margin-right: -160px">
                                <span x-show="!showConfirmPassword"><i class="bi bi-eye"></i></span>
                                <span x-show="showConfirmPassword"><i class="bi bi-eye-slash"></i></span>
                            </button>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="form-group">
                        <button type="submit" class="btn-primary">
                            Reset Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
        @push('scripts')
            <script>
                grecaptcha.ready(function () {
                    document.getElementById('resetForm').addEventListener("submit", function (event) {
                        event.preventDefault();
                        grecaptcha.execute('{{ config('services.recaptcha.site_key') }}', {action: 'reset'})
                            .then(function (token) {
                                document.getElementById("recaptcha_token").value = token;
                                document.getElementById('resetForm').submit();
                            });
                    });
                });
            </script>
        @endpush
</x-guest-layout>
