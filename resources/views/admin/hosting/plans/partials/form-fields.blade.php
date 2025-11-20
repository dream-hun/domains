@php
    /** @var \App\Models\HostingPlan|null $plan */
    $plan = $plan ?? null;
@endphp

<div class="row">
    {{-- Category (always first) --}}
    <div class="col-md-6 mb-3">
        <label for="category_id" class="form-label required">Category</label>
        <select name="category_id" id="category_id" class="form-control @error('category_id') is-invalid @enderror" required>
            <option value="">Select Category</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected((int) old('category_id', $plan?->category_id) === $category->id)>
                    {{ $category->name }}
                </option>
            @endforeach
        </select>
        @error('category_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Name --}}
    <div class="col-md-6 mb-3">
        <label for="name" class="form-label required">Name</label>
        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name', $plan?->name) }}" required>
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="row">
    {{-- Slug --}}
    <div class="col-md-6 mb-3">
        <label for="slug" class="form-label required">Slug</label>
        <input type="text" name="slug" id="slug" class="form-control @error('slug') is-invalid @enderror"
               value="{{ old('slug', $plan?->slug) }}" required>
        @error('slug')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <small class="form-text text-muted">URL-friendly identifier (e.g., basic-plan)</small>
    </div>

    {{-- Tagline --}}
    <div class="col-md-6 mb-3">
        <label for="tagline" class="form-label required">Tagline</label>
        <input type="text" name="tagline" id="tagline" class="form-control @error('tagline') is-invalid @enderror"
               value="{{ old('tagline', $plan?->tagline) }}" required>
        @error('tagline')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="row">
    {{-- Sort Order --}}
    <div class="col-md-6 mb-3">
        <label for="sort_order" class="form-label">Sort Order</label>
        <input type="number" name="sort_order" id="sort_order" min="0"
               class="form-control @error('sort_order') is-invalid @enderror"
               value="{{ old('sort_order', $plan?->sort_order) }}">
        @error('sort_order')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <small class="form-text text-muted">Leave empty to append to the end.</small>
    </div>

    {{-- Status --}}
    <div class="col-md-6 mb-3">
        <label for="status" class="form-label required">Status</label>
        <select name="status" id="status" class="form-control @error('status') is-invalid @enderror" required>
            <option value="">Select Status</option>
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}" @selected(old('status', $plan?->status?->value) === $status->value)>
                    {{ $status->label() }}
                </option>
            @endforeach
        </select>
        @error('status')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="row">
    {{-- Is Popular --}}
    <div class="col-md-6 mb-3">
        <label class="form-label required">Popular Plan</label>
        <div class="form-check form-switch">
            <input type="hidden" name="is_popular" value="0">
            <input class="form-check-input @error('is_popular') is-invalid @enderror" type="checkbox" id="is_popular"
                   name="is_popular" value="1" @checked((int) old('is_popular', $plan?->is_popular ? 1 : 0) === 1)>
            <label class="form-check-label" for="is_popular">Highlight this plan as popular</label>
            @error('is_popular')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

{{-- Description --}}
<div class="mb-3">
    <label for="description" class="form-label">Description</label>
    <textarea name="description" id="description" rows="3"
              class="form-control @error('description') is-invalid @enderror">{{ old('description', $plan?->description) }}</textarea>
    @error('description')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
