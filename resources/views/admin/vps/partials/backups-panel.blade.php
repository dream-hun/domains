<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-hdd mr-1"></i> Automated Backups</h3>
    </div>
    <div class="card-body">
        @if (! empty($backupError))
            <div class="alert alert-warning mb-0">
                <i class="fas fa-exclamation-triangle mr-1"></i> {{ $backupError }}
            </div>
        @elseif (empty($backups))
            <p class="text-muted text-center py-3">No automated backups found. This feature may need to be activated.</p>
        @else
            <div class="table-responsive">
                <table class="table table-hover table-sm">
                    <thead>
                        <tr>
                            <th>Backup ID</th>
                            <th>Name</th>
                            <th>Size</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($backups as $backup)
                            <tr>
                                <td><code>{{ $backup['backupId'] ?? $backup['id'] ?? 'N/A' }}</code></td>
                                <td>{{ $backup['name'] ?? 'N/A' }}</td>
                                <td>{{ isset($backup['sizeMb']) ? round($backup['sizeMb'] / 1024, 2) . ' GB' : 'N/A' }}</td>
                                <td>{{ $backup['createdDate'] ?? 'N/A' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
