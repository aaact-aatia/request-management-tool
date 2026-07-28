/**
 * openrequest.php page logic: service stream navigation and cascade dropdowns.
 *
 * Requires these variables to be set before this script loads (via inline <script>):
 *   window.guidanceWorkshop        – HTML string for workshop guidance panel
 *   window.guidanceNonSscDocuments – HTML string for non-SSC documents guidance panel
 */

function showElement(id, visible) {
	const node = document.getElementById(id);
	if (!node) return;
	node.style.display = visible ? '' : 'none';
}

function clearLegacySelectors() {
	$('.divservice').empty();
	$('.divsubservice').empty();
	$('.divsubservice2').empty();
	$('.divsubservice3').empty();
}

function setTrackableCatalogue(catalogueId) {
	document.getElementById('catalogueid').value = catalogueId;
	showElement('guidance-only', false);
	ajax1(catalogueId);
}

function showGuidanceOnly(html) {
	document.getElementById('catalogueid').value = '';
	clearLegacySelectors();
	document.getElementById('guidance-only-content').innerHTML = html;
	showElement('guidance-only', true);
	document.getElementById('guidance-only').focus();
}

function onStreamChange(stream) {
	showElement('informational-options', stream === 'informational');
	showElement('software-options', stream === 'software');
	showElement('documents-options', stream === 'documents');
	document.getElementById('informational_kind').value = '';
	document.getElementById('software_kind').value = '';
	document.getElementById('documents_ssc').value = '';
	document.getElementById('catalogueid').value = '';
	clearLegacySelectors();
	showElement('guidance-only', false);
}

function onInformationalChoice(kind) {
	if (kind === 'workshops') {
		showGuidanceOnly(window.guidanceWorkshop);
		return;
	}
	setTrackableCatalogue(3);
}

function onSoftwareChoice() {
	setTrackableCatalogue(8);
}

function onDocumentsChoice(isSsc) {
	if (isSsc === 'yes') {
		setTrackableCatalogue(6);
		return;
	}
	showGuidanceOnly(window.guidanceNonSscDocuments);
}

// ============================================================================
// CASCADE DROPDOWNS
// ============================================================================

function ajax1(val1) {
	$.ajax({
		url: 'addrequest2-ajax1.php?v1=' + val1,
		success: function (result) { $('.divservice').html(result); }
	});
	$('.divsubservice').hide();
	$('.divsubservice2').hide();
	$('.divsubservice3').hide();
}

function ajax2(val1) {
	$.ajax({
		url: 'addrequest2-ajax2.php?v1=' + val1,
		success: function (result) { $('.divsubservice').html(result); }
	});
	$('.divsubservice').show();
	$('.divsubservice2').hide();
	$('.divsubservice3').hide();
}

function ajax3(val1) {
	$.ajax({
		url: 'addrequest2-ajax3.php?v1=' + val1,
		success: function (result) { $('.divsubservice2').html(result); }
	});
	$('.divsubservice2').show();
	$('.divsubservice3').hide();
}

function ajax4(val1) {
	$.ajax({
		url: 'addrequest2-ajax4.php?v1=' + val1,
		success: function (result) { $('.divsubservice3').html(result); }
	});
	$('.divsubservice3').show();
}

// ============================================================================
// EVENT LISTENERS
// ============================================================================

document.addEventListener('DOMContentLoaded', function () {
	document.getElementById('service_stream').addEventListener('change', function (evt) {
		const selected = evt.target.value;
		if (!selected) {
			onStreamChange('none');
			return;
		}
		if (selected === 'guidance_workshops') {
			onStreamChange('none');
			showGuidanceOnly(window.guidanceWorkshop);
			return;
		}
		if (selected === 'catalogue_3') {
			onStreamChange('informational');
			return;
		}
		if (selected === 'catalogue_8') {
			onStreamChange('software');
			return;
		}
		if (selected === 'catalogue_6') {
			onStreamChange('documents');
			return;
		}
	});

	document.getElementById('informational_kind').addEventListener('change', function (evt) {
		onInformationalChoice(evt.target.value);
	});

	document.getElementById('software_kind').addEventListener('change', function () {
		onSoftwareChoice();
	});

	document.getElementById('documents_ssc').addEventListener('change', function (evt) {
		onDocumentsChoice(evt.target.value);
	});
});
