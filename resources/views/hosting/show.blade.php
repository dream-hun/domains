<x-user-layout>
    @section('page-title')
        {{ $category->name }}
    @endsection
    @section('page-description')
        {{ $category->description }}
    @endsection
    <livewire:hosting.category-show :category="$category" :plans="$plans" />
</x-user-layout>
