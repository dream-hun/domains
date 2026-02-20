<script>
    $(function () {
        $('.select2bs4').select2({
            theme: 'bootstrap4',
            width: '100%'
        });

        const categorySelect = $('#hosting_category_id');
        const planSelect = $('#hosting_plan_id');

        if (!categorySelect.length || !planSelect.length) {
            return;
        }

        const planSelectElement = planSelect[0];
        const planOptions = Array.from(planSelectElement.options).filter(option => option.value !== '');

        const filterPlans = function (categoryId, preserveSelection = true) {
            const hasCategory = Boolean(categoryId);
            planSelect.prop('disabled', !hasCategory);

            planOptions.forEach(option => {
                const matchesCategory = hasCategory && option.dataset.category === categoryId;
                option.hidden = !matchesCategory;

                if (!matchesCategory && option.selected) {
                    option.selected = false;
                }
            });

            if (!hasCategory) {
                planSelect.val('');
                planOptions.forEach(option => option.hidden = true);
                planSelect.trigger('change.select2');
                return;
            }

            if (!preserveSelection) {
                planSelect.val('');
            }

            // Trigger select2 update to reflect filtered options
            planSelect.trigger('change.select2');
        };

        filterPlans(categorySelect.val() || '', true);

        categorySelect.on('change', function () {
            filterPlans($(this).val(), false);
        });
    });
</script>

