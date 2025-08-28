<x-guest-layout>
    @section('page-title')
        Login
    @endsection

    @push('styles')
        <style>
            * {
                box-sizing: border-box;
            }

            .reset-password-page body {
                background-color: #f8f9fa;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            }

            .reset-password-page .login-container {
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
                margin-top: 40px;
                min-height: calc(100vh - 200px);
                margin-top: 180px;

            }

            .reset-password-page .login-card {
                background: white;
                border-radius: 12px;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
                max-width: 400px;
                width: 100%;
                overflow: hidden;
            }

            .reset-password-page .form-section {
                padding: 40px;
            }

            .reset-password-page .form-title {
                font-size: 28px;
                font-weight: 600;
                color: #333;
                margin-bottom: 30px;
                text-align: center;
            }

            .reset-password-page .form-group {
                margin-bottom: 20px;
            }

            .reset-password-page .form-label {
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

            .reset-password-page .form-control.error {
                border-color: #dc3545;
            }

            .reset-password-page .password-field {
                position: relative;
            }

            .reset-password-page .password-toggle {
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
                margin-right: -160px;
            }

            .reset-password-page .password-toggle:hover {
                color: #333;
            }



            .reset-password-page .btn-primary {
                width: 100%;
                padding: 16px;
                background:#007bff;
                color: white;
                border: none;
                border-radius: 8px;
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s ease;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }

            .reset-password-page .btn-primary:hover {
                background:  #0056b3;
                transform: translateY(-2px);
                box-shadow: 0 5px 15px rgba(0, 123, 255, 0.3);
            }

            .reset-password-page .btn-primary:active {
                transform: translateY(0);
            }

            .reset-password-page .btn-primary:disabled {
                opacity: 0.6;
                cursor: not-allowed;
                transform: none;
            }

            .reset-password-page .remember-forgot-row {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 25px;
                flex-wrap: wrap;
                gap: 10px;
            }

            .reset-password-page .remember-me {
                display: flex;
                align-items: center;
                font-size: 14px;
                color: #555;
                user-select: none;
            }

            .reset-password-page .remember-me input[type="checkbox"] {
                margin-right: 8px;
                width: 16px;
                height: 16px;
                cursor: pointer;
            }

            .reset-password-page .forgot-link {
                font-size: 14px;
                color: #007bff;
                text-decoration: none;
                font-weight: 500;
                transition: color 0.2s ease;
            }

            .reset-password-page .forgot-link:hover {
                color: #0056b3;
                text-decoration: underline;
            }

            .reset-password-page .signup-link {
                text-align: center;
                margin-top: 25px;
                font-size: 14px;
                color: #666;
            }

            .reset-password-page .signup-link a {
                color: #007bff;
                text-decoration: none;
                font-weight: 500;
            }

            .reset-password-page .signup-link a:hover {
                text-decoration: underline;
            }

            .reset-password-page .error-text {
                color: #dc3545;
                font-size: 13px;
                margin-top: 5px;
                font-weight: 500;
            }

            .reset-password-page .status-message {
                background-color: #d4edda;
                color: #155724;
                padding: 12px 16px;
                border-radius: 8px;
                margin-bottom: 20px;
                font-size: 14px;
                border: 1px solid #c3e6cb;
            }




        </style>
    @endpush

    <div class="login-container">
        <div class="login-card">
            <div class="form-section">
                <!-- Session Status -->
                @if (session('status'))
                    <div class="status-message">
                        {{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" id="loginForm">
                    @csrf
                    <input type="hidden" name="recaptcha_token" id="recaptcha_token">
                    <h2 class="form-title">Sign In</h2>

                    <!-- Email -->
                    <div class="form-group">
                        <label for="email" class="form-label">Email</label>
                        <input id="email"
                               type="email"
                               name="email"
                               class="form-control {{ $errors->has('email') ? 'error' : '' }}"
                               value="{{ old('email') }}"
                               required
                               placeholder="Enter your email"
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
                                   class="form-control {{ $errors->has('password') ? 'error' : '' }}"
                                   required
                                   placeholder="Enter password"
                                   autocomplete="off">
                            <button type="button"
                                    class="password-toggle"
                                    @click="showPassword = !showPassword"
                                    :title="showPassword ? 'Hide password' : 'Show password'">
                                <span x-show="!showPassword"><i class="bi bi-eye"></i></span>
                                <span x-show="showPassword"><i class="bi bi-eye-slash"></i></span>
                            </button>
                        </div>
                        @error('password')
                        <div class="error-text">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Remember Me and Forgot Password -->
                    <div class="remember-forgot-row">
                        <label class="remember-me">
                            <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
                            Remember Me
                        </label>
                        <a href="{{ route('password.request') }}" class="forgot-link">
                            Forgot Password?
                        </a>
                    </div>

                    <!-- Login Button -->
                    <div class="form-group">
                        <button type="submit" class="btn-primary" id="loginButton">
                            Sign In
                        </button>
                    </div>

                    <!-- Sign Up Link -->
                    <div class="signup-link">
                        Don't have an account? <a href="{{ route('register') }}">Sign Up</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            // Add loading state to button on form submission
            document.getElementById('loginForm').addEventListener('submit', function() {
                const submitButton = document.getElementById('loginButton');
                submitButton.disabled = true;
                submitButton.textContent = 'Signing In...';
            });

            // reCAPTCHA integration
            @if(config('services.recaptcha.site_key'))
            grecaptcha.ready(function () {
                document.getElementById('loginForm').addEventListener("submit", function (event) {
                    event.preventDefault();

                    const submitButton = document.getElementById('loginButton');
                    submitButton.disabled = true;
                    submitButton.textContent = 'Verifying...';

                    grecaptcha.execute('{{ config('services.recaptcha.site_key') }}', {action: 'login'})
                        .then(function (token) {
                            document.getElementById("recaptcha_token").value = token;
                            document.getElementById('loginForm').submit();
                        })
                        .catch(function(error) {
                            console.error('reCAPTCHA error:', error);
                            submitButton.disabled = false;
                            submitButton.textContent = 'Sign In';
                        });
                });
            });
            @endif
        </script>
    @endpush
</x-guest-layout>
