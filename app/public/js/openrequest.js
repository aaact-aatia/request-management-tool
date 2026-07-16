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

/**
 * Called when the top-level service-type dropdown changes.
 * stream format: 'catalogue_{id}'
 */
function onStreamChange(stream) {
	clearLegacySelectors();
	showElement('guidance-only', false);
	document.getElementById('catalogueid').value = '';

	if (!stream || !stream.startsWith('catalogue_')) { return; }

	var catId = parseInt(stream.replace('catalogue_', ''), 10);
	if (isNaN(catId)) { return; }

	setTrackableCatalogue(catId);
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
// Use jQuery ready so the listener fires even if DOMContentLoaded already fired
// (scripts load at bottom of page, after the DOM is parsed).
// ============================================================================

$(document).ready(function () {
	$('#service_stream').on('change', function () {
		onStreamChange($(this).val() || '');
	});
});
