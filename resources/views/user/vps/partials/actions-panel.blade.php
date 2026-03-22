<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-cogs mr-1"></i> Actions</h3>
    </div>
    <div class="card-body">
        @if (session('generated_password'))
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <strong><i class="fas fa-key mr-1"></i> Generated Root Password:</strong>
                <code class="ml-1 user-select-all">{{ session('generated_password') }}</code>
                <br><small class="text-muted">Save this password now. It will not be shown again.</small>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif
        <div class="row">
            {{-- Power Actions --}}
            <div class="col-md-6">
                <h5 class="mb-3">Power Management</h5>
                <div class="btn-group-vertical w-100" role="group">
                    @if (($instance['status'] ?? '') === 'stopped')
                        @can('vps_start')
                            <form method="POST" action="{{ route('user.vps.start', $subscription) }}" onsubmit="return confirm('Are you sure you want to start this instance?')">
                                @csrf
                                <button type="submit" class="btn btn-outline-success text-left w-100 mb-2">
                                    <i class="fas fa-play mr-2"></i> Start Instance
                                </button>
                            </form>
                        @endcan
                    @endif
                    @if (($instance['status'] ?? '') === 'running')
                        @can('vps_shutdown')
                            <form method="POST" action="{{ route('user.vps.shutdown', $subscription) }}" onsubmit="return confirm('Are you sure you want to shut down this instance?')">
                                @csrf
                                <button type="submit" class="btn btn-outline-danger text-left w-100 mb-2">
                                    <i class="fas fa-power-off mr-2"></i> Shutdown Instance
                                </button>
                            </form>
                        @endcan
                        @can('vps_restart')
                            <form method="POST" action="{{ route('user.vps.restart', $subscription) }}" onsubmit="return confirm('Are you sure you want to restart this instance?')">
                                @csrf
                                <button type="submit" class="btn btn-outline-warning text-left w-100 mb-2">
                                    <i class="fas fa-redo mr-2"></i> Restart Instance
                                </button>
                            </form>
                        @endcan
                    @endif
                    @can('vps_rescue')
                        <form method="POST" action="{{ route('user.vps.rescue', $subscription) }}" onsubmit="return confirm('Boot into rescue mode? The instance will be restarted.')">
                            @csrf
                            <button type="submit" class="btn btn-outline-info text-left w-100 mb-2">
                                <i class="fas fa-life-ring mr-2"></i> Rescue Mode
                            </button>
                        </form>
                    @endcan
                </div>
            </div>

            {{-- Configuration Actions --}}
            <div class="col-md-6">
                <h5 class="mb-3">Configuration</h5>

                @can('vps_change_display_name')
                    <form method="POST" action="{{ route('user.vps.display-name', $subscription) }}" class="mb-3">
                        @csrf
                        <div class="input-group">
                            <input type="text" name="display_name" class="form-control" placeholder="Display name" value="{{ old('display_name', $instance['display_name'] ?? '') }}">
                            <div class="input-group-append">
                                <button type="submit" class="btn btn-outline-primary">
                                    <i class="fas fa-save"></i> Update Name
                                </button>
                            </div>
                        </div>
                        @error('display_name')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </form>
                @endcan

                @can('vps_reset_credentials')
                    <form method="POST" action="{{ route('user.vps.reset-credentials', $subscription) }}" onsubmit="return confirm('Reset instance credentials? A new password will be generated.')">
                        @csrf
                        <button type="submit" class="btn btn-outline-secondary text-left w-100 mb-2">
                            <i class="fas fa-key mr-2"></i> Reset Credentials
                        </button>
                    </form>
                @endcan

                @can('vps_reinstall')
                    <form method="POST" action="{{ route('user.vps.reinstall', $subscription) }}" onsubmit="return confirm('Are you sure you want to reinstall this instance? All data will be lost.')" class="mb-3">
                        @csrf
                        <div class="input-group">
                            <input type="text" name="imageId" class="form-control" placeholder="Image ID" value="{{ old('imageId', $instance['image_id'] ?? '') }}">
                            <div class="input-group-append">
                                <button type="submit" class="btn btn-outline-danger">
                                    <i class="fas fa-sync-alt"></i> Reinstall
                                </button>
                            </div>
                        </div>
                        @error('imageId')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </form>
                @endcan

                @can('vps_vnc_access')
                    @if (!empty($instance['vnc_url']))
                        <a href="{{ $instance['vnc_url'] }}" target="_blank" class="btn btn-outline-primary text-left w-100 mb-2">
                            <i class="fas fa-desktop mr-2"></i> Open VNC Console
                        </a>
                    @endif
                @endcan
            </div>
        </div>
    </div>
</div>
