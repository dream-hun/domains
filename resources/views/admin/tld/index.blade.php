@php use App\Enums\TldStatus; @endphp
<x-admin-layout page-title="TLDs">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">TLDs</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">TLDs</li>
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
                        <a href="{{ route('admin.tlds.create') }}" class="btn btn-success">
                            <i class="bi bi-plus-lg"></i> Add New TLD
                        </a>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h3 class="card-title">Manage TLDs</h3>
                            </div>
                        </div>
                        <div class="card-body">
                            @if (session('success'))
                                <div class="alert alert-success alert-dismissible">
                                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×
                                    </button>
                                    <h5><i class="icon fas fa-check"></i> Success!</h5>
                                    {{ session('success') }}
                                </div>
                            @endif

                            @if (session('error'))
                                <div class="alert alert-danger alert-dismissible">
                                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×
                                    </button>
                                    <h5><i class="icon fas fa-ban"></i> Error!</h5>
                                    {{ session('error') }}
                                </div>
                            @endif

                            @if ($errors->isNotEmpty())
                                <div class="alert alert-danger alert-dismissible">
                                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×
                                    </button>
                                    <h5><i class="icon fas fa-ban"></i> Validation Error</h5>
                                    <ul class="mb-0">
                                        @foreach ($errors->all() as $message)
                                            <li>{{ $message }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @if ($tlds->isEmpty())
                                <div class="alert alert-info">
                                    <h5><i class="icon fas fa-info"></i> Info!</h5>
                                    No TLDs found.
                                </div>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped table-hover datatable-Tld w-100">
                                        <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Status</th>
                                            <th>Type</th>
                                            <th>Actions</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach ($tlds as $tld)
                                            <tr>
                                                <td>{{ $tld->name }}</td>
                                                <td>
                                                    <span
                                                        class="badge badge-{{ $tld->status->value === 'active' ? 'success' : 'danger' }}">
                                                        {{ $tld->status->label() }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span
                                                        class="badge badge-{{ $tld->type->value === 'international' ? 'info' : 'success' }}">
                                                        {{ $tld->type->label() }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group">
                                                        <a href="{{ route('admin.tlds.edit', $tld) }}"
                                                           class="btn btn-warning btn-sm" title="Edit">
                                                            <i class="bi bi-pencil"></i> Edit
                                                        </a>
                                                        <form action="{{ route('admin.tlds.destroy', $tld) }}"
                                                              method="POST" style="display:inline-block;">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-danger btn-sm"
                                                                    onclick="return confirm('Are you sure you want to delete this TLD?');"
                                                                    title="Delete">
                                                                <i class="bi bi-trash"></i> Delete
                                                            </button>
                                                        </form>
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
                    </div>
                </div>
            </div>
        </div>
    </section>

    @section('styles')
        @parent
        <style>
            .datatable-Tld {
                width: 100% !important;
                table-layout: fixed;
            }
        </style>
    @endsection

    @section('scripts')
        @parent
        <script>
            $(function () {
                let dtButtons = $.extend(true, [], $.fn.dataTable.defaults.buttons)
                let table = $('.datatable-Tld:not(.ajaxTable)').DataTable({
                    buttons: dtButtons,
                    paging: true,
                    pageLength: 25,
                    lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                    searching: true,
                    ordering: true,
                    info: true,
                    lengthChange: true,
                    dom: 'lBfrtip',
                    autoWidth: false,
                    language: {
                        search: "Search:",
                        searchPlaceholder: "Search TLDs..."
                    }
                })
            })
        </script>
    @endsection
</x-admin-layout>
