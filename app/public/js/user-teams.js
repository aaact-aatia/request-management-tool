/**
 * Account type / team checkbox rules for user forms.
 * Used by: includes/add-users.php, includes/edit-users.php
 */
(function () {
	var accountType = document.getElementById('accounttype');
	var teamBoxes = document.querySelectorAll('.team-option');
	var reportsToManager = document.getElementById('reports-to-manager');
	var userForm = document.querySelector('form[action="/includes/add-users.php"]');
	var submitButton = userForm ? userForm.querySelector('button[type="submit"]') : null;
	var busyStatus = userForm ? userForm.querySelector('[data-add-user-status]') : null;
	var originalButtonLabel = submitButton ? submitButton.textContent : '';
	var isSubmitting = false;

	function updateTeamSelectionRules() {
		var role = accountType.value;
		var noTeamRoles = ['1', '2', '6'];
		var singleTeamRoles = ['5'];
		var managerRoles = ['5'];

		if (noTeamRoles.indexOf(role) !== -1) {
			teamBoxes.forEach(function (cb) {
				cb.checked = false;
				cb.disabled = true;
			});
			if (reportsToManager) {
				reportsToManager.checked = false;
				reportsToManager.disabled = true;
			}
			return;
		}

		if (reportsToManager) {
			reportsToManager.disabled = managerRoles.indexOf(role) === -1;
		}

		teamBoxes.forEach(function (cb) {
			cb.disabled = false;
		});

		if (singleTeamRoles.indexOf(role) !== -1) {
			var checked = Array.prototype.filter.call(teamBoxes, function (cb) { return cb.checked; });
			if (checked.length > 1) {
				checked.slice(1).forEach(function (cb) { cb.checked = false; });
			}
		}
	}

	function setSubmittingState(isBusy) {
		if (!userForm || !submitButton) {
			return;
		}

		if (isBusy) {
			submitButton.disabled = true;
			submitButton.setAttribute('aria-busy', 'true');
			submitButton.textContent = userForm.dataset.busyLabel || originalButtonLabel;
			if (busyStatus) {
				busyStatus.textContent = userForm.dataset.busyStatus || submitButton.textContent;
			}
			return;
		}

		submitButton.disabled = false;
		submitButton.removeAttribute('aria-busy');
		submitButton.textContent = originalButtonLabel;
		if (busyStatus) {
			busyStatus.textContent = '';
		}
	}

	if (userForm && submitButton) {
		userForm.addEventListener('submit', function (event) {
			if (isSubmitting) {
				event.preventDefault();
				return;
			}

			isSubmitting = true;
			setSubmittingState(true);
		});

		window.addEventListener('pageshow', function (event) {
			if (event.persisted) {
				isSubmitting = false;
				setSubmittingState(false);
			}
		});
	}

	teamBoxes.forEach(function (cb) {
		cb.addEventListener('change', function () {
			if (['5'].indexOf(accountType.value) !== -1) {
				teamBoxes.forEach(function (other) {
					if (other !== cb) {
						other.checked = false;
					}
				});
			}
		});
	});

	accountType.addEventListener('change', updateTeamSelectionRules);
	updateTeamSelectionRules();
})();
