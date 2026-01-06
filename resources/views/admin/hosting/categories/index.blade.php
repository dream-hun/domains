<x-admin-layout>
    @section('page-title')
        Hosting Categories
    @endsection

    <section class="content-header">
        <div class="container-fluid col-md-12">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Hosting Categories</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Hosting Categories</li>
                    </ol>
                </div>
            </div>
        </div>
        <div class="row mb-2 push-right">
            <div class="col-lg-12">
                @can('hosting_category_create')
                    <a class="btn btn-success push-right" href="{{ route('admin.hosting-categories.create') }}">
                        <i class="bi bi-plus-circle"></i> Create Category
                    </a>
                @endcan
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Hosting Categories</h3>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover datatable">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>

                            <th>Icon</th>

                            <th>Status</th>
                            <th>Sort</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($categories as $category)
                            <tr>
                                <td>{{ $category->id }}</td>
                                <td>{{ $category->name }}</td>

                                <td>
                                    @if ($category->icon)
                                        <i class="{{ $category->icon }}"></i>
                                    @else
                                        -
                                    @endif
                                </td>

                                <td>
                                    @if ($category->status)
                                        <span class="badge {{ $category->status->color() }}">
                                                    {{ $category->status->label() }}
                                                </span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ $category->sort ?? '-' }}</td>
                                <td>{{ $category->created_at ? $category->created_at->format('Y-m-d H:i') : '-' }}</td>
                                <td>
                                    @can('hosting_category_edit')
                                        <a class="btn btn-sm btn-primary"
                                           href="{{ route('admin.hosting-categories.edit', $category) }}">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                    @endcan
                                    @can('hosting_category_delete')
                                        <form action="{{ route('admin.hosting-categories.destroy', $category) }}"
                                              method="POST"
                                              class="d-inline"
                                              onsubmit="return confirm('Are you sure you want to delete this category?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="bi bi-trash"></i> Delete
                                            </button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>


    @section('scripts')
        @parent
        <script>
            $(function () {
                $('.datatable').DataTable({
                    order: [[6, 'asc']],
                    pageLength: 25,
                    paging: true,
                    searching: true,
                    info: true,
                });
            });
        </script>
    @endsection

</x-admin-layout>
