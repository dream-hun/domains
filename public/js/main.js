$(document).ready(function () {
    window._token = $('meta[name="csrf-token"]').attr('content')

    moment.updateLocale('en', {
        week: {dow: 1} // Monday is the first day of the week
    })

    $('.date').datetimepicker({
        format: 'YYYY-MM-DD',
        locale: 'en',
        icons: {
            up: 'fas fa-chevron-up',
            down: 'fas fa-chevron-down',
            previous: 'fas fa-chevron-left',
            next: 'fas fa-chevron-right'
        }
    })

    $('.datetime').datetimepicker({
        format: 'YYYY-MM-DD HH:mm:ss',
        locale: 'en',
        sideBySide: true,
        icons: {
            up: 'fas fa-chevron-up',
            down: 'fas fa-chevron-down',
            previous: 'fas fa-chevron-left',
            next: 'fas fa-chevron-right'
        }
    })

    $('.timepicker').datetimepicker({
        format: 'HH:mm:ss',
        icons: {
            up: 'fas fa-chevron-up',
            down: 'fas fa-chevron-down',
            previous: 'fas fa-chevron-left',
            next: 'fas fa-chevron-right'
        }
    })

    $('.select-all').click(function () {
        let $select = $(this).closest('.form-group').find('select')
        let allValues = $select.find('option').map(function () { return this.value }).get()
        $select.val(allValues).trigger('change')
    })
    $('.deselect-all').click(function () {
        let $select = $(this).closest('.form-group').find('select')
        $select.val([]).trigger('change')
    })

    $('.select2').select2()

    $('.treeview').each(function () {
        var shouldExpand = false
        $(this).find('li').each(function () {
            if ($(this).hasClass('active')) {
                shouldExpand = true
            }
        })
        if (shouldExpand) {
            $(this).addClass('active')
        }
    })

    $('a[data-widget^="pushmenu"]').click(function () {
        setTimeout(function() {
            $($.fn.dataTable.tables(true)).DataTable().columns.adjust();
        }, 350);
    })
})
