@extends('layouts.admin')

@section('title', 'Notifications')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-bell mr-2"></i>
                            Notifications
                        </h3>
                        <div class="card-tools">
                            @if ($notifications->where('read_at', null)->count() > 0)
                                <form method="POST" action="{{ route('admin.notifications.mark-all-read') }}"
                                    class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-check mr-1"></i>
                                        Mark All Read
                                    </button>
                                </form>
                            @endif
                            <form method="POST" action="{{ route('admin.notifications.destroy-all') }}"
                                class="d-inline ml-2"
                                onsubmit="return confirm('Are you sure you want to delete all notifications?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="fas fa-trash mr-1"></i>
                                    Delete All
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="card-body p-0">
                        @if ($notifications->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Type</th>
                                            <th>Message</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($notifications as $notification)
                                            <tr class="{{ $notification->read_at ? '' : 'table-warning' }}">
                                                <td>
                                                    @if ($notification->type === 'App\Notifications\DomainRegisteredNotification')
                                                        <span class="badge badge-success">
                                                            <i class="fas fa-globe mr-1"></i>
                                                            Domain Registration
                                                        </span>
                                                    @elseif($notification->type === 'App\Notifications\DomainImportedNotification')
                                                        <span class="badge badge-info">
                                                            <i class="fas fa-download mr-1"></i>
                                                            Domain Import
                                                        </span>
                                                    @else
                                                        <span class="badge badge-secondary">
                                                            <i class="fas fa-bell mr-1"></i>
                                                            General
                                                        </span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($notification->type === 'App\Notifications\DomainRegisteredNotification')
                                                        <strong>Domain Registered:</strong>
                                                        {{ $notification->data['domain_name'] ?? 'Unknown' }}
                                                        <br>
                                                        <small class="text-muted">
                                                            Registration Period:
                                                            {{ $notification->data['registration_years'] ?? 1 }} year(s)
                                                        </small>
                                                    @elseif($notification->type === 'App\Notifications\DomainImportedNotification')
                                                        @if (($notification->data['total_imported'] ?? 1) > 1)
                                                            <strong>Bulk Import:</strong>
                                                            {{ $notification->data['total_imported'] }} domains imported
                                                            <br>
                                                            <small class="text-muted">
                                                                Including:
                                                                {{ $notification->data['domain_name'] ?? 'Unknown' }}
                                                            </small>
                                                        @else
                                                            <strong>Domain Imported:</strong>
                                                            {{ $notification->data['domain_name'] ?? 'Unknown' }}
                                                        @endif
                                                    @else
                                                        {{ $notification->data['message'] ?? 'New notification' }}
                                                    @endif
                                                </td>
                                                <td>
                                                    <small class="text-muted">
                                                        {{ $notification->created_at->format('M d, Y H:i') }}
                                                        <br>
                                                        {{ $notification->created_at->diffForHumans() }}
                                                    </small>
                                                </td>
                                                <td>
                                                    @if ($notification->read_at)
                                                        <span class="badge badge-success">
                                                            <i class="fas fa-check mr-1"></i>
                                                            Read
                                                        </span>
                                                    @else
                                                        <span class="badge badge-warning">
                                                            <i class="fas fa-clock mr-1"></i>
                                                            Unread
                                                        </span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        @if (!$notification->read_at)
                                                            <form method="POST"
                                                                action="{{ route('admin.notifications.mark-read', $notification->id) }}"
                                                                class="d-inline">
                                                                @csrf
                                                                <button type="submit"
                                                                    class="btn btn-outline-primary btn-sm"
                                                                    title="Mark as Read">
                                                                    <i class="fas fa-check"></i>
                                                                </button>
                                                            </form>
                                                        @endif
                                                        <form method="POST"
                                                            action="{{ route('admin.notifications.destroy', $notification->id) }}"
                                                            class="d-inline"
                                                            onsubmit="return confirm('Are you sure you want to delete this notification?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-outline-danger btn-sm"
                                                                title="Delete">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-5">
                                <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No notifications yet</h5>
                                <p class="text-muted">You'll see domain-related notifications here when they occur.</p>
                            </div>
                        @endif
                    </div>

                    @if ($notifications->hasPages())
                        <div class="card-footer">
                            {{ $notifications->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
