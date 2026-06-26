<?php
// This is called through ajax on the product management page

// Start session
require_once __DIR__ . '/session_start.php';

// Set language from session
$lang_code = $_SESSION['lang'] ?? 'en';
require("../lang/{$lang_code}.php");

// Check if the user has the right priv's
$canEditTeams = ($_SESSION['is_superuser'] || $_SESSION['is_admin']) || in_array((int)($_SESSION['atype'] ?? 0), [3, 4], true);
if (!$canEditTeams) {
	header("location:/openrequest.php?lang={$lang_code}&status=accessdenied"); 
	exit();
}

// Grab MySQL connection
require('../sql.php');
/** @var mysqli $link */

// Now first get the ID
$contactid = $_GET['id'];

// Process the edit team form
if ($_SERVER['REQUEST_METHOD']=='POST'){
	
	// Grab form elements
	$teamnameen = mysqli_real_escape_string($link,$_POST['nameen']);
	$teamnamefr = mysqli_real_escape_string($link,$_POST['namefr']);
	$teamemail = mysqli_real_escape_string($link,$_POST['email']);
	$teamLeadUserId = !empty($_POST['team_lead_user_id']) ? (int)$_POST['team_lead_user_id'] : 0;
	$date_now = date("Y-m-d H:i:s");
	$updatedby = $_SESSION['pid'];
	$noerror = false;
	
	// Custom form validation - require team name and email
	if (empty($teamnameen) || empty($teamnamefr) || empty($teamemail)) {
		$noerror = true;
	}

	// Validate team lead if provided
	if (!$noerror && $teamLeadUserId > 0) {
		$leadCheckSql = "SELECT id FROM tblusers WHERE id='" . $teamLeadUserId . "' AND atype='4' AND status='1' LIMIT 1";
		$leadCheckResult = rmt_admin_query($link, $leadCheckSql);
		if (!rmt_result_num_rows($leadCheckResult)) {
			$noerror = true;
		}
	}
	
	// If error detected send user back to modal dialog
	if ($noerror) {
		header("location:/teams.php?lang={$lang_code}&status=failed"); 
		exit();
	}
	
	// Create SQL statement
	$teamLeadSqlValue = ($teamLeadUserId > 0) ? (string)$teamLeadUserId : "NULL";
	$sql = "UPDATE `tblteams` SET `nameen` = '$teamnameen', `namefr` = '$teamnamefr', `email` = '$teamemail', `team_lead_user_id` = $teamLeadSqlValue, `dateupdated` = '$date_now', `updatedby` = '$updatedby' WHERE id='$contactid'";
	rmt_admin_query($link,$sql);
	
	// Now redirect
	header("location:/teams.php?lang={$lang_code}&status=success"); 
	exit();
}

// Construct SQL statement
$sql2 = "SELECT * FROM tblteams WHERE id='$contactid'";

$result2 = rmt_admin_query($link,$sql2);
//List it
if(rmt_result_num_rows($result2)>0){
	while($row2 = rmt_result_fetch_array($result2)){
		$display_name = $lang_code === 'fr' ? $row2['namefr'] : $row2['nameen'];
?>
<section id="filter-id" class="modal-dialog modal-content overlay-def">
	<header class="modal-header">
		<h2 class="modal-title"><?php echo $lang_code === 'en' ? 'Edit' : 'Modifier l\'équipe'; ?> <?php echo htmlspecialchars($display_name); ?><?php echo $lang_code === 'en' ? ' team' : ''; ?></h2>
	</header>
	<div class="modal-body">
		<form method="post" action="/includes/edit-teams.php?id=<?php echo $row2['id']; ?>">
		<div class="form-group">
			<label for="nameen"><span class="field-name"><?php echo $lang_code === 'en' ? 'Team name (english)' : 'Nom de l\'équipe (anglais)'; ?>: <strong>(<?php echo $lang_code === 'en' ? 'required' : 'requis'; ?>)</strong></span></label>
				<input type="text" class="form-control" id="nameen" name="nameen" value="<?php echo htmlspecialchars($row2['nameen']); ?>" required>
		</div>
		<div class="form-group">
			<label for="namefr"><span class="field-name"><?php echo $lang_code === 'en' ? 'Team name (french)' : 'Nom de l\'équipe (français)'; ?>: <strong>(<?php echo $lang_code === 'en' ? 'required' : 'requis'; ?>)</strong></span></label>
				<input type="text" class="form-control" id="namefr" name="namefr" value="<?php echo htmlspecialchars($row2['namefr']); ?>" required>
		</div>
		<div class="form-group">
			<label for="email"><span class="field-name"><?php echo $lang_code === 'en' ? 'Team email' : 'Courriel de l\'équipe'; ?>: <strong>(<?php echo $lang_code === 'en' ? 'required' : 'requis'; ?>)</strong></span></label>
				<input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($row2['email']); ?>" required>
		</div>
		<div class="form-group">
			<label for="team_lead_user_id"><span class="field-name"><?php echo $lang_code === 'en' ? 'Team Lead' : 'Chef d\'équipe'; ?>:</span></label>
			<select class="form-control" id="team_lead_user_id" name="team_lead_user_id">
				<option value=""><?php echo $lang_code === 'en' ? 'None assigned' : 'Aucun assigné'; ?></option>
				<?php
				$leadSql = "SELECT id, firstname, lastname FROM tblusers WHERE atype='4' AND status='1' ORDER BY firstname ASC, lastname ASC";
				$leadResult = rmt_admin_query($link, $leadSql);
				while ($leadRow = rmt_result_fetch_array($leadResult)) {
					$leadId = (int)$leadRow['id'];
					$currentLeadId = (int)($row2['team_lead_user_id'] ?? 0);
				?>
					<option value="<?php echo $leadId; ?>"<?php if ($leadId === $currentLeadId) echo ' selected'; ?>><?php echo htmlspecialchars($leadRow['firstname'] . ' ' . $leadRow['lastname']); ?></option>
				<?php
				}
				?>
			</select>
			<p class="small"><?php echo $lang_code === 'en' ? 'The person responsible for day-to-day team operations.' : 'La personne responsable des opérations quotidiennes de l\'équipe.'; ?></p>
		</div>
		<div class="form-group form-buttons">
			<button type="submit" class="btn btn-default"><?php echo $lang_code === 'en' ? 'Save' : 'Sauvegarder'; ?></button>
			<button type="button" class="btn btn-default popup-modal-dismiss"><?= $lang_code === 'fr' ? 'Annuler' : 'Cancel' ?></button>
		</div>
		</form>
	</div>
</section>
<?php
	}
} else { 
// Wrong ID so display an error message
?>
<section id="filter-id" class="modal-dialog modal-content overlay-def">
	<header class="modal-header">
		<h2 class="modal-title"><?php echo $lang_code === 'en' ? 'Oops something went wrong!' : 'Oups, quelque chose s\'est mal passé!'; ?></h2>
	</header>
	<div class="modal-body">
		<p><?php echo $lang_code === 'en' ? 'Sorry something went wrong with your request, please try again!' : 'Désolé, une erreur s\'est produite avec votre demande, veuillez réessayer!'; ?></p>
	</div>
</section>
<?php
}
// Close connection
mysqli_close($link);
?>
