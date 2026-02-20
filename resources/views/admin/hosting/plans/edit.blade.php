<x-admin-layout>
    @section('page-title')
        Edit Hosting Plan
    @endsection

    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4>Edit Hosting Plan</h4>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.hosting-plans.update', $plan) }}" method="POST">
                            @csrf
                            @method('PUT')

                            @include('admin.hosting.plans.partials.form-fields', [
                                'plan' => $plan,
                                'categories' => $categories,
                                'statuses' => $statuses,
                            ])

                            <div class="mt-4 d-flex justify-content-end">
                                <a href="{{ route('admin.hosting-plans.index') }}" class="btn btn-secondary mr-2">Cancel</a>
                                <button type="submit" class="btn btn-primary">Update Plan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
