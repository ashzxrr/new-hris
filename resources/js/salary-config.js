const form = document.getElementById('salaryConfigForm');

if (form) {
    const selectAll = document.getElementById('selectAllEmployees');
    const selectedCount = document.getElementById('selectedCount');
    const saveButton = document.getElementById('saveSalaryConfigs');

    const updateSelection = () => {
        const checkboxes = Array.from(form.querySelectorAll('.employee-checkbox'));
        const selected = checkboxes.filter((checkbox) => checkbox.checked);

        checkboxes.forEach((checkbox) => {
            const row = checkbox.closest('.salary-config-row');
            row.querySelectorAll('.salary-entry-field').forEach((field) => {
                field.disabled = !checkbox.checked;
            });
        });

        selectedCount.textContent = `${selected.length} dipilih`;
        saveButton.disabled = selected.length === 0;
        selectAll.checked = checkboxes.length > 0 && selected.length === checkboxes.length;
        selectAll.indeterminate = selected.length > 0 && selected.length < checkboxes.length;
    };

    selectAll.addEventListener('change', () => {
        form.querySelectorAll('.employee-checkbox').forEach((checkbox) => {
            checkbox.checked = selectAll.checked;
        });
        updateSelection();
    });

    form.querySelectorAll('.employee-checkbox').forEach((checkbox) => {
        checkbox.addEventListener('change', updateSelection);
    });

    form.querySelectorAll('.salary-config-row').forEach((row) => {
        row.addEventListener('click', (event) => {
            if (event.target.closest('input, button, select, textarea, a, label')) {
                return;
            }

            const checkbox = row.querySelector('.employee-checkbox');
            if (checkbox) {
                checkbox.checked = !checkbox.checked;
                updateSelection();
            }
        });
    });

    updateSelection();
}
