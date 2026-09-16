/**
 * Ajax cascade dropdowns for request hierarchy fields.
 * Used by: addrequest.php, asearch.php, clonerequest.php, editrequest.php
 */
function ajax1(val1, context) {
	$.ajax({
		url: "addrequest-ajax1.php?v1=" + val1 + "&context=" + encodeURIComponent(context || ''),
		success: function(result) {
			$(".divservice").html(result);
			$(".divservice").toggle($.trim(result) !== "");
		}
	});
	$(".divsubservice").empty().hide();
}

function ajax2(val1, context) {
	$.ajax({
		url: "addrequest-ajax2.php?v1=" + val1 + "&context=" + encodeURIComponent(context || ''),
		success: function(result) {
			$(".divsubservice").html(result);
			$(".divsubservice").toggle($.trim(result) !== "");
		}
	});
}
