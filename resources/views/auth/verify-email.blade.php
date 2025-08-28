<x-guest-layout>
    @section('page-title')
        Email Verification
    @endsection
    @push('styles')
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            body {
                background-color: #f8fafc;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                color: #334155;
                line-height: 1.6;
            }

            .verification-container {

                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
                margin-top: 180px;
            }

            .verification-card {
                background: white;
                border-radius: 12px;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
                max-width: 420px;
                width: 100%;
                padding: 40px;
                text-align: center;
            }

            .verification-icon {
                width: 64px;
                height: 64px;
                background-color: #3b82f6;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 24px;
            }

            .verification-title {
                font-size: 24px;
                font-weight: 600;
                color: #1e293b;
                margin-bottom: 12px;
            }

            .verification-subtitle {
                font-size: 16px;
                color: #64748b;
                margin-bottom: 32px;
            }

            .alert {
                padding: 16px;
                border-radius: 8px;
                margin-bottom: 24px;
                font-size: 14px;
                text-align: left;
            }

            .alert-info {
                background-color: #dbeafe;
                color: #1e40af;
                border: 1px solid #bfdbfe;
            }

            .alert-success {
                background-color: #dcfce7;
                color: #166534;
                border: 1px solid #bbf7d0;
            }

            .btn-primary {
                width: 100%;
                padding: 12px 24px;
                background-color: #3b82f6;
                color: white;
                border: none;
                border-radius: 8px;
                font-size: 16px;
                font-weight: 500;
                cursor: pointer;
                transition: background-color 0.2s;
            }

            .btn-primary:hover {
                background-color: #2563eb;
            }

            .btn-primary:active {
                background-color: #1d4ed8;
            }

            .signin-link {
                margin-top: 24px;
                color: #64748b;
                font-size: 14px;
            }

            .signin-link a {
                color: #3b82f6;
                text-decoration: none;
            }

            .signin-link a:hover {
                text-decoration: underline;
            }

            @media (max-width: 480px) {
                .verification-card {
                    padding: 32px 24px;
                    margin: 16px;
                }

                .verification-title {
                    font-size: 22px;
                }
            }
        </style>
    @endpush

    <div class="verification-container">
        <div class="verification-card">
            <div class="verification-icon">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M3 8L10.89 13.26C11.2187 13.4793 11.6049 13.5963 12 13.5963C12.3951 13.5963 12.7813 13.4793 13.11 13.26L21 8M5 19H19C19.5304 19 20.0391 18.7893 20.4142 18.4142C20.7893 18.0391 21 17.5304 21 17V7C21 6.46957 20.7893 5.96086 20.4142 5.58579C20.0391 5.21071 19.5304 5 19 5H5C4.46957 5 3.96086 5.21071 3.58579 5.58579C3.21071 5.96086 3 6.46957 3 7V17C3 17.5304 3.21071 18.0391 3.58579 18.4142C3.96086 18.7893 4.46957 19 5 19Z"
                        stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>

            <h1 class="verification-title">Verify Your Email</h1>
            <p class="verification-subtitle">Check your inbox and click the verification link to continue.</p>

            <div class="alert alert-info" role="alert">
                {{ __('Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.') }}
            </div>

            @if (session('status') == 'verification-link-sent')
                <div class="alert alert-success" role="alert">
                    {{ __('A new verification link has been sent to the email address you provided during registration.') }}
                </div>
            @endif

            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit" class="btn btn-primary">
                    {{ __('Resend Verification Email') }}
                </button>
            </form>

            <p class="signin-link">
                Already verified? <a href="{{ route('login') }}">Sign in</a>
            </p>
        </div>
    </div>
</x-guest-layout>
