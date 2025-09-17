<x-admin-layout>
    @can('contact_create')
        <div style="margin-bottom: 10px;" class="row">
            <div class="col-lg-12">
                <a class="btn btn-success" href="{{ route('admin.contacts.create') }}">
                    {{ trans('global.add') }} {{ trans('cruds.contact.title_singular') }}
                </a>
            </div>
        </div>
    @endcan
    <div class="card">
        <div class="card-header">
            {{ trans('cruds.contact.title_singular') }} {{ trans('global.list') }}
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class=" table table-bordered table-striped table-hover datatable datatable-Contact">
                    <thead>
                        <tr>

                            <th>
                                {{ trans('cruds.contact.fields.id') }}
                            </th>

                            <th>
                                {{ trans('cruds.contact.fields.full_name') }}
                            </th>
                            <th>
                                Email
                            </th>
                            <th>
                                Mobile
                            </th>
                            <th>
                                {{ trans('cruds.contact.fields.organization') }}
                            </th>
                            <th>
                                {{ trans('cruds.contact.fields.created_at') }}
                            </th>
                            <th>
                                &nbsp;
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($contacts as $key => $contact)
                            <tr data-entry-id="{{ $contact->id }}">

                                <td>
                                    {{ $contact->id ?? '' }}
                                </td>

                                <td>
                                    {{ $contact->getFullNameAttribute() ?? '' }}
                                    @if ($contact->is_primary)
                                        <span class="badge badge-primary ml-2">Primary</span>
                                    @endif
                                </td>
                                <td>
                                    {{ $contact->email ?? '' }}
                                </td>
                                <td>
                                    {{ $contact->phone ?? '' }}
                                </td>
                                <td>
                                    {{ $contact->organization ?? '' }}
                                </td>
                                <td>
                                    {{ $contact->created_at ?? '' }}
                                </td>
                                <td>
                                    @can('contact_edit')
                                        <a class="btn btn-sm btn-primary"
                                            href="{{ route('admin.contacts.edit', $contact) }}">
                                            <i class="bi bi-pencil"></i> {{ trans('global.edit') }}
                                        </a>
                                    @endcan
                                    @can('contact_delete')
                                        <form action="{{ route('admin.contacts.destroy', $contact->uuid) }}" method="POST"
                                            class="d-inline"
                                            onsubmit="return confirm('{{ trans('global.areYouSure') }}');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="bi bi-trash"></i> {{ trans('global.delete') }}
                                            </button>
                                        </form>
                                    @endcan
                                </td>

                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @section('scripts')
        @parent
        <script>
            $(function() {
                let dtButtons = $.extend(true, [], $.fn.dataTable.defaults.buttons)

                let table = $('.datatable-Contact:not(.ajaxTable)').DataTable({
                    buttons: dtButtons
                })
                $('a[data-toggle="tab"]').on('shown.bs.tab click', function(e) {
                    $($.fn.dataTable.tables(true)).DataTable()
                        .columns.adjust();
                });

            })
        </script>
    @endsection


</x-admin-layout>
