<script>
// ============================================================================
// FILE HANDLING SCRIPTS
// ============================================================================

// Select All checkbox functionality
document.getElementById('selectAll').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.fileCheckbox');
    checkboxes.forEach(checkbox => checkbox.checked = this.checked);
});

// Download All button
document.getElementById('downloadAll').addEventListener('click', function() {
    const selectedCheckboxes = document.querySelectorAll('.fileCheckbox:checked');
    if (selectedCheckboxes.length === 0) {
        alert('Please select at least one file to download.');
        return;
    }
    
    selectedCheckboxes.forEach(checkbox => {
        const button = checkbox.closest('tr').querySelector('.download-btn');
        if (button) {
            const fileCode = button.getAttribute('data-file');
            const fileName = button.getAttribute('data-name');
            downloadFile(fileCode, fileName);
        }
    });
});

// Download individual file button
document.querySelectorAll('.download-btn').forEach(button => {
    button.addEventListener('click', function(e) {
        e.preventDefault();
        const fileCode = this.getAttribute('data-file');
        const fileName = this.getAttribute('data-name');
        downloadFile(fileCode, fileName);
    });
});

// Image preview links
document.querySelectorAll('.image-link').forEach(link => {
    link.addEventListener('click', function(e) {
        e.preventDefault();
        const imagePreview = document.getElementById('imagePreview');
        const previewImage = document.getElementById('previewImage');
        const imageAnnouncement = document.getElementById('imageAnnouncement');
        
        previewImage.src = this.dataset.src;
        imagePreview.style.display = 'flex';
        imagePreview.setAttribute('aria-hidden', 'false');
        
        // Announce to screen readers
        imageAnnouncement.textContent = "Image preview opened.";
        
        // Focus the close button for better keyboard navigation
        document.getElementById('closePreview').focus();
    });
});

// Close image preview
function closePreview() {
    const imagePreview = document.getElementById('imagePreview');
    if (!imagePreview) {
        return;
    }

    imagePreview.style.display = 'none';
    imagePreview.setAttribute('aria-hidden', 'true');

    const previewImage = document.getElementById('previewImage');
    if (previewImage) {
        previewImage.src = '';
    }
    
    // Clear announcement text
    const imageAnnouncement = document.getElementById('imageAnnouncement');
    if (imageAnnouncement) {
        imageAnnouncement.textContent = "";
    }
}

// Download file function
async function downloadFile(code, name) {
    try {
        const response = await fetch(`download.php?code=${encodeURIComponent(code)}`);
        
        if (!response.ok) {
            alert('File not found!');
            return;
        }
        
        const blob = await response.blob();
        const sanitizedFileName = sanitizeFileName(name);
        
        // Create a download link
        const downloadLink = document.createElement('a');
        downloadLink.href = URL.createObjectURL(blob);
        downloadLink.download = sanitizedFileName;
        
        // Append the link to the DOM, trigger the click, and remove it
        document.body.appendChild(downloadLink);
        downloadLink.click();
        document.body.removeChild(downloadLink);
        
        // Revoke the object URL to free up resources
        URL.revokeObjectURL(downloadLink.href);
    } catch (error) {
        console.error('Error downloading the file:', error);
        alert('Failed to download file.');
    }
}

// Sanitize filename
function sanitizeFileName(fileName) {
    return fileName.replace(/[^\w.-]/g, '_');
}

// Close preview when clicking on overlay or button
document.getElementById('imagePreview')?.addEventListener('click', closePreview);
document.getElementById('closePreview')?.addEventListener('click', closePreview);

// Close on Escape key press
document.addEventListener('keydown', function(event) {
    if (event.key === "Escape") {
        closePreview();
    }
});

// Delete file function
function deleteFile(fileCode) {
    if (confirm("Are you sure you want to delete this file?")) {
        fetch(`delete-file.php?code=${fileCode}`, {
            method: 'GET',
        })
        .then(response => response.text())
        .then(data => {
            console.log(data);
            alert("File deleted successfully!");
            location.reload();
        })
        .catch(error => {
            console.error('Error deleting file:', error);
            alert('Failed to delete file.');
        });
    }
}

// Add event listener to all delete buttons
document.querySelectorAll('.delete-btn').forEach(button => {
    button.addEventListener('click', function() {
        const fileCode = this.getAttribute('data-file');
        deleteFile(fileCode);
    });
});

// ============================================================================
// AJAX FOR CATALOGUE/SERVICE DROPDOWNS
// ============================================================================

function ajax1(val1) {
    $.ajax({
        url: "addrequest-ajax1.php?v1=" + val1,
        success: function(result) {
            $(".divservice").html(result);
        }
    });
    $(".divsubservice").hide();
}

function ajax2(val1) {
    $.ajax({
        url: "addrequest-ajax2.php?v1=" + val1,
        success: function(result) {
            $(".divsubservice").html(result);
        }
    });
    $(".divsubservice").show();
}

// ============================================================================
// SPRINT DATE VALIDATION
// ============================================================================

document.addEventListener('DOMContentLoaded', function() {
    const startDateInput = document.getElementById('firstsprintstartdate');
    const endDateInput = document.getElementById('firstsprintenddate');
    
    if (startDateInput && endDateInput) {
        startDateInput.addEventListener('change', function() {
            const startDateValue = this.value;
            endDateInput.min = startDateValue;
        });
    }
});
</script>
