<x-admin-layout>
    @section('page-title')
        Hosting Plan Features
    @endsection

    <section class="content-header">
        <div class="container-fluid col-md-12">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Hosting Plan Features</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Hosting Plan Features</li>
                    </ol>
                </div>
            </div>
        </div>
        <div class="row mb-2 push-right">
            <div class="col-lg-12">
                @can('hosting_plan_feature_create')
                <a class="btn btn-success push-right" href="{{ route('admin.hosting-plan-features.create') }}">
                        <i class="bi bi-plus-circle"></i> Create Plan Feature
                    </a>
                @endcan
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Hosting Plan Features</h3>
            </div>

            <div class="card-body">
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                        <h5><i class="icon fas fa-check"></i> Success!</h5>
                        {{ session('success') }}
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                        <h5><i class="icon fas fa-ban"></i> Error!</h5>
                        {{ session('error') }}
                    </div>
                @endif

                <form action="{{ route('admin.hosting-plan-features.index') }}" method="GET" class="mb-4" id="hosting-plan-feature-filters">
                    <div class="form-row">
                        <div class="col-md-4 mb-3">
                            <label for="filter_hosting_category_id" class="form-label">Hosting Category</label>
                            <select name="hosting_category_id" id="filter_hosting_category_id" class="form-control">
                                <option value="">All categories</option>
                                @foreach ($hostingCategories as $category)
                                    <option value="{{ $category->id }}"
                                        {{ (string) ($filters['hosting_category_id'] ?? '') === (string) $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="filter_hosting_plan_id" class="form-label">Hosting Plan</label>
                            <select name="hosting_plan_id" id="filter_hosting_plan_id" class="form-control">
                                <option value="">All plans</option>
                                @foreach ($hostingPlans as $plan)
                                    <option value="{{ $plan->id }}"
                                        data-category-id="{{ $plan->category_id }}"
                                        {{ (string) ($filters['hosting_plan_id'] ?? '') === (string) $plan->id ? 'selected' : '' }}>
                                        {{ $plan->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 mb-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary mr-2">
                                <i class="bi bi-funnel"></i> Apply Filters
                            </button>
                            @if (($filters['hosting_category_id'] ?? null) || ($filters['hosting_plan_id'] ?? null))
                                <a href="{{ route('admin.hosting-plan-features.index') }}" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                                </a>
                            @endif
                        </div>
                    </div>
                </form>

                @if ($hostingPlanFeatures->isEmpty())
                    <div class="alert alert-info">
                        <h5><i class="icon fas fa-info"></i> Info!</h5>
                        No hosting plan features found.
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover datatable-HostingPlanFeature w-100">
                            <thead>
                                <tr>
                                    <th style="width: 15%">Hosting Category</th>
                                    <th style="width: 18%">Hosting Plan</th>
                                    <th style="width: 18%">Hosting Feature</th>
                                    <th style="width: 10%">Feature Value</th>
                                    <th style="width: 8%">Unlimited</th>
                                    <th style="width: 12%">Custom Text</th>
                                    <th style="width: 8%">Included</th>
                                    <th style="width: 6%">Sort Order</th>
                                    <th style="width: 15%">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($hostingPlanFeatures as $planFeature)
                                    <tr>
                                        <td>{{ $planFeature->hostingPlan?->category?->name ?? '-' }}</td>
                                        <td><strong>{{ $planFeature->hostingPlan?->name ?? '-' }}</strong></td>
                                        <td>{{ $planFeature->hostingFeature?->name ?? '-' }}</td>
                                        <td>{{ $planFeature->feature_value ?? '-' }}</td>
                                        <td>
                                            @if ($planFeature->is_unlimited)
                                                <span class="badge badge-success">Yes</span>
                                            @else
                                                <span class="badge badge-secondary">No</span>
                                            @endif
                                        </td>
                                        <td>{{ $planFeature->custom_text ?? '-' }}</td>
                                        <td>
                                            @if ($planFeature->is_included)
                                                <span class="badge badge-success">Yes</span>
                                            @else
                                                <span class="badge badge-warning">No</span>
                                            @endif
                                        </td>
                                        <td>{{ $planFeature->sort_order ?? 0 }}</td>
                                        <td>
                                            <div class="btn-group">
                                                @can('hosting_plan_feature_edit')
                                                <a class="btn btn-sm btn-warning"
                                                    href="{{ route('admin.hosting-plan-features.edit', $planFeature) }}"
                                                    title="Edit">
                                                    <i class="bi bi-pencil"></i> Edit
                                                </a>
                                                @endcan
                                                @can('hosting_plan_feature_delete')
                                                <form action="{{ route('admin.hosting-plan-features.destroy', $planFeature) }}" method="POST"
                                                    class="d-inline"
                                                    onsubmit="return confirm('Are you sure you want to delete this plan feature? This action cannot be undone.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                                        <i class="bi bi-trash"></i> Delete
                                                    </button>
                                                </form>
                                                @endcan
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
    </section>

    @section('styles')
        @parent
        <style>
            .datatable-HostingPlanFeature {
                width: 100% !important;
                table-layout: fixed;
            }
        </style>
    @endsection

    @section('scripts')
        @parent
        <script>
            $(function() {
                let dtButtons = $.extend(true, [], $.fn.dataTable.defaults.buttons)
                let table = $('.datatable-HostingPlanFeature:not(.ajaxTable)').DataTable({
                    buttons: dtButtons,
                    paging: true,
                    searching: true,
                    ordering: true,
                    info: true,
                    lengthChange: true,
                    dom: 'Bfrtip',
                    autoWidth: false,
                    order: [[7, 'asc']],
                    pageLength: 25,
                    language: {
                        search: "Search:",
                        searchPlaceholder: "Search plan features..."
                    }
                })
            })

            document.addEventListener('DOMContentLoaded', function () {
                const filterForm = document.getElementById('hosting-plan-feature-filters')
                const categorySelect = document.getElementById('filter_hosting_category_id')
                const planSelect = document.getElementById('filter_hosting_plan_id')

                const filterPlanOptions = () => {
                    if (!planSelect) {
                        return
                    }

                    const selectedCategory = categorySelect?.value ?? ''

                    planSelect.querySelectorAll('option[data-category-id]').forEach((option) => {
                        const matchesCategory = !selectedCategory || option.dataset.categoryId === selectedCategory
                        option.hidden = !matchesCategory
                        option.disabled = !matchesCategory
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

                    categorySelect.addEventListener('change', () => {
                        filterPlanOptions()
                        filterForm?.submit()
                    })

                    planSelect.addEventListener('change', () => filterForm?.submit())
                }
            })
        </script>
    @endsection
</x-admin-layout>

