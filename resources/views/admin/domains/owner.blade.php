<x-admin-layout>
    @section('page-title')
        Domain Management
    @endsection
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Assign New Owner to Domain</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.domains.index') }}">Domains</a></li>
                        <li class="breadcrumb-item active">{{ $domain->name }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>
    <section class="content">
        <div class="container-fluid">
            
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Assign New Owner to Domain</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.domains.assign.store', $domain->uuid) }}" method="POST">
                        @csrf
                        <p>You can transfer your domain to another Bluhub account by selecting the owner from the list below. This will send an email to the recipient with instructions on how to accept the domain transfer.</p>
                        

                        <div class="form-group mb-3">
                            <label>Domain Name</label>
                            <input type="text" class="form-control" value="{{ $domain->name ?? '' }}" disabled>
                        </div>
                        <div class="form-group mb-3">
                            <label>Status</label>
                            <input type="text" class="form-control" value="{{ $domain->status ?? '' }}" disabled>
                        </div>
                        <div class="form-group mb-3">
                            <label>Expiry Date</label>
                            <input type="text" class="form-control" value="{{ $domain->expiresAt() ?? '' }}" disabled>
                        </div>
                        <div class="form-group">
                            <label for="owner_id">Owner</label>
                            <select name="owner_id" id="owner_id" class="form-control select2bs4">
                                @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Assign</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

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
</x-admin-layout>