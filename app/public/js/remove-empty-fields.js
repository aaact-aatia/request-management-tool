/**
 * Form Utility: Remove Empty Fields
 * 
 * Removes empty form fields before submission to keep query strings clean.
 * Attached to form onsubmit handlers to strip empty parameters from URLs.
 * 
 * @param {Event} event - The form submission event
 */
function removeEmptyFields(event) {
	// Get all form inputs
	var form = event.target;
	var inputs = form.querySelectorAll('input, select, textarea');
	
	// Loop through each input and remove empty ones
	inputs.forEach(function(input) {
		// Skip the lang hidden field - always keep it
		if (input.name === 'lang') return;
		
		// Remove input if value is empty
		if (input.value === '') {
			input.removeAttribute('name');
		}
	});
}
