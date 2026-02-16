@php use App\Enums\TldStatus; use App\Enums\TldType; @endphp
<x-admin-layout page-title="Add New TLD">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Add New TLD</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.tlds.index') }}">TLDs</a></li>
                        <li class="breadcrumb-item active">Add New TLD</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="col-md-12">
                <form class="card" method="POST" action="{{ route('admin.tlds.store') }}">
                    @csrf
                    <div class="card-body">
                        <div class="form-group">
                            <label for="name">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name"
                                class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}"
                                placeholder=".com" required>
                            @error('name')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                            <small class="form-text text-muted">TLD including leading dot (e.g. .com, .net)</small>
                        </div>
                        <div class="form-group">
                            <label for="type">Type <span class="text-danger">*</span></label>
                            <select name="type" id="type" class="form-control select2 @error('type') is-invalid @enderror" required>
                                @foreach (TldType::cases() as $case)
                                    <option value="{{ $case->value }}" {{ old('type') === $case->value ? 'selected' : '' }}>
                                        {{ $case->label() }}
                                    </option>
                                @endforeach
                            </select>
                            @error('type')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="status">Status <span class="text-danger">*</span></label>
                            <select name="status" id="status" class="form-control select2 @error('status') is-invalid @enderror" required>
                                @foreach (TldStatus::cases() as $case)
                                    <option value="{{ $case->value }}" {{ old('status') === $case->value ? 'selected' : '' }}>
                                        {{ $case->label() }}
                                    </option>
                                @endforeach
                            </select>
                            @error('status')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-floppy"></i> Create
                        </button>
                        <a href="{{ route('admin.tlds.index') }}" class="btn btn-secondary float-right">
                            <i class="bi bi-dash-circle"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </section>

    @section('scripts')
        @parent
        <script>
            $(function () {
                $('.select2').select2({
                    theme: 'bootstrap4',
                    width: '100%'
                })
            })
        </script>
    @endsection
</x-admin-layout>
