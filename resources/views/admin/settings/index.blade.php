<x-admin-layout>
    @can('setting_create')
        <div style="margin-bottom: 10px;" class="row">
            <div class="col-lg-12">
                <a class="btn btn-success" href="{{ route('admin.settings.create') }}">
                    {{ trans('global.add') }} {{ trans('cruds.setting.title_singular') }}
                </a>
            </div>
        </div>
    @endcan
    <div class="card">
        <div class="card-header">
            {{ trans('cruds.setting.title_singular') }} {{ trans('global.list') }}
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class=" table table-bordered table-striped table-hover datatable datatable-Setting">
                    <thead>
                        <tr>

                            <th>
                                {{ trans('cruds.setting.fields.id') }}
                            </th>
                            <th>
                                {{ trans('cruds.setting.fields.email') }}
                            </th>
                            <th>
                                {{ trans('cruds.setting.fields.phone') }}
                            </th>
                            <th>
                                {{ trans('cruds.setting.fields.address') }}
                            </th>

                            <th>
                                &nbsp;
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($settings as $key => $setting)
                            <tr data-entry-id="{{ $setting->id }}">

                                <td>
                                    {{ $setting->id ?? '' }}
                                </td>
                                <td>
                                    {{ $setting->email ?? '' }}
                                </td>
                                <td>
                                    {{ $setting->phone ?? '' }}
                                </td>
                                <td>
                                    {{ $setting->address ?? '' }}
                                </td>

                                <td>
                                    @can('setting_show')
                                        <a class="btn btn-xs btn-primary"
                                            href="{{ route('admin.settings.show', $setting->id) }}">
                                            {{ trans('global.view') }}
                                        </a>
                                    @endcan

                                    @can('setting_edit')
                                        <a class="btn btn-xs btn-info" href="{{ route('admin.settings.edit', $setting->id) }}">
                                            {{ trans('global.edit') }}
                                        </a>
                                    @endcan

                                    @can('setting_delete')
                                        <form action="{{ route('admin.settings.destroy', $setting->id) }}" method="POST"
                                            onsubmit="return confirm('{{ trans('global.areYouSure') }}');"
                                            style="display: inline-block;">
                                            <input type="hidden" name="_method" value="DELETE">
                                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                            <input type="submit" class="btn btn-xs btn-danger"
                                                value="{{ trans('global.delete') }}">
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
                $(function () {
                    let dtButtons = $.extend(true, [], $.fn.dataTable.defaults.buttons)

                    $.extend(true, $.fn.dataTable.defaults, {
                        orderCellsTop: true,
                        order: [
                            [1, 'desc']
                        ],
                        pageLength: 100,
                    });
                    let table = $('.datatable-Setting:not(.ajaxTable)').DataTable({
                        buttons: dtButtons
                    })
                    $('a[data-toggle="tab"]').on('shown.bs.tab click', function (e) {
                        $($.fn.dataTable.tables(true)).DataTable()
                            .columns.adjust();
                    });

                })
            </script>
        @endsection
</x-admin-layout>

