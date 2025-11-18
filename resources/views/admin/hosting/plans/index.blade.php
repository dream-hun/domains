<x-admin-layout>
    @section('page-title')
        Hosting Plans
    @endsection

    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Hosting Plans</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Hosting Plans</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid col-md-12">
            <div class="row">
                <div class="col-12">
                    <div class="py-lg-2">
                        <a href="{{ route('admin.hosting-plans.create') }}" class="btn btn-success">
                            <i class="bi bi-plus-lg"></i> Add Hosting Plan
                        </a>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Available Plans</h3>
                        </div>
                        <div class="card-body table-responsive p-0">
                            <table class="table table-hover text-nowrap">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Category</th>
                                        <th>Status</th>
                                        <th>Popular</th>
                                        <th>Sort Order</th>
                                        <th>Created</th>
                                        <th class="text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($plans as $plan)
                                        <tr>
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <span class="font-weight-bold">{{ $plan->name }}</span>
                                                    <small class="text-muted">{{ $plan->slug }}</small>
                                                </div>
                                            </td>
                                            <td>{{ $plan->category?->name ?? '—' }}</td>
                                            <td>
                                                @if ($plan->status)
                                                    <span class="{{ $plan->status->badge() }}">{{ $plan->status->label() }}</span>
                                                @else
                                                    <span class="badge bg-secondary">Unknown</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($plan->is_popular)
                                                    <span class="badge bg-success">Yes</span>
                                                @else
                                                    <span class="badge bg-secondary">No</span>
                                                @endif
                                            </td>
                                            <td>{{ $plan->sort_order }}</td>
                                            <td>{{ $plan->created_at?->format('M d, Y') }}</td>
                                            <td class="text-right">
                                                @can('hosting_plan_edit')
                                                    <a href="{{ route('admin.hosting-plans.edit', $plan) }}"
                                                        class="btn btn-sm btn-primary">
                                                        <i class="bi bi-pencil"></i> Edit
                                                    </a>
                                                @endcan
                                                @can('hosting_plan_delete')
                                                    <form action="{{ route('admin.hosting-plans.destroy', $plan) }}"
                                                        method="POST" class="d-inline"
                                                        onsubmit="return confirm('Are you sure you want to delete this plan?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-danger">
                                                            <i class="bi bi-trash"></i> Delete
                                                        </button>
                                                    </form>
                                                @endcan
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center py-4">
                                                No hosting plans found.
                                                @can('hosting_plan_create')
                                                 <a
                                                    href="{{ route('admin.hosting-plans.create') }}">Create one</a>.
                                                @endcan
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="mt-3">
                        {{ $plans->links('vendor.pagination.adminlte') }}
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-admin-layout>

