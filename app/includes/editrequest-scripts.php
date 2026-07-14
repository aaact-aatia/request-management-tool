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
});
</script>

