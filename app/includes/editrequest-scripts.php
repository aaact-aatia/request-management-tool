<script src="/public/js/file-manager.js"></script>
<script src="/public/js/ajax-dropdowns.js"></script>
<script>
// Sprint date validation (editrequest-specific)
document.addEventListener('DOMContentLoaded', function () {
    const focusTarget = '<?php echo htmlspecialchars((string) ($focusTarget ?? ''), ENT_QUOTES, 'UTF-8'); ?>';
    const focusMap = {
        update: 'update-status-message',
        upload: 'upload-status-message',
        log: 'log-status-message'
    };

    const targetId = focusMap[focusTarget] || '';
    if (targetId) {
        const target = document.getElementById(targetId);
        if (target) {
            target.focus();
        }
    }

    const startDateInput = document.getElementById('firstsprintstartdate');
    const endDateInput = document.getElementById('firstsprintenddate');
    if (startDateInput && endDateInput) {
        startDateInput.addEventListener('change', function () {
            endDateInput.min = this.value;
        });
    }

    const departmentInput = document.getElementById('departmentagency');
    const departmentOptions = document.getElementById('departmentagency-options');
    const departmentReview = document.getElementById('departmentagency-review');
    if (departmentInput && departmentOptions && departmentReview && !departmentInput.readOnly) {
        const recognizedDepartments = new Set();
        Array.from(departmentOptions.options).forEach(function (option) {
            [option.value, option.dataset.name, option.dataset.abbreviation].forEach(function (value) {
                if (value && value.trim() !== '') {
                    recognizedDepartments.add(value.trim().toLocaleLowerCase());
                }
            });
        });

        const updateDepartmentReview = function () {
            const value = departmentInput.value.trim().toLocaleLowerCase();
            const needsReview = value !== '' && !recognizedDepartments.has(value);
            departmentReview.hidden = !needsReview;

            const describedBy = new Set((departmentInput.getAttribute('aria-describedby') || '').split(/\s+/).filter(Boolean));
            if (needsReview) {
                describedBy.add('departmentagency-review');
            } else {
                describedBy.delete('departmentagency-review');
            }
            departmentInput.setAttribute('aria-describedby', Array.from(describedBy).join(' '));
        };

        departmentInput.addEventListener('input', updateDepartmentReview);
        departmentInput.addEventListener('change', updateDepartmentReview);
    }
});
</script>

