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
                            <form method="POST" action="{{ route('admin.vps.start', $subscription) }}" onsubmit="return confirm('Are you sure you want to start this instance?')">
                                @csrf
                                <button type="submit" class="btn btn-outline-success text-left w-100 mb-2">
                                    <i class="fas fa-play mr-2"></i> Start Instance
                                </button>
                            </form>
                        @endcan
                    @endif
                    @if (($instance['status'] ?? '') === 'running')
                        @can('vps_shutdown')
                            <form method="POST" action="{{ route('admin.vps.shutdown', $subscription) }}" onsubmit="return confirm('Are you sure you want to shut down this instance?')">
                                @csrf
                                <button type="submit" class="btn btn-outline-danger text-left w-100 mb-2">
                                    <i class="fas fa-power-off mr-2"></i> Shutdown Instance
                                </button>
                            </form>
                        @endcan
                        @can('vps_restart')
                            <form method="POST" action="{{ route('admin.vps.restart', $subscription) }}" onsubmit="return confirm('Are you sure you want to restart this instance?')">
                                @csrf
                                <button type="submit" class="btn btn-outline-warning text-left w-100 mb-2">
                                    <i class="fas fa-redo mr-2"></i> Restart Instance
                                </button>
                            </form>
                        @endcan
                    @endif
                    @can('vps_rescue')
                        <form method="POST" action="{{ route('admin.vps.rescue', $subscription) }}" onsubmit="return confirm('Boot into rescue mode? The instance will be restarted.')">
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
                    <form method="POST" action="{{ route('admin.vps.display-name', $subscription) }}" class="mb-3">
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
                    <form method="POST" action="{{ route('admin.vps.reset-credentials', $subscription) }}" onsubmit="return confirm('Reset instance credentials? A new password will be generated.')">
                        @csrf
                        <button type="submit" class="btn btn-outline-secondary text-left w-100 mb-2">
                            <i class="fas fa-key mr-2"></i> Reset Credentials
                        </button>
                    </form>
                @endcan

                @can('vps_reinstall')
                    <form method="POST" action="{{ route('admin.vps.reinstall', $subscription) }}" onsubmit="return confirm('Are you sure you want to reinstall this instance? All data will be lost.')" class="mb-3">
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

                @can('vps_upgrade')
                    <form method="POST" action="{{ route('admin.vps.upgrade', $subscription) }}" onsubmit="return confirm('Are you sure you want to upgrade this instance?')">
                        @csrf
                        <button type="submit" class="btn btn-outline-success text-left w-100 mb-2">
                            <i class="fas fa-arrow-up mr-2"></i> Upgrade Instance
                        </button>
                    </form>
                @endcan

                @can('vps_vnc_access')
                    @if (!empty($instance['vnc_url']))
                        <a href="{{ $instance['vnc_url'] }}" target="_blank" class="btn btn-outline-primary text-left w-100 mb-2">
                            <i class="fas fa-desktop mr-2"></i> Open VNC Console
                        </a>
                    @endif
                @endcan

                @can('vps_order_license')
                    <form method="POST" action="{{ route('admin.vps.order-license', $subscription) }}" onsubmit="return confirm('Order a license for this instance?')" class="mb-3">
                        @csrf
                        <div class="input-group">
                            <select name="license_type" class="form-control">
                                <option value="">Select license type...</option>
                                <option value="cPanel" {{ old('license_type') === 'cPanel' ? 'selected' : '' }}>cPanel</option>
                                <option value="Plesk" {{ old('license_type') === 'Plesk' ? 'selected' : '' }}>Plesk</option>
                            </select>
                            <div class="input-group-append">
                                <button type="submit" class="btn btn-outline-primary">
                                    <i class="fas fa-certificate"></i> Order License
                                </button>
                            </div>
                        </div>
                        @error('license_type')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </form>
                @endcan

                @can('vps_extend_storage')
                    <form method="POST" action="{{ route('admin.vps.extend-storage', $subscription) }}" onsubmit="return confirm('Extend storage for this instance?')" class="mb-3">
                        @csrf
                        <div class="input-group">
                            <input type="number" name="storage_gb" class="form-control" placeholder="Additional storage (GB)" min="1" value="{{ old('storage_gb') }}">
                            <div class="input-group-append">
                                <button type="submit" class="btn btn-outline-primary">
                                    <i class="fas fa-hdd"></i> Extend Storage
                                </button>
                            </div>
                        </div>
                        @error('storage_gb')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </form>
                @endcan

                @can('vps_move_region')
                    <form method="POST" action="{{ route('admin.vps.move-region', $subscription) }}" onsubmit="return confirm('This will create a snapshot as the first step of region migration. Continue?')" class="mb-3">
                        @csrf
                        <div class="input-group">
                            <input type="text" name="target_region" class="form-control" placeholder="Target region (e.g. EU)" value="{{ old('target_region') }}">
                            <div class="input-group-append">
                                <button type="submit" class="btn btn-outline-warning">
                                    <i class="fas fa-globe"></i> Move Region
                                </button>
                            </div>
                        </div>
                        @error('target_region')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                        <small class="text-muted">Note: Region migration is semi-automated. A snapshot will be created first, then manual steps are required.</small>
                    </form>
                @endcan

                @can('vps_cancel')
                    <form method="POST" action="{{ route('admin.vps.cancel', $subscription) }}" onsubmit="return confirm('Are you sure you want to cancel this VPS instance? This action schedules deletion.')">
                        @csrf
                        <button type="submit" class="btn btn-danger text-left w-100 mb-2">
                            <i class="fas fa-trash mr-2"></i> Cancel Instance
                        </button>
                    </form>
                @endcan
            </div>
        </div>
    </div>
</div>
