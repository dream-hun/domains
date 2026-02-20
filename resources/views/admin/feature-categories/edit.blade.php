<x-admin-layout>
    @section('page-title')
        Edit Feature Category
    @endsection

    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Edit Feature Category</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.feature-categories.index') }}">Feature Categories</a></li>
                        <li class="breadcrumb-item active">Edit</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4>Edit Feature Category</h4>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.feature-categories.update', $featureCategory) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label required">Name <span class="text-danger">*</span></label>
                                    <input type="text" name="name" id="name"
                                        class="form-control @error('name') is-invalid @enderror"
                                        value="{{ old('name', $featureCategory->name) }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="slug" class="form-label">Slug</label>
                                    <input type="text" name="slug" id="slug"
                                        class="form-control @error('slug') is-invalid @enderror"
                                        value="{{ old('slug', $featureCategory->slug) }}">
                                    @error('slug')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">URL-friendly identifier. Leave empty to auto-generate from name.</small>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="icon" class="form-label">Icon</label>
                                    <input type="text" name="icon" id="icon"
                                        class="form-control @error('icon') is-invalid @enderror"
                                        value="{{ old('icon', $featureCategory->icon) }}"
                                        placeholder="bi bi-stars">
                                    @error('icon')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Bootstrap Icons class (e.g., bi bi-stars, bi bi-cpu)</small>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="sort_order" class="form-label">Sort Order</label>
                                    <input type="number" name="sort_order" id="sort_order"
                                        class="form-control @error('sort_order') is-invalid @enderror"
                                        value="{{ old('sort_order', $featureCategory->sort_order ?? 0) }}" min="0">
                                    @error('sort_order')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Lower numbers appear first</small>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="status" class="form-label required">Status <span class="text-danger">*</span></label>
                                    <select name="status" id="status"
                                        class="form-control select2bs4 @error('status') is-invalid @enderror" required>
                                        <option value="">Select Status</option>
                                        @foreach ($statuses as $status)
                                            <option value="{{ $status->value }}"
                                                {{ old('status', $featureCategory->status?->value) == $status->value ? 'selected' : '' }}>
                                                {{ $status->label() }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea name="description" id="description" rows="3"
                                    class="form-control @error('description') is-invalid @enderror">{{ old('description', $featureCategory->description) }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mt-4 d-flex justify-content-end">
                                <a href="{{ route('admin.feature-categories.index') }}"
                                    class="btn btn-secondary mr-2">
                                    <i class="bi bi-dash-circle"></i> Cancel
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-floppy"></i> Update Category
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>

@push('scripts')
<script>
    $(function () {
        $('.select2bs4').select2({
            theme: 'bootstrap4',
            width: '100%'
        });
    });
</script>
@endpush
