<x-admin-layout>
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Transfer Domain</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('admin.domains.index') }}">Domains</a></li>
                        <li class="breadcrumb-item">Transfer</li>
                        <li class="breadcrumb-item active">{{$domain->name}}</li>

                    </ol>
                </div>
            </div>
        </div>
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <form method="POST" action="{{ route('admin.domains.transfer.store',$domain->uuid) }}">
                            @csrf
                            <div class="card-body">

                                <h5>Transfer Your Domain to Our Registrar</h5>
                                <small class="form-text text-muted mt-4">
                                    This feature allows you to transfer your domain name to another Bluhub account.It
                                    makes
                                    managing multiple domains under a single account or transferring ownership easy
                                    without
                                    losing the domain's settings. This feature will not move your website files or
                                    databases
                                    to another account.
                                </small>

                                <div class="form-group">
                                    <label for="auth_code">Enter Email Account <span
                                            class="text-danger">*</span></label>
                                    <input type="email" class="form-control @error('email') is-invalid @enderror"
                                           id="email" name="email" value="{{ old('email') }}"
                                           placeholder="Enter email" required>

                                    @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>


                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary"><i class="bi bi-send-check"></i> Initiate Transfer</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-admin-layout>
