
<style>
    [x-cloak] { display: none !important; }

    .delete-account-container {
        display: flex;
        align-items: center;
        justify-content: left;
        padding: 20px;
    }

    .delete-account-card {
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

    .form-control.is-invalid {
        border-color: #dc3545;
    }

    .invalid-feedback {
        color: #dc3545;
        font-size: 14px;
        margin-top: 5px;
        display: block;
    }

    .btn-danger {
        background-color: #dc3545;
        color: #fff;
        padding: 10px 20px;
        border: none;
        border-radius: 4px;
        font-size: 16px;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
    }

    .btn-danger:hover {
        background-color: #c82333;
        color: #fff;
        text-decoration: none;
    }

    .btn-secondary {
        background-color: #6c757d;
        color: #fff;
        padding: 10px 20px;
        border: none;
        border-radius: 4px;
        font-size: 16px;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        margin-right: 10px;
    }

    .btn-secondary:hover {
        background-color: #5a6268;
        color: #fff;
        text-decoration: none;
    }

    .description-text {
        color: #666;
        font-size: 14px;
        line-height: 1.5;
        margin-bottom: 20px;
        text-align: left;
    }

    .d-flex {
        display: flex;
        align-items: center;
    }

    .justify-end {
        justify-content: flex-end;
    }

    .modal {
        position: fixed;
        z-index: 1050;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
    }

    .modal-dialog {
        position: relative;
        width: auto;
        margin: 10% auto;
        max-width: 500px;
    }

    .modal-content {
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        padding: 24px;
    }

    .modal-title {
        font-size: 18px;
        font-weight: 600;
        color: #333;
        margin-bottom: 15px;
    }

    .sr-only {
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        margin: -1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        white-space: nowrap;
        border: 0;
    }
</style>

<!-- Delete Account Section -->
<div class="delete-account-container">
    <div class="delete-account-card">
        <div class="form-section">
            <h2 class="form-title">{{ __('Delete Account') }}</h2>

            <p class="description-text">
                {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.') }}
            </p>

            <button type="button"
                    class="btn-danger"
                    x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')">
                {{ __('Delete Account') }}
            </button>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal"
     id="confirm-user-deletion"
     x-cloak
     x-data="{
        show: @if($errors->userDeletion->isNotEmpty()) true @else false @endif,
        open() { this.show = true },
        close() { this.show = false }
     }"
     x-show="show"
     x-on:open-modal.window="if ($event.detail === 'confirm-user-deletion') open()"
     x-on:close.window="close()"
     x-on:keydown.escape.window="close()"
     x-on:click.self="close()">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="{{ route('profile.destroy') }}">
                @csrf
                @method('delete')

                <h2 class="modal-title">
                    {{ __('Are you sure you want to delete your account?') }}
                </h2>

                <p class="description-text">
                    {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
                </p>

                <div class="form-group">
                    <label for="password" class="form-label">{{ __('Password') }}</label>
                    <input type="password"
                           id="password"
                           name="password"
                           class="form-control @error('password', 'userDeletion') is-invalid @enderror"
                           placeholder="{{ __('Password') }}">
                    @error('password', 'userDeletion')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex justify-end">
                    <button type="button" class="btn-secondary" x-on:click="close()">
                        {{ __('Cancel') }}
                    </button>
                    <button type="submit" class="btn-danger">
                        {{ __('Delete Account') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
