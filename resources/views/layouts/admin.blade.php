<!DOCTYPE html>
<html>

<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta charset="UTF-8">
    <title>@yield('page-title') - {{config('app.name')}}</title>
    <link rel="stylesheet" href="{{asset('css/adminlte.min.css')}}">
    <link href="{{asset('plugins/select2/css/select2.min.css')}}" rel="stylesheet"/>
    <link href="{{asset('plugins/datatables-bs4/css/dataTables.bootstrap4.min.css')}}" rel="stylesheet"/>
    <link href="{{asset('plugins/datatables-responsive/css/responsive.bootstrap4.min.css')}}" rel="stylesheet">
    <link href="{{asset('plugins/datatables-buttons/css/buttons.bootstrap4.min.css')}}" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datetimepicker/4.17.47/css/bootstrap-datetimepicker.min.css"
          rel="stylesheet"/>
    <link href="{{ asset('font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/custom.css') }}" rel="stylesheet"/>
    <link rel="stylesheet" href="https://rsms.me/inter/inter.css">

    <style>
        body {
            font-family: "Inter", sans-serif !important;
        }

        [x-cloak] {
            display: none !important;
        }
    </style>


    <script>
        window.fetchContactDetails = async function (contactId) {
            if (!contactId) {
                return null;
            }
            try {
                const response = await fetch(`/contacts/${contactId}/details`, {
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                const data = await response.json();
                if (data.success) {
                    return data.contact;
                } else {
                    console.error('Failed to fetch contact details:', data.message);
                    return null;
                }
            } catch (error) {
                console.error('Error fetching contact details:', error);
                return null;
            }
        };
    </script>
    @livewireStyles
    @yield('styles')

</head>

<body class="hold-transition sidebar-mini">
<div class="wrapper">
    <livewire:navbar-component/>
    @include('partials.menu')
    <div class="content-wrapper" style="min-height: 818px;">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1>@yield('page-title', 'Dashboard')</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            @yield('breadcrumb', '')
                        </ol>
                    </div>
                </div>
            </div><!-- /.container-fluid -->
        </section>
        <section class="content">
            @if(session('message'))
                <div class="row mb-2">
                    <div class="col-lg-12">
                        <div class="alert alert-info" role="alert">{{ session('message') }}</div>
                    </div>
                </div>
            @endif
            @if(session('success'))
                <div class="row mb-2">
                    <div class="col-lg-12">
                        <div class="alert alert-success" role="alert">{{ session('success') }}</div>
                    </div>
                </div>
            @endif
            @if(session('warning'))
                <div class="row mb-2">
                    <div class="col-lg-12">
                        <div class="alert alert-warning" role="alert">{{ session('warning') }}</div>
                    </div>
                </div>
            @endif
            @if($errors->count() > 0)
                <div class="alert alert-danger">
                    <ul class="list-unstyled">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            {{$slot}}
        </section>
        <!-- /.content -->
    </div>
    <!-- /.content-wrapper -->

    <!-- Footer -->
    @include('partials.footer')
    <!-- /.footer -->
</div>
<!-- ./wrapper -->

<!-- Scripts -->

<script src="{{asset('plugins/jquery/jquery.min.js')}}"></script>
<script src="https://unpkg.com/@popperjs/core@2"></script>
<script src="{{asset('plugins/select2/js/select2.min.js')}}"></script>
<script src="{{asset('plugins/moment/moment.min.js')}}"></script>
<script src="{{ asset('plugins/datatables/jquery.dataTables.js') }}"></script>
<script src="{{ asset('plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
<script src="{{ asset('plugins/datatables-buttons/js/dataTables.buttons.min.js') }}"></script>
<script src="{{ asset('plugins/datatables-select/js/dataTables.select.min.js') }}"></script>
<script src="{{ asset('plugins/datatables-buttons/js/buttons.flash.min.js') }}"></script>
<script src="{{ asset('plugins/datatables-buttons/js/buttons.html5.min.js') }}"></script>
<script src="{{ asset('plugins/datatables-buttons/js/buttons.print.min.js') }}"></script>
<script src="{{ asset('plugins/datatables-buttons/js/buttons.colVis.min.js') }}"></script>
<script src="{{ asset('plugins/pdfmake/pdfmake.min.js') }}"></script>
<script src="{{ asset('plugins/pdfmake/vfs_fonts.js') }}"></script>
<script src="{{ asset('plugins/jszip/jszip.min.js') }}"></script>
<script
    src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datetimepicker/4.17.47/js/bootstrap-datetimepicker.min.js"></script>
<script src="{{asset('js/adminlte.min.js')}}"></script>
<script src="{{ asset('js/main.js') }}"></script>
<script>
    $(function () {
        let copyButtonTrans = '{{ trans('global.datatables.copy') }}'
        let csvButtonTrans = '{{ trans('global.datatables.csv') }}'
        let excelButtonTrans = '{{ trans('global.datatables.excel') }}'
        let pdfButtonTrans = '{{ trans('global.datatables.pdf') }}'
        let printButtonTrans = '{{ trans('global.datatables.print') }}'
        let colvisButtonTrans = '{{ trans('global.datatables.colvis') }}'
        let selectAllButtonTrans = '{{ trans('global.select_all') }}'
        let selectNoneButtonTrans = '{{ trans('global.deselect_all') }}'

        let languages = {
            'en': 'https://cdn.datatables.net/plug-ins/1.10.19/i18n/English.json'
        };

        $.extend(true, $.fn.dataTable.Buttons.defaults.dom.button, {className: 'btn'})
        $.extend(true, $.fn.dataTable.defaults, {
            language: {
                url: languages['{{ app()->getLocale() }}']
            },
            columnDefs: [{
                orderable: false,
                className: 'select-checkbox',
                targets: 0
            }, {
                orderable: false,
                searchable: false,
                targets: -1
            }],
            select: {
                style: 'multi+shift',
                selector: 'td:first-child'
            },
            order: [],
            scrollX: true,
            pageLength: 100,
            dom: 'lBfrtip<"actions">',
            buttons: [
                {
                    extend: 'selectAll',
                    className: 'btn-primary',
                    text: selectAllButtonTrans,
                    exportOptions: {
                        columns: ':visible'
                    },
                    action: function (e, dt) {
                        e.preventDefault()
                        dt.rows().deselect();
                        dt.rows({search: 'applied'}).select();
                    }
                },
                {
                    extend: 'selectNone',
                    className: 'btn-primary',
                    text: selectNoneButtonTrans,
                    exportOptions: {
                        columns: ':visible'
                    }
                },
                {
                    extend: 'copy',
                    className: 'btn-default',
                    text: copyButtonTrans,
                    exportOptions: {
                        columns: ':visible'
                    }
                },
                {
                    extend: 'csv',
                    className: 'btn-default',
                    text: csvButtonTrans,
                    exportOptions: {
                        columns: ':visible'
                    }
                },
                {
                    extend: 'excel',
                    className: 'btn-default',
                    text: excelButtonTrans,
                    exportOptions: {
                        columns: ':visible'
                    }
                },
                {
                    extend: 'pdf',
                    className: 'btn-default',
                    text: pdfButtonTrans,
                    exportOptions: {
                        columns: ':visible'
                    }
                },
                {
                    extend: 'print',
                    className: 'btn-default',
                    text: printButtonTrans,
                    exportOptions: {
                        columns: ':visible'
                    }
                },
                {
                    extend: 'colvis',
                    className: 'btn-default',
                    text: colvisButtonTrans,
                    exportOptions: {
                        columns: ':visible'
                    }
                }
            ]
        });

        $.fn.dataTable.ext.classes.sPageButton = '';
    });

</script>

@livewireScripts
@yield('scripts')
</body>

</html>
