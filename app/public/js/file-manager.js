/**
 * File manager: download, preview, and delete uploaded files.
 * Used by: viewrequest.php, editrequest.php (via includes/editrequest-scripts.php)
 */

// ============================================================================
// FILE SELECTION
// ============================================================================

document.getElementById('selectAll').addEventListener('change', function () {
	const checkboxes = document.querySelectorAll('.fileCheckbox');
	checkboxes.forEach(function (checkbox) { checkbox.checked = this.checked; }, this);
});

// ============================================================================
// DOWNLOAD
// ============================================================================

document.getElementById('downloadAll').addEventListener('click', function () {
	const selectedCheckboxes = document.querySelectorAll('.fileCheckbox:checked');
	if (selectedCheckboxes.length === 0) {
		alert('Please select at least one file to download.');
		return;
	}
	selectedCheckboxes.forEach(function (checkbox) {
		const button = checkbox.closest('tr').querySelector('.download-btn');
		if (button) {
			downloadFile(button.getAttribute('data-file'), button.getAttribute('data-name'));
		}
	});
});

document.querySelectorAll('.download-btn').forEach(function (button) {
	button.addEventListener('click', function (e) {
		e.preventDefault();
		downloadFile(this.getAttribute('data-file'), this.getAttribute('data-name'));
	});
});

async function downloadFile(code, name) {
	try {
		const response = await fetch('download.php?code=' + encodeURIComponent(code));
		if (!response.ok) {
			alert('File not found!');
			return;
		}
		const blob = await response.blob();
		const sanitizedFileName = sanitizeFileName(name);
		const downloadLink = document.createElement('a');
		downloadLink.href = URL.createObjectURL(blob);
		downloadLink.download = sanitizedFileName;
		document.body.appendChild(downloadLink);
		downloadLink.click();
		document.body.removeChild(downloadLink);
		URL.revokeObjectURL(downloadLink.href);
	} catch (error) {
		console.error('Error downloading the file:', error);
		alert('Failed to download file.');
	}
}

function sanitizeFileName(fileName) {
	return fileName.replace(/[^\w.-]/g, '_');
}

// ============================================================================
// IMAGE PREVIEW
// ============================================================================

document.querySelectorAll('.image-link').forEach(function (link) {
	link.addEventListener('click', function (e) {
		e.preventDefault();
		const imagePreview = document.getElementById('imagePreview');
		const previewImage = document.getElementById('previewImage');
		const imageAnnouncement = document.getElementById('imageAnnouncement');
		previewImage.src = this.dataset.src;
		imagePreview.style.display = 'flex';
		imagePreview.setAttribute('aria-hidden', 'false');
		imageAnnouncement.textContent = 'Image preview opened.';
		document.getElementById('closePreview').focus();
	});
});

function closePreview() {
	const imagePreview = document.getElementById('imagePreview');
	if (!imagePreview) return;
	imagePreview.style.display = 'none';
	imagePreview.setAttribute('aria-hidden', 'true');
	const previewImage = document.getElementById('previewImage');
	if (previewImage) previewImage.src = '';
	const imageAnnouncement = document.getElementById('imageAnnouncement');
	if (imageAnnouncement) imageAnnouncement.textContent = '';
}

document.getElementById('imagePreview')?.addEventListener('click', closePreview);
document.getElementById('closePreview')?.addEventListener('click', closePreview);

document.addEventListener('keydown', function (event) {
	if (event.key === 'Escape') {
		closePreview();
	}
});

// ============================================================================
// DELETE
// ============================================================================

function deleteFile(fileCode) {
	if (confirm('Are you sure you want to delete this file?')) {
		fetch('delete-file.php?code=' + fileCode, { method: 'GET' })
			.then(function (response) { return response.text(); })
			.then(function (data) {
				console.log(data);
				alert('File deleted successfully!');
				location.reload();
			})
			.catch(function (error) {
				console.error('Error deleting file:', error);
				alert('Failed to delete file.');
			});
	}
}

document.querySelectorAll('.delete-btn').forEach(function (button) {
	button.addEventListener('click', function () {
		deleteFile(this.getAttribute('data-file'));
	});
});
