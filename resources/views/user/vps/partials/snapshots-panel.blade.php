<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-camera mr-1"></i> Snapshots
            @if (isset($maxSnapshots))
                <small class="text-muted">({{ count($snapshots) }} / {{ $maxSnapshots }} used)</small>
            @endif
        </h3>
    </div>
    <div class="card-body">
        {{-- Create Snapshot Form --}}
        @can('vps_snapshot_create')
            <form method="POST" action="{{ route('user.vps.snapshots.store', $subscription) }}" class="mb-4">
                @csrf
                <div class="row">
                    <div class="col-md-4">
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" placeholder="Snapshot name" value="{{ old('name') }}">
                        @error('name')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <input type="text" name="description" class="form-control" placeholder="Description (optional)" value="{{ old('description') }}">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Create Snapshot
                        </button>
                    </div>
                </div>
            </form>
        @endcan

        {{-- Snapshot List --}}
        @if (empty($snapshots))
            <p class="text-muted text-center py-3">No snapshots found.</p>
        @else
            <div class="table-responsive">
                <table class="table table-hover table-sm">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($snapshots as $snapshot)
                            <tr>
                                <td>{{ $snapshot['name'] ?? 'N/A' }}</td>
                                <td>{{ $snapshot['description'] ?? '-' }}</td>
                                <td>{{ $snapshot['createdDate'] ?? 'N/A' }}</td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        @can('vps_backup_restore')
                                            <form method="POST" action="{{ route('user.vps.snapshots.restore', [$subscription, $snapshot['snapshotId']]) }}" class="d-inline" onsubmit="return confirm('Restore this snapshot? The current state will be overwritten.')">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-success btn-sm" title="Restore">
                                                    <i class="fas fa-undo"></i>
                                                </button>
                                            </form>
                                        @endcan
                                        @can('vps_snapshot_delete')
                                            <form method="POST" action="{{ route('user.vps.snapshots.destroy', [$subscription, $snapshot['snapshotId']]) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this snapshot?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger btn-sm" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
