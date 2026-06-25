<?php
// This is called through ajax on the product management page

// Start session
require_once __DIR__ . '/session_start.php';

// Get language
$lang = $_GET['lang'] ?? 'en';

// Check if the user has the right priv's
if (!($_SESSION['is_superuser'] OR $_SESSION['is_admin'])) {
	header("location:/openrequest-$lang.php?status=accessdenied"); 
	exit();
}

// Grab MySQL connection
require('../sql.php');

// Now first get the ID
$contactid = $_GET['id'];

// Process the delete product form
if ($_SERVER['REQUEST_METHOD']=='POST'){
	
	// Hard-delete the team record
	$sql = "DELETE FROM `tblteams` WHERE id='$contactid'";
	//echo $sql;
	rmt_admin_query($link,$sql);
	
	// Now redirect
	header("location:/teams.php?lang=$lang&status=success"); 
	exit();
}

// Construct SQL statement
$sql2 = "SELECT * FROM tblteams WHERE id='$contactid'";

$result2 = rmt_admin_query($link,$sql2);
//List it
if(rmt_result_num_rows($result2)>0){
	while($row2 = rmt_result_fetch_array($result2)){
		$teamname = ($lang == 'fr') ? $row2['namefr'] : $row2['nameen'];
		$title = ($lang == 'fr') ? "Supprimer l'équipe $teamname" : "Delete $teamname team";
		$question = ($lang == 'fr') ? "Voulez-vous vraiment supprimer l'équipe <strong>" . htmlspecialchars($teamname) . "</strong>?" : "Are you sure you wish to delete the <strong>" . htmlspecialchars($teamname) . "</strong> team?";
		$buttonText = ($lang == 'fr') ? "Oui" : "Yes";
	// Check for users assigned to this team
	$userCheckSql = "SELECT firstname, lastname FROM tblusers WHERE team='$contactid' ORDER BY firstname ASC, lastname ASC";
	$userCheckResult = rmt_admin_query($link, $userCheckSql);
	$assignedUserCount = rmt_result_num_rows($userCheckResult);

	$warningMessage = '';
	if ($assignedUserCount > 0) {
		$userListItems = '';
		while ($urow = rmt_result_fetch_array($userCheckResult)) {
			$userListItems .= '<li>' . htmlspecialchars($urow['firstname'] . ' ' . $urow['lastname']) . '</li>';
		}
		if ($lang == 'fr') {
			$warningMessage = "<div class='alert alert-warning' role='alert'><strong>Attention&nbsp;:</strong> Les utilisateurs suivants seront orphelins après la suppression de l'équipe <strong>" . htmlspecialchars($teamname) . "</strong>&nbsp;:<ul>$userListItems</ul></div>";
		} else {
			$warningMessage = "<div class='alert alert-warning' role='alert'><strong>Warning:</strong> The following user(s) will be orphaned after deleting the <strong>" . htmlspecialchars($teamname) . "</strong> team:<ul>$userListItems</ul></div>";
		}
	}?>
<section id="filter-id" class="modal-dialog modal-content overlay-def">
	<header class="modal-header">
		<h2 class="modal-title"><?php echo $title ?></h2>
	</header>
	<div class="modal-body">
		<?php if (!empty($warningMessage)) { echo $warningMessage; } ?>
		<form method="post" action="/includes/delete-teams.php?lang=<?php echo $lang ?>&id=<?php echo $row2['id'] ?>">
		<p tabindex="0"><?php echo $question ?></p>
		<div class="form-group form-buttons">
			<button type="submit" class="btn btn-default"><?php echo $buttonText ?></button>
			<button type="button" class="btn btn-default popup-modal-dismiss"><?= $lang === 'fr' ? 'Non' : 'No' ?></button>
		</div>
		</form>
	</div>
</section>
<?php
	}
} else { 
// Wrong ID so display an error message
	$errorTitle = ($lang == 'fr') ? "Oups, quelque chose s'est mal passé!" : "Oops something went wrong!";
	$errorMsg = ($lang == 'fr') ? "Désolé, une erreur s'est produite avec votre demande, veuillez réessayer!" : "Sorry something went wrong with your request, please try again!";
?>
<section id="filter-id" class="modal-dialog modal-content overlay-def">
	<header class="modal-header">
		<h2 class="modal-title"><?php echo $errorTitle ?></h2>
	</header>
	<div class="modal-body">
		<p><?php echo $errorMsg ?></p>
	</div>
</section>
<?php
}
// Close connection
mysqli_close($link);
?>
