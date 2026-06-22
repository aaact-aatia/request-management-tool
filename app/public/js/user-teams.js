/**
 * Account type / team checkbox rules for user forms.
 * Used by: includes/add-users.php, includes/edit-users.php
 */
(function () {
	var accountType = document.getElementById('accounttype');
	var teamBoxes = document.querySelectorAll('.team-option');
	var managerSelect = document.getElementById('manager_id');

	function updateTeamSelectionRules() {
		var role = accountType.value;
		var noTeamRoles = ['1', '2', '6'];
		var singleTeamRoles = ['5'];
		var managerRoles = ['4'];

		if (noTeamRoles.indexOf(role) !== -1) {
			teamBoxes.forEach(function (cb) {
				cb.checked = false;
				cb.disabled = true;
			});
			if (managerSelect) {
				managerSelect.value = '';
				managerSelect.disabled = true;
				managerSelect.required = false;
			}
			return;
		}

		if (managerSelect) {
			if (managerRoles.indexOf(role) !== -1) {
				managerSelect.disabled = false;
				managerSelect.required = true;
			} else {
				managerSelect.value = '';
				managerSelect.disabled = true;
				managerSelect.required = false;
			}
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
