/**
 * Ajax cascade dropdowns for addrequest pages.
 * Used by: addrequest.php, asearch.php, clonerequest.php
 */
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
