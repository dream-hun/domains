<x-admin-layout>
    @section('page-title')
        Edit Hosting Category
    @endsection

    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4>Edit Hosting Category</h4>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.hosting-categories.update', $category) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label required">Name</label>
                                    <input type="text" name="name" id="name"
                                        class="form-control @error('name') is-invalid @enderror"
                                        value="{{ old('name', $category->name) }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="slug" class="form-label required">Slug</label>
                                    <input type="text" name="slug" id="slug"
                                        class="form-control @error('slug') is-invalid @enderror"
                                        value="{{ old('slug', $category->slug) }}" required>
                                    @error('slug')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">URL-friendly identifier (e.g., shared-hosting)</small>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="icon" class="form-label required">Icon</label>
                                    <input type="text" name="icon" id="icon"
                                        class="form-control @error('icon') is-invalid @enderror"
                                        value="{{ old('icon', $category->icon) }}" required
                                        placeholder="bi bi-server">
                                    @error('icon')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Bootstrap Icons class (e.g., bi bi-server)</small>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="sort" class="form-label">Sort Order</label>
                                    <input type="number" name="sort" id="sort"
                                        class="form-control @error('sort') is-invalid @enderror"
                                        value="{{ old('sort', $category->sort) }}" min="0">
                                    @error('sort')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="status" class="form-label required">Status</label>
                                    <select name="status" id="status"
                                        class="form-control select2bs4 @error('status') is-invalid @enderror" required>
                                        <option value="">Select Status</option>
                                        @foreach ($statuses as $status)
                                            <option value="{{ $status->value }}"
                                                {{ old('status', $category->status?->value) == $status->value ? 'selected' : '' }}>
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
                                    class="form-control @error('description') is-invalid @enderror">{{ old('description', $category->description) }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mt-4 d-flex justify-content-end">
                                <a href="{{ route('admin.hosting-categories.index') }}"
                                    class="btn btn-secondary mr-2">Cancel</a>
                                <button type="submit" class="btn btn-primary">Update Category</button>
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
