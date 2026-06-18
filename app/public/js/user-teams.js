/**
 * Account type / team checkbox rules for user forms.
 * Used by: includes/add-users.php, includes/edit-users.php
 */
(function () {
	var accountType = document.getElementById('accounttype');
	var teamBoxes = document.querySelectorAll('.team-option');

	function updateTeamSelectionRules() {
		var role = accountType.value;
		var noTeamRoles = ['1', '2', '6'];
		var singleTeamRoles = ['4', '5'];

		if (noTeamRoles.indexOf(role) !== -1) {
			teamBoxes.forEach(function (cb) {
				cb.checked = false;
				cb.disabled = true;
			});
			return;
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
			if (['4', '5'].indexOf(accountType.value) !== -1) {
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
