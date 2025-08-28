<x-guest-layout>
    @section('page-title')
        Register
    @endsection

    <style>
        .reset-password-page body {
            background-color: #f8f9fa;
            font-family: "Inter", sans-serif;
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

        .reset-password-page .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 14px;
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

        .reset-password-page .form-row {
            display: flex;
            gap: 15px;
        }

        .reset-password-page .form-row .form-group {
            flex: 1;
        }

        .reset-password-page .recaptcha-error {
            text-align: center;
            margin-top: 10px;
        }
    </style>


    <div class="register-container">
        <div class="register-card">


            <!-- Form Section -->
            <div class="form-section">
                <!-- Session Status -->
                @if (session('success'))
                    <div class="status-message">
                        {{ session('success') }}
                    </div>
                @endif

                <!-- General Error Messages -->
                @if ($errors->any() && !$errors->has('first_name') && !$errors->has('last_name') && !$errors->has('email') && !$errors->has('password') && !$errors->has('recaptcha_token'))
                    <div class="error-message">
                        <ul style="margin: 0; padding-left: 20px;">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('register') }}" id="registerForm">
                    @csrf
                    <input type="hidden" name="recaptcha_token" id="recaptcha_token">
                    <h2 class="form-title">Create Account</h2>

                    <!-- Name Fields Row -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name" class="form-label">First Name</label>
                            <input id="first_name"
                                   type="text"
                                   name="first_name"
                                   class="form-control {{ $errors->has('first_name') ? 'error' : '' }}"
                                   value="{{ old('first_name') }}"
                                   required
                                   autofocus>
                            @error('first_name')
                            <div class="error-text">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="last_name" class="form-label">Last Name</label>
                            <input id="last_name"
                                   type="text"
                                   name="last_name"
                                   class="form-control {{ $errors->has('last_name') ? 'error' : '' }}"
                                   value="{{ old('last_name') }}"
                                   required>
                            @error('last_name')
                            <div class="error-text">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Email -->
                    <div class="form-group">
                        <label for="email" class="form-label">Email</label>
                        <input id="email"
                               type="email"
                               name="email"
                               class="form-control {{ $errors->has('email') ? 'error' : '' }}"
                               value="{{ old('email') }}"
                               required
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
                                   autocomplete="new-password">
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

                    <!-- Confirm Password -->
                    <div class="form-group" x-data="{ showConfirmPassword: false }">
                        <label for="password_confirmation" class="form-label">Confirm Password</label>
                        <div class="password-field">
                            <input id="password_confirmation"
                                   :type="showConfirmPassword ? 'text' : 'password'"
                                   name="password_confirmation"
                                   class="form-control {{ $errors->has('password_confirmation') ? 'error' : '' }}"
                                   required
                                   autocomplete="new-password">
                            <button type="button"
                                    class="password-toggle"
                                    @click="showConfirmPassword = !showConfirmPassword"
                                    :title="showConfirmPassword ? 'Hide password' : 'Show password'">
                                <span x-show="!showConfirmPassword"><i class="bi bi-eye"></i></span>
                                <span x-show="showConfirmPassword"><i class="bi bi-eye-slash"></i></span>
                            </button>
                        </div>
                        @error('password_confirmation')
                        <div class="error-text">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Register Button -->
                    <div class="form-group">
                        <button type="submit" class="btn-primary">
                            Register
                        </button>
                    </div>

                    <!-- reCAPTCHA Error Display -->
                    @error('recaptcha_token')
                    <div class="error-text recaptcha-error">{{ $message }}</div>
                    @enderror

                    <!-- Sign In Link -->
                    <div class="signin-link">
                        Already have an account? <a href="{{ route('login') }}">Sign In</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @push('scripts')
        <script>
            grecaptcha.ready(function () {
                document.getElementById('registerForm').addEventListener("submit", function (event) {
                    event.preventDefault();
                    grecaptcha.execute('{{ config('services.recaptcha.site_key') }}', {action: 'register'})
                        .then(function (token) {
                            document.getElementById("recaptcha_token").value = token;
                            document.getElementById('registerForm').submit();
                        });
                });
            });
        </script>
    @endpush
</x-guest-layout>
