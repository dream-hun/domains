@extends('layouts.admin')

@section('title', 'Audit Activity')

@section('content')
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center">
                        <div>
                            <h3 class="card-title mb-1">
                                <i class="bi bi-clipboard-data mr-2"></i>
                                Audit Activity
                            </h3>
                            <p class="text-muted mb-0 small">
                                Monitor every model change, login, logout and password action across the platform.
                            </p>
                        </div>
                        <div class="mt-3 mt-md-0 text-muted small">
                            Showing
                            <strong>{{ $activities->firstItem() ?? 0 }}-{{ $activities->lastItem() ?? 0 }}</strong>
                            of
                            <strong>{{ $activities->total() }}</strong>
                            entries
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label for="search" class="form-label text-uppercase small text-muted">Search</label>
                                <input type="text" id="search" name="search" class="form-control"
                                    placeholder="Description, IP, URL or ID" value="{{ $filters['search'] ?? '' }}">
                            </div>
                            <div class="col-md-3">
                                <label for="event" class="form-label text-uppercase small text-muted">Event</label>
                                <select id="event" name="event" class="form-control">
                                    <option value="">All events</option>
                                    @foreach ($eventOptions as $option)
                                        <option value="{{ $option }}" @selected(($filters['event'] ?? null) === $option)>
                                            {{ \Illuminate\Support\Str::headline($option) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="subject_type" class="form-label text-uppercase small text-muted">Subject</label>
                                <select id="subject_type" name="subject_type" class="form-control">
                                    <option value="">All models</option>
                                    @foreach ($subjectOptions as $value => $label)
                                        <option value="{{ $value }}" @selected(($filters['subject_type'] ?? null) === $value)>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-funnel mr-1"></i>
                                    Filter
                                </button>
                            </div>
                            <div class="col-md-2 d-flex align-items-end mt-2 mt-md-0">
                                <a href="{{ route('admin.audit-logs.index') }}" class="btn btn-outline-secondary w-100">
                                    Reset
                                </a>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-striped align-middle">
                                <thead>
                                    <tr>
                                        <th>Description</th>
                                        <th>Event</th>
                                        <th>Subject</th>
                                        <th>Causer</th>
                                        <th>Request</th>
                                        <th>Recorded</th>
                                        <th>Details</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($activities as $activity)
                                        @php
                                            $requestMeta = (array) ($activity->properties['request'] ?? []);
                                            $subjectMeta = (array) ($activity->properties['subject'] ?? []);
                                        @endphp
                                        <tr>
                                            <td>
                                                <strong>{{ $activity->description }}</strong>
                                                <div class="text-muted small">
                                                    ID: {{ $activity->id }}
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge badge-pill badge-info text-uppercase">
                                                    {{ $activity->event ? \Illuminate\Support\Str::headline($activity->event) : 'Custom' }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <span class="font-weight-bold">
                                                        {{ $subjectMeta['label'] ?? ($activity->subject?->name ?? 'N/A') }}
                                                    </span>
                                                    <span class="text-muted small">
                                                        {{ $subjectMeta['type'] ?? ($activity->subject_type ?? 'Unknown') }}
                                                    </span>
                                                </div>
                                            </td>
                                            <td>
                                                @if ($activity->causer)
                                                    <div class="d-flex flex-column">
                                                        <span class="font-weight-bold">{{ $activity->causer->name }}</span>
                                                        <span
                                                            class="text-muted small">{{ $activity->causer->email }}</span>
                                                    </div>
                                                @else
                                                    <span class="badge badge-light">System</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="small">
                                                    <div>
                                                        <strong>IP:</strong> {{ $requestMeta['ip'] ?? '—' }}
                                                    </div>
                                                    <div>
                                                        <strong>Method:</strong> {{ $requestMeta['method'] ?? '—' }}
                                                    </div>
                                                    <div class="text-truncate" style="max-width: 220px;">
                                                        <strong>URL:</strong> {{ $requestMeta['url'] ?? '—' }}
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="small">
                                                    <div>{{ $activity->created_at->format('M d, Y H:i:s') }}</div>
                                                    <div class="text-muted">{{ $activity->created_at->diffForHumans() }}
                                                    </div>
                                                </div>
                                            </td>
                                            <td style="min-width: 200px;">
                                                <details>
                                                    <summary class="text-primary" style="cursor: pointer;">Expand</summary>
                                                    <pre class="bg-light rounded mt-2 p-2 small">{{ json_encode($activity->properties->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                                </details>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center py-5">
                                                <i class="bi bi-clipboard-check text-muted" style="font-size: 2rem;"></i>
                                                <p class="mt-3 mb-0 text-muted">No audit entries recorded yet.</p>
                                                <small class="text-muted">Actions performed across the application will
                                                    appear
                                                    here automatically.</small>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @if ($activities->hasPages())
                        <div class="card-footer d-flex justify-content-between flex-column flex-md-row align-items-center">
                            <small class="text-muted mb-3 mb-md-0">
                                Page {{ $activities->currentPage() }} of {{ $activities->lastPage() }}
                            </small>
                            {{ $activities->onEachSide(1)->links('pagination::bootstrap-4') }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
