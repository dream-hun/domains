<x-guest-layout>
    @section('page-title')
        Forgot Password
    @endsection
        @push('styles')
            <style>
                .reset-password-page body {
                    background-color: #f8f9fa;
                }

                .reset-password-page .register-container {
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 20px;
                    margin-top: 180px;
                }

                .reset-password-page .register-card {
                    background: white;
                    border-radius: 8px;
                    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                    max-width: 450px;
                    width: 100%;
                }


                .reset-password-page .form-section {
                    padding: 20px 40px 40px;
                }

                .reset-password-page .form-title {
                    font-size: 24px;
                    font-weight: 600;
                    color: #333;
                    margin-bottom: 20px;
                    text-align: center;
                }

                .reset-password-page .form-group {
                    margin-bottom: 20px;
                }

                .form-label {
                    display: block;
                    margin-bottom: 5px;
                    font-weight: 500;
                    color: #555;
                }

                .reset-password-page .form-control {
                    width: 100%;
                    padding: 12px;
                    border: 1px solid #ddd;
                    border-radius: 4px;
                    font-size: 16px;
                }

                .reset-password-page .form-control:focus {
                    outline: none;
                    border-color: #007bff;
                    box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
                }

                .reset-password-page .btn-primary {
                    width: 100%;
                    padding: 12px;
                    background-color: #007bff;
                    color: white;
                    border: none;
                    border-radius: 4px;
                    font-size: 16px;
                    font-weight: 500;
                    cursor: pointer;
                }

                .reset-password-page .btn-primary:hover {
                    background-color: #0056b3;
                }

                .reset-password-page .signin-link {
                    text-align: center;
                    margin-top: 20px;
                    font-size: 14px;
                    color: #666;
                }

                .reset-password-page .signin-link a {
                    color: #007bff;
                    text-decoration: none;
                }

                .reset-password-page .signin-link a:hover {
                    text-decoration: underline;
                }


                .reset-password-page .error-text {
                    color: #dc3545;
                    font-size: 14px;
                    margin-top: 5px;
                }

                .reset-password-page .status-message {
                    background-color: #d4edda;
                    color: #155724;
                    padding: 10px;
                    border-radius: 4px;
                    margin-bottom: 20px;
                    font-size: 14px;
                }
            </style>

        @endpush


    <div class="register-container">
        <div class="register-card">
            <!-- Form Section -->
            <div class="form-section">
                <h2 class="form-title">Reset Password</h2>
                <p style="text-align: center; margin-bottom: 20px;">
                    {{ __('Forgot your password? No problem. Just let us know your email address and we will email you a password reset link.') }}
                </p>

                <!-- Session Status -->
                @if (session('status'))
                    <div class="status-message">
                        {{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('password.email') }}" id="forgotForm">
                    @csrf
                    <input type="hidden" name="recaptcha_token" id="recaptcha_token">


                    <div class="form-group">
                        <label for="email" class="form-label">Email</label>
                        <input id="email"
                               type="email"
                               name="email"
                               class="form-control @error('email') is-invalid @enderror"
                               value="{{ old('email') }}"
                               required
                               autofocus>
                        @error('email')
                        <div class="error-text">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn-primary">Send Password Reset Link</button>
                    </div>

                    <div class="signin-link">
                        Remember your password?
                        <a href="{{ route('login') }}">Back to Login</a>
                    </div>
                </form>
            </div>

        </div>
    </div>
        @push('scripts')
            <script>
                grecaptcha.ready(function () {
                    document.getElementById('forgotForm').addEventListener("submit", function (event) {
                        event.preventDefault();
                        grecaptcha.execute('{{ config('services.recaptcha.site_key') }}', {action: 'forgot'})
                            .then(function (token) {
                                document.getElementById("recaptcha_token").value = token;
                                document.getElementById('forgotForm').submit();
                            });
                    });
                });
            </script>
        @endpush
</x-guest-layout>
