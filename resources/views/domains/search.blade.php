<x-user-layout>
    @section('page-title')
        Register Domain
    @endsection

    <livewire:domain-search-page :domain="$domain ?? null" />
</x-user-layout>
