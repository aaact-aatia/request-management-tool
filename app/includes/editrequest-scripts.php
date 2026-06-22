<script src="/public/js/file-manager.js"></script>
<script src="/public/js/ajax-dropdowns.js"></script>
<script>
// Sprint date validation (editrequest-specific)
document.addEventListener('DOMContentLoaded', function () {
    const startDateInput = document.getElementById('firstsprintstartdate');
    const endDateInput = document.getElementById('firstsprintenddate');
    if (startDateInput && endDateInput) {
        startDateInput.addEventListener('change', function () {
            endDateInput.min = this.value;
        });
    }
});
</script>

