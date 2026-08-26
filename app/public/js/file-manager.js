/**
 * File manager: download, preview, and delete uploaded files.
 * Used by: viewrequest.php, editrequest.php (via includes/editrequest-scripts.php)
 */

// ============================================================================
// FILE SELECTION
// ============================================================================

const selectAllCheckbox = document.getElementById('selectAll');
const fileCheckboxes = document.querySelectorAll('.fileCheckbox');
if (selectAllCheckbox) {
	selectAllCheckbox.addEventListener('change', function () {
		fileCheckboxes.forEach(function (checkbox) { checkbox.checked = this.checked; }, this);
		this.indeterminate = false;
	});

	fileCheckboxes.forEach(function (checkbox) {
		checkbox.addEventListener('change', function () {
			const selectedCount = Array.from(fileCheckboxes).filter(function (fileCheckbox) {
				return fileCheckbox.checked;
			}).length;
			selectAllCheckbox.checked = selectedCount === fileCheckboxes.length;
			selectAllCheckbox.indeterminate = selectedCount > 0 && selectedCount < fileCheckboxes.length;
		});
	});
}

// ============================================================================
// DOWNLOAD
// ============================================================================

const downloadAllButton = document.getElementById('downloadAll');
if (downloadAllButton) {
	downloadAllButton.addEventListener('click', function () {
		const selectedCheckboxes = Array.from(fileCheckboxes).filter(function (checkbox) {
			return checkbox.checked;
		});
		if (selectedCheckboxes.length === 0) {
			alert(this.getAttribute('data-no-selection-message'));
			return;
		}

		const form = document.createElement('form');
		form.method = 'get';
		form.action = '/download-selected.php';
		selectedCheckboxes.forEach(function (checkbox) {
			const row = checkbox.closest('tr');
			if (!row) {
				return;
			}

			const button = row.querySelector('.download-btn');
			if (button) {
				const codeInput = document.createElement('input');
				codeInput.type = 'hidden';
				codeInput.name = 'codes[]';
				codeInput.value = button.getAttribute('data-file');
				form.appendChild(codeInput);
			}
		});
		document.body.appendChild(form);
		form.submit();
		form.remove();
	});
}

// ============================================================================
// IMAGE PREVIEW
// ============================================================================

let imagePreviewTrigger = null;

document.querySelectorAll('.image-link').forEach(function (link) {
	link.addEventListener('click', function (e) {
		e.preventDefault();
		const imagePreview = document.getElementById('imagePreview');
		const previewImage = document.getElementById('previewImage');
		const imageAnnouncement = document.getElementById('imageAnnouncement');
		if (!imagePreview || !previewImage || !imageAnnouncement) {
			return;
		}
		imagePreviewTrigger = this;
		previewImage.src = this.dataset.src;
		previewImage.alt = this.dataset.name || '';
		imagePreview.style.display = 'flex';
		imagePreview.setAttribute('aria-hidden', 'false');
		imageAnnouncement.textContent = imagePreview.dataset.openedMessage || '';
		document.getElementById('closePreview')?.focus();
	});
});

function closePreview() {
	const imagePreview = document.getElementById('imagePreview');
	if (!imagePreview) return;
	imagePreview.style.display = 'none';
	imagePreview.setAttribute('aria-hidden', 'true');
	const previewImage = document.getElementById('previewImage');
	if (previewImage) {
		previewImage.src = '';
		previewImage.alt = '';
	}
	const imageAnnouncement = document.getElementById('imageAnnouncement');
	if (imageAnnouncement) imageAnnouncement.textContent = '';
	imagePreviewTrigger?.focus();
	imagePreviewTrigger = null;
}

document.getElementById('imagePreview')?.addEventListener('click', function (event) {
	if (event.target === this) {
		closePreview();
	}
});
document.getElementById('closePreview')?.addEventListener('click', closePreview);

document.addEventListener('keydown', function (event) {
	const imagePreview = document.getElementById('imagePreview');
	if (!imagePreview || imagePreview.getAttribute('aria-hidden') === 'true') {
		return;
	}

	if (event.key === 'Escape') {
		closePreview();
	} else if (event.key === 'Tab') {
		event.preventDefault();
		document.getElementById('closePreview')?.focus();
	}
});
