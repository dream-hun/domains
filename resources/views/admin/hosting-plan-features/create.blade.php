<x-admin-layout>
    @section('page-title')
        Create Hosting Plan Feature
    @endsection

    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Create Hosting Plan Feature</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.hosting-plan-features.index') }}">Hosting Plan Features</a></li>
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
                        <h4>Create Hosting Plan Feature</h4>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.hosting-plan-features.store') }}" method="POST">
                            @csrf

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="hosting_category_id" class="form-label required">Hosting Category <span class="text-danger">*</span></label>
                                    <select name="hosting_category_id" id="hosting_category_id"
                                        class="form-control @error('hosting_category_id') is-invalid @enderror" required>
                                        <option value="">Select Hosting Category</option>
                                        @foreach ($hostingCategories as $category)
                                            <option value="{{ $category->id }}"
                                                {{ old('hosting_category_id') == $category->id ? 'selected' : '' }}>
                                                {{ $category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('hosting_category_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="hosting_plan_id" class="form-label required">Hosting Plan <span class="text-danger">*</span></label>
                                    <select name="hosting_plan_id" id="hosting_plan_id"
                                        class="form-control @error('hosting_plan_id') is-invalid @enderror" required>
                                        <option value="">Select Hosting Plan</option>
                                        @foreach ($hostingPlans as $plan)
                                            <option value="{{ $plan->id }}"
                                                data-category-id="{{ $plan->category_id }}"
                                                {{ old('hosting_plan_id') == $plan->id ? 'selected' : '' }}>
                                                {{ $plan->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('hosting_plan_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="hosting_feature_id" class="form-label required">Hosting Feature <span class="text-danger">*</span></label>
                                    <select name="hosting_feature_id" id="hosting_feature_id"
                                        class="form-control @error('hosting_feature_id') is-invalid @enderror" required>
                                        <option value="">Select Hosting Feature</option>
                                        @foreach ($hostingFeatures as $feature)
                                            <option value="{{ $feature->id }}"
                                                {{ old('hosting_feature_id') == $feature->id ? 'selected' : '' }}>
                                                {{ $feature->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('hosting_feature_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="feature_value" class="form-label">Feature Value</label>
                                    <input type="text" name="feature_value" id="feature_value"
                                        class="form-control @error('feature_value') is-invalid @enderror"
                                        value="{{ old('feature_value') }}"
                                        placeholder="e.g., 3, Unmetered, true">
                                    @error('feature_value')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">The value of the feature (e.g., "3", "Unmetered", "true")</small>
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
                                    <label for="is_unlimited" class="form-label">Is Unlimited</label>
                                    <div class="form-check">
                                        <input type="checkbox" name="is_unlimited" id="is_unlimited" value="1"
                                            class="form-check-input @error('is_unlimited') is-invalid @enderror"
                                            {{ old('is_unlimited') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_unlimited">
                                            Mark as unlimited
                                        </label>
                                    </div>
                                    @error('is_unlimited')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="is_included" class="form-label">Is Included</label>
                                    <div class="form-check">
                                        <input type="checkbox" name="is_included" id="is_included" value="1"
                                            class="form-check-input @error('is_included') is-invalid @enderror"
                                            {{ old('is_included', true) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_included">
                                            Feature is included in the plan
                                        </label>
                                    </div>
                                    @error('is_included')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="custom_text" class="form-label">Custom Text</label>
                                <textarea name="custom_text" id="custom_text" rows="3"
                                    class="form-control @error('custom_text') is-invalid @enderror"
                                    placeholder="Override display text">{{ old('custom_text') }}</textarea>
                                @error('custom_text')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">Override the default display text for this feature</small>
                            </div>

                            <div class="mt-4 d-flex justify-content-end">
                                <a href="{{ route('admin.hosting-plan-features.index') }}"
                                    class="btn btn-secondary mr-2">
                                    <i class="bi bi-dash-circle"></i> Cancel
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-floppy"></i> Create Plan Feature
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @section('scripts')
        @parent
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const categorySelect = document.getElementById('hosting_category_id')
                const planSelect = document.getElementById('hosting_plan_id')

                const filterPlanOptions = () => {
                    if (!planSelect) {
                        return
                    }

                    const selectedCategory = categorySelect?.value ?? ''

                    planSelect.querySelectorAll('option[data-category-id]').forEach((option) => {
                        const matches = !selectedCategory || option.dataset.categoryId === selectedCategory
                        option.hidden = !matches
                        option.disabled = !matches
                    })

                    if (selectedCategory) {
                        const selectedOption = planSelect.options[planSelect.selectedIndex]
                        if (selectedOption && (selectedOption.hidden || selectedOption.disabled)) {
                            planSelect.value = ''
                        }
                    }
                }

                if (categorySelect && planSelect) {
                    filterPlanOptions()
                    categorySelect.addEventListener('change', filterPlanOptions)
                }
            })
        </script>
    @endsection
</x-admin-layout>

