<script>
    document.addEventListener('DOMContentLoaded', function () {
        const categorySelect = document.getElementById('hosting_category_id');
        const planSelect = document.getElementById('hosting_plan_id');

        if (!categorySelect || !planSelect) {
            return;
        }

        const planOptions = Array.from(planSelect.options).filter(option => option.value !== '');

        const filterPlans = function (categoryId, preserveSelection = true) {
            const hasCategory = Boolean(categoryId);
            planSelect.disabled = !hasCategory;

            planOptions.forEach(option => {
                const matchesCategory = hasCategory && option.dataset.category === categoryId;
                option.hidden = !matchesCategory;

                if (!matchesCategory && option.selected) {
                    option.selected = false;
                }
            });

            if (!hasCategory) {
                planSelect.value = '';
                planOptions.forEach(option => option.hidden = true);
                return;
            }

            if (!preserveSelection) {
                planSelect.value = '';
            }
        };

        filterPlans(categorySelect.value || '', true);

        categorySelect.addEventListener('change', function (event) {
            filterPlans(event.target.value, false);
        });
    });
</script>

