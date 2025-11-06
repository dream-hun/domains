<x-admin-layout>
    @section('page-title')
        Failed Registration Details
    @endsection
    <section class="content-header">
        <div class="container-fluid col-md-12">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Failed Registration Details</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.failed-registrations.index') }}">Failed
                                Registrations</a></li>
                        <li class="breadcrumb-item active">Details</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <div style="margin-bottom: 10px;" class="row">
        <div class="col-lg-12">
            <a class="btn btn-secondary" href="{{ route('admin.failed-registrations.index') }}">
                <i class="bi bi-arrow-left"></i> Back to List
            </a>
            @can('failed_registration_retry')
                @if ($failedRegistration->canRetry())
                    <form action="{{ route('admin.failed-registrations.retry', $failedRegistration) }}" method="POST"
                        style="display: inline-block;">
                        @csrf
                        <button type="submit" class="btn btn-warning"
                            onclick="return confirm('Are you sure you want to retry registering {{ $failedRegistration->domain_name }}?')">
                            <i class="bi bi-arrow-repeat"></i> Retry Registration
                        </button>
                    </form>
                @endif
            @endcan
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Domain Information</h3>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <th style="width: 40%">Domain Name</th>
                                <td>{{ $failedRegistration->domain_name }}</td>
                            </tr>
                            <tr>
                                <th>Status</th>
                                <td>
                                    @if ($failedRegistration->status === 'pending')
                                        <span class="badge badge-warning">Pending</span>
                                    @elseif ($failedRegistration->status === 'retrying')
                                        <span class="badge badge-info">Retrying</span>
                                    @elseif ($failedRegistration->status === 'resolved')
                                        <span class="badge badge-success">Resolved</span>
                                    @elseif ($failedRegistration->status === 'abandoned')
                                        <span class="badge badge-danger">Abandoned</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Retry Count</th>
                                <td>{{ $failedRegistration->retry_count }} / {{ $failedRegistration->max_retries }}</td>
                            </tr>
                            <tr>
                                <th>Registration Years</th>
                                <td>{{ $failedRegistration->orderItem->years ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Created At</th>
                                <td>{{ $failedRegistration->created_at->format('Y-m-d H:i:s') }}</td>
                            </tr>
                            <tr>
                                <th>Last Attempted At</th>
                                <td>{{ $failedRegistration->last_attempted_at ? $failedRegistration->last_attempted_at->format('Y-m-d H:i:s') : 'Never' }}
                                </td>
                            </tr>
                            <tr>
                                <th>Next Retry At</th>
                                <td>{{ $failedRegistration->next_retry_at ? $failedRegistration->next_retry_at->format('Y-m-d H:i:s') : 'N/A' }}
                                </td>
                            </tr>
                            <tr>
                                <th>Resolved At</th>
                                <td>{{ $failedRegistration->resolved_at ? $failedRegistration->resolved_at->format('Y-m-d H:i:s') : 'Not resolved' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Order Information</h3>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <th style="width: 40%">Order Number</th>
                                <td>{{ $failedRegistration->order->order_number ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>User</th>
                                <td>{{ $failedRegistration->order->user->name ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>User Email</th>
                                <td>{{ $failedRegistration->order->user->email ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Order Status</th>
                                <td>
                                    <span
                                        class="badge badge-info">{{ $failedRegistration->order->status ?? 'N/A' }}</span>
                                </td>
                            </tr>
                            <tr>
                                <th>Order Total</th>
                                <td>{{ $failedRegistration->order->total ?? 'N/A' }}
                                    {{ $failedRegistration->order->currency ?? '' }}</td>
                            </tr>
                            <tr>
                                <th>Order Date</th>
                                <td>{{ $failedRegistration->order->created_at ? $failedRegistration->order->created_at->format('Y-m-d H:i:s') : 'N/A' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Failure Details</h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-danger">
                        <h5><i class="icon bi bi-exclamation-triangle"></i> Failure Reason</h5>
                        <p class="mb-0">{{ $failedRegistration->failure_reason }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($failedRegistration->contact_ids)
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Contact Information</h3>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered">
                            <tbody>
                                @foreach ($failedRegistration->contact_ids as $type => $contactId)
                                    <tr>
                                        <th style="width: 20%">{{ ucfirst($type) }} Contact ID</th>
                                        <td>{{ $contactId }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif
</x-admin-layout>
