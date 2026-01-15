<x-admin-layout>
    @section('page-title')
        Profile
    @endsection
    @section('breadcrumb')
        <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item active">Dashboard</li>
        </ol>
    @endsection

    <div class="row">
        <div class="col-md-6">
            @include('profile.partials.update-profile-information-form')
        </div>

        <div class="col-md-6">
            @include('profile.partials.update-password-form')
        </div>
        <div class="col-md-6">
            @include('profile.partials.billing-info')
        </div>
    </div>

</x-admin-layout>
