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
require_once('helpers.php');
require_once('csrf.php');

// Now first get the ID
$contactid = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0;
$csrfToken = rmt_csrf_token('teams');
$existingTeam = $contactid > 0
	? rmt_db_fetch_one($link, 'SELECT * FROM tblteams WHERE id = ?', 'i', [$contactid])
	: null;

// Process the edit team form
if ($_SERVER['REQUEST_METHOD']=='POST'){
	if (!rmt_csrf_token_is_valid('teams', (string) ($_POST['csrf_token'] ?? ''))) {
		header("location:/teams.php?lang={$lang_code}&status=failed");
		exit();
	}

	$teamnameen = trim((string) ($_POST['nameen'] ?? ''));
	$teamnamefr = trim((string) ($_POST['namefr'] ?? ''));
	$teamemail = strtolower(trim((string) ($_POST['email'] ?? '')));
	$replyToId = trim((string) ($_POST['reply_to_id'] ?? ''));
	$teamLeadUserId = filter_var($_POST['team_lead_user_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0;
	$date_now = date("Y-m-d H:i:s");
	$updatedby = filter_var($_SESSION['pid'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0;
	$noerror = $existingTeam === null || $contactid <= 0 || $teamnameen === '' || $teamnamefr === '' || !filter_var($teamemail, FILTER_VALIDATE_EMAIL) || $updatedby <= 0;

	// Validate team lead if provided
	if (!$noerror && $teamLeadUserId > 0) {
		if (!rmt_db_fetch_one($link, 'SELECT id FROM tblusers WHERE id = ? AND atype = 4 AND status = 1 LIMIT 1', 'i', [$teamLeadUserId])) {
			$noerror = true;
		}
	}
	
	// If error detected send user back to modal dialog
	if ($noerror) {
		header("location:/teams.php?lang={$lang_code}&status=failed"); 
		exit();
	}
	
	$statement = rmt_db_execute(
		$link,
		"UPDATE tblteams SET nameen = ?, namefr = ?, email = ?, reply_to_id = NULLIF(?, ''), team_lead_user_id = NULLIF(?, 0), dateupdated = ?, updatedby = ? WHERE id = ?",
		'ssssisii',
		[$teamnameen, $teamnamefr, $teamemail, $replyToId, $teamLeadUserId, $date_now, $updatedby, $contactid]
	);
	mysqli_stmt_close($statement);
	
	// Now redirect
	header("location:/teams.php?lang={$lang_code}&status=success"); 
	exit();
}


if ($existingTeam) {
	$row2 = $existingTeam;
		$display_name = $lang_code === 'fr' ? $row2['namefr'] : $row2['nameen'];
?>
<section id="filter-id" class="modal-dialog modal-content overlay-def">
	<header class="modal-header">
		<h2 class="modal-title"><?php echo $lang_code === 'en' ? 'Edit' : 'Modifier l\'équipe'; ?> <?php echo htmlspecialchars($display_name); ?><?php echo $lang_code === 'en' ? ' team' : ''; ?></h2>
	</header>
	<div class="modal-body">
		<form method="post" action="/includes/edit-teams.php?id=<?php echo $row2['id']; ?>">
		<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
		<div class="form-group">
			<label for="nameen"><span class="field-name"><?php echo $lang_code === 'en' ? 'Team name (english)' : 'Nom de l\'équipe (anglais)'; ?>: <strong>(<?php echo $lang_code === 'en' ? 'required' : 'requis'; ?>)</strong></span></label>
				<input type="text" class="form-control full-width" id="nameen" name="nameen" value="<?php echo htmlspecialchars($row2['nameen']); ?>" required>
		</div>
		<div class="form-group">
			<label for="namefr"><span class="field-name"><?php echo $lang_code === 'en' ? 'Team name (french)' : 'Nom de l\'équipe (français)'; ?>: <strong>(<?php echo $lang_code === 'en' ? 'required' : 'requis'; ?>)</strong></span></label>
				<input type="text" class="form-control full-width" id="namefr" name="namefr" value="<?php echo htmlspecialchars($row2['namefr']); ?>" required>
		</div>
		<div class="form-group">
			<label for="email"><span class="field-name"><?php echo $lang_code === 'en' ? 'Team email' : 'Courriel de l\'équipe'; ?>: <strong>(<?php echo $lang_code === 'en' ? 'required' : 'requis'; ?>)</strong></span></label>
				<input type="email" class="form-control full-width" id="email" name="email" value="<?php echo htmlspecialchars($row2['email']); ?>" required>
		</div>
		<div class="form-group">
			<label for="reply_to_id"><span class="field-name"><?php echo $lang_code === 'en' ? 'GC Notify reply-to ID' : 'ID de réponse GC Notify'; ?>:</span></label>
			<input type="text" class="form-control full-width" id="reply_to_id" name="reply_to_id" value="<?php echo htmlspecialchars($row2['reply_to_id'] ?? ''); ?>">
			<p class="small"><?php echo $lang_code === 'en' ? 'Optional. Use the ID from GC Notify Settings. Blank uses the global default.' : 'Facultatif. Utilisez l\'ID des paramètres GC Notify. Vide utilise la valeur globale.'; ?></p>
		</div>
		<div class="form-group">
			<label for="team_lead_user_id"><span class="field-name"><?php echo $lang_code === 'en' ? 'Team Lead' : 'Chef d\'équipe'; ?>:</span></label>
			<select class="form-control full-width" id="team_lead_user_id" name="team_lead_user_id">
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
