<x-admin-layout>
    @section('page-title')
        Failed Domain Registrations
    @endsection
    <section class="content-header">
        <div class="container-fluid col-md-12">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Failed Domain Registrations</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Failed Registrations</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <div style="margin-bottom: 10px;" class="row">
        <div class="col-lg-12">
            @can('failed_registration_retry')
                <a class="btn btn-success" href="{{ route('admin.failed-registrations.manual-register') }}">
                    <i class="bi bi-plus-circle"></i> Manual Register Domain
                </a>
            @endcan
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <span>Failed Registrations</span>
                <form method="GET" action="{{ route('admin.failed-registrations.index') }}" class="form-inline">
                    <label for="status" class="mr-2">Filter by Status:</label>
                    <select name="status" id="status" class="form-control form-control-sm mr-2"
                        onchange="this.form.submit()">
                        <option value="">All</option>
                        <option value="pending" {{ $selectedStatus === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="retrying" {{ $selectedStatus === 'retrying' ? 'selected' : '' }}>Retrying
                        </option>
                        <option value="abandoned" {{ $selectedStatus === 'abandoned' ? 'selected' : '' }}>Abandoned
                        </option>
                    </select>
                </form>
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table
                    class="table table-bordered table-striped table-hover datatable datatable-FailedRegistration w-100">
                    <thead>
                        <tr>
                            <th>Domain Name</th>
                            <th>User</th>
                            <th>Status</th>
                            <th>Retry Count</th>
                            <th>Failure Reason</th>
                            <th>Last Attempted</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($failedRegistrations as $registration)
                            <tr data-entry-id="{{ $registration->id }}">
                                <td>{{ $registration->domain_name }}</td>

                                <td>
                                    {{ $registration->order->user->name ?? 'N/A' }}
                                </td>
                                <td>
                                    @if ($registration->status === 'pending')
                                        <span class="badge badge-warning">Pending</span>
                                    @elseif ($registration->status === 'retrying')
                                        <span class="badge badge-info">Retrying</span>
                                    @elseif ($registration->status === 'resolved')
                                        <span class="badge badge-success">Resolved</span>
                                    @elseif ($registration->status === 'abandoned')
                                        <span class="badge badge-danger">Abandoned</span>
                                    @endif
                                </td>
                                <td>{{ $registration->retry_count }} / {{ $registration->max_retries }}</td>
                                <td>
                                    <span class="text-truncate d-inline-block" style="max-width: 200px;"
                                        title="{{ $registration->failure_reason }}">
                                        {{ $registration->failure_reason }}
                                    </span>
                                </td>
                                <td>
                                    {{ $registration->last_attempted_at ? $registration->last_attempted_at->format('Y-m-d H:i:s') : 'Never' }}
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        @can('failed_registration_access')
                                            <a href="{{ route('admin.failed-registrations.show', $registration) }}"
                                                class="btn btn-sm btn-info">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                        @endcan

                                        @can('failed_registration_retry')
                                            @if ($registration->canRetry())
                                                <form
                                                    action="{{ route('admin.failed-registrations.retry', $registration) }}"
                                                    method="POST" style="display: inline-block;">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-warning"
                                                        onclick="return confirm('Are you sure you want to retry registering {{ $registration->domain_name }}?')">
                                                        <i class="bi bi-arrow-repeat"></i> Retry
                                                    </button>
                                                </form>
                                            @endif
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center">No failed registrations found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-center mt-3 float-right">
                {{ $failedRegistrations->links('vendor.pagination.adminlte') }}
            </div>
        </div>
    </div>

    @section('styles')
        @parent
        <style>
            .datatable-FailedRegistration {
                width: 100% !important;
                table-layout: fixed;
            }

            .text-truncate {
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }
        </style>
    @endsection

    @section('scripts')
        @parent
        <script>
            $(function() {
                let dtButtons = $.extend(true, [], $.fn.dataTable.defaults.buttons)
                let table = $('.datatable-FailedRegistration:not(.ajaxTable)').DataTable({
                    buttons: dtButtons,
                    paging: false,
                    searching: true,
                    ordering: true,
                    info: false,
                    lengthChange: false,
                    dom: 'Bfrtip',
                    autoWidth: false,
                    language: {
                        search: "Search:",
                        searchPlaceholder: "Search registrations..."
                    }
                })
            })
        </script>
    @endsection
</x-admin-layout>
