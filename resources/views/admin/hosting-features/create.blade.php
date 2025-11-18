<x-admin-layout>
    @section('page-title')
        Create Hosting Feature
    @endsection

    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Create Hosting Feature</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.hosting-features.index') }}">Hosting Features</a></li>
                        <li class="breadcrumb-item active">Create</li>
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
                        <h4>Create Hosting Feature</h4>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.hosting-features.store') }}" method="POST">
                            @csrf

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label required">Name <span class="text-danger">*</span></label>
                                    <input type="text" name="name" id="name"
                                        class="form-control @error('name') is-invalid @enderror"
                                        value="{{ old('name') }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="slug" class="form-label">Slug</label>
                                    <input type="text" name="slug" id="slug"
                                        class="form-control @error('slug') is-invalid @enderror"
                                        value="{{ old('slug') }}"
                                        placeholder="Auto-generated from name">
                                    @error('slug')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">URL-friendly identifier. Leave empty to auto-generate from name.</small>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="feature_category_id" class="form-label">Feature Category</label>
                                    <select name="feature_category_id" id="feature_category_id"
                                        class="form-control @error('feature_category_id') is-invalid @enderror">
                                        <option value="">Select Category</option>
                                        @foreach ($featureCategories as $category)
                                            <option value="{{ $category->id }}"
                                                {{ old('feature_category_id') == $category->id ? 'selected' : '' }}>
                                                {{ $category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('feature_category_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="category" class="form-label">Category <span class="text-danger">*</span></label>
                                    <input type="text" name="category" id="category"
                                        class="form-control @error('category') is-invalid @enderror"
                                        value="{{ old('category') }}"
                                        placeholder="e.g., resources, security, email"
                                        required>
                                    @error('category')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="value_type" class="form-label">Value Type <span class="text-danger">*</span></label>
                                    <select name="value_type" id="value_type"
                                        class="form-control @error('value_type') is-invalid @enderror" required>
                                        <option value="">Select Value Type</option>
                                        <option value="boolean" {{ old('value_type') == 'boolean' ? 'selected' : '' }}>Boolean</option>
                                        <option value="numeric" {{ old('value_type') == 'numeric' ? 'selected' : '' }}>Numeric</option>
                                        <option value="text" {{ old('value_type') == 'text' ? 'selected' : '' }}>Text</option>
                                        <option value="unlimited" {{ old('value_type') == 'unlimited' ? 'selected' : '' }}>Unlimited</option>
                                    </select>
                                    @error('value_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="unit" class="form-label">Unit</label>
                                    <input type="text" name="unit" id="unit"
                                        class="form-control @error('unit') is-invalid @enderror"
                                        value="{{ old('unit') }}"
                                        placeholder="e.g., GB, MB, accounts">
                                    @error('unit')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="icon" class="form-label">Icon</label>
                                    <input type="text" name="icon" id="icon"
                                        class="form-control @error('icon') is-invalid @enderror"
                                        value="{{ old('icon') }}"
                                        placeholder="bi bi-check">
                                    @error('icon')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Bootstrap Icons class (e.g., bi bi-check, bi bi-star)</small>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="sort_order" class="form-label">Sort Order</label>
                                    <input type="number" name="sort_order" id="sort_order"
                                        class="form-control @error('sort_order') is-invalid @enderror"
                                        value="{{ old('sort_order', 0) }}" min="0">
                                    @error('sort_order')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Lower numbers appear first</small>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="is_highlighted" class="form-label">Highlighted</label>
                                    <div class="form-check">
                                        <input type="checkbox" name="is_highlighted" id="is_highlighted" value="1"
                                            class="form-check-input @error('is_highlighted') is-invalid @enderror"
                                            {{ old('is_highlighted') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_highlighted">
                                            Mark as highlighted feature
                                        </label>
                                    </div>
                                    @error('is_highlighted')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea name="description" id="description" rows="3"
                                    class="form-control @error('description') is-invalid @enderror">{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mt-4 d-flex justify-content-end">
                                <a href="{{ route('admin.hosting-features.index') }}"
                                    class="btn btn-secondary mr-2">
                                    <i class="bi bi-dash-circle"></i> Cancel
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-floppy"></i> Create Feature
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>

