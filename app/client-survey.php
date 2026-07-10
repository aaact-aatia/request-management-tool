<?php

/**
 * Client Survey - Customer Satisfaction Survey Form
 */

// Grab MySQL connection (includes session management)
require('sql.php');
/** @var mysqli $link */

// Handle language from query string or session
if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'fr'])) {
	$_SESSION['lang'] = $_GET['lang'];
}

// Set default language if not set
if (!isset($_SESSION['lang']) || !in_array($_SESSION['lang'], ['en', 'fr'])) {
	$_SESSION['lang'] = 'en';
}

// Load language file
$lang = $_SESSION['lang'];
$langFile = require("lang/{$_SESSION['lang']}.php");

// Grab HTTPS check
require('includes/httpscheck.php');

/**
 * Resolve the team contact for a survey request.
 */
function rmt_get_survey_team_contact(mysqli $link, int $requestId, string $language = 'en'): array {
	$fallback = [
		'email' => 'aaact-aatia@ssc-spc.gc.ca',
		'label' => 'Accessibility, Accommodation and Adaptive Computer Technology (AAACT)',
	];

	if ($requestId <= 0) {
		return $fallback;
	}

	$isFrench = ($language === 'fr');

	$sql = "SELECT t.email AS team_email,
				   t.nameen AS team_name_en,
				   t.namefr AS team_name_fr,
				   c2.teamemail AS contact_team_email,
				   c2.teamnameen AS contact_team_name_en,
				   c2.teamnamefr AS contact_team_name_fr
			FROM tbltriage tr
			LEFT JOIN tblcatalogue cat ON cat.id = tr.catalogueid
			LEFT JOIN tblteams t ON t.id = cat.contactid
			LEFT JOIN tblcontacts c2 ON c2.id = cat.contactid
			WHERE tr.id = '$requestId'
			LIMIT 1";
	$result = mysqli_query($link, $sql);
	if ($result && mysqli_num_rows($result) > 0) {
		$row = mysqli_fetch_assoc($result);
		$teamLabel = trim((string)($isFrench ? ($row['team_name_fr'] ?? '') : ($row['team_name_en'] ?? '')));
		$contactTeamLabel = trim((string)($isFrench ? ($row['contact_team_name_fr'] ?? '') : ($row['contact_team_name_en'] ?? '')));
		$teamEmail = trim((string)($row['team_email'] ?? ''));

		if ($teamEmail !== '') {
			return [
				'email' => $teamEmail,
				'label' => ($teamLabel !== '') ? $teamLabel : $fallback['label'],
			];
		}

		$contactTeamEmail = trim((string)($row['contact_team_email'] ?? ''));
		if ($contactTeamEmail !== '') {
			return [
				'email' => $contactTeamEmail,
				'label' => ($contactTeamLabel !== '') ? $contactTeamLabel : $fallback['label'],
			];
		}
	}

	return $fallback;
}

// Process the add product form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

	// Grab form elements
	$requestid = (int)($_POST['requestid'] ?? 0);
	$overall = mysqli_real_escape_string($link, $_POST['satisfaction']);
	$response = mysqli_real_escape_string($link, $_POST['response']);
	$comments = mysqli_real_escape_string($link, $_POST['comments']);
	$status = 1;

	// Encode the ID
	$nrequestid = base64_encode($requestid);

	// Set to no error
	$noerror = false;

	// Custom form validation
	if ($requestid <= 0 or $overall == "" or $response == "") {
		$noerror = true;
	}

	// If error detected send user back to modal dialog
	if ($noerror) {
		header("location:/client-survey.php?lang=" . $_SESSION['lang'] . "&status=incomplete&erid=$nrequestid");
		exit();
	}

	mysqli_begin_transaction($link);

	try {
		$lockSql = "SELECT id FROM tbltriage WHERE id = '$requestid' LIMIT 1 FOR UPDATE";
		$lockResult = mysqli_query($link, $lockSql);
		if (!$lockResult || mysqli_num_rows($lockResult) === 0) {
			mysqli_rollback($link);
			header("location:/client-survey.php?lang=" . $_SESSION['lang'] . "&status=failed");
			exit();
		}

		$existingSql = "SELECT id FROM tblcss WHERE requestid = '$requestid' LIMIT 1 FOR UPDATE";
		$existingResult = mysqli_query($link, $existingSql);
		if (!$existingResult) {
			mysqli_rollback($link);
			header("location:/client-survey.php?lang=" . $_SESSION['lang'] . "&status=failed&erid=$nrequestid");
			exit();
		}
		if ($existingResult && mysqli_num_rows($existingResult) > 0) {
			mysqli_rollback($link);
			header("location:/client-survey.php?lang=" . $_SESSION['lang'] . "&status=complete&erid=$nrequestid");
			exit();
		}

		$insertSql = "INSERT INTO tblcss(`requestid`, `overall`, `response`, `comments`, `status`) VALUES ('$requestid', '$overall', '$response', '$comments', '$status')";
		$insertResult = mysqli_query($link, $insertSql);
		if (!$insertResult) {
			mysqli_rollback($link);
			header("location:/client-survey.php?lang=" . $_SESSION['lang'] . "&status=failed&erid=$nrequestid");
			exit();
		}

		mysqli_commit($link);
		header("location:/client-survey-thank-you.php?lang=" . $_SESSION['lang']);
		exit();
	} catch (Throwable $e) {
		mysqli_rollback($link);
		header("location:/client-survey.php?lang=" . $_SESSION['lang'] . "&status=failed&erid=$nrequestid");
		exit();
	}
}

// Check if there is a status
if (!empty($_GET['status'])) {
	$status = $_GET['status'];
} else {
	$status = "";
}

// Grab the request ID
if (!empty($_GET['erid'])) {
	// There is an id so grab it
	$requestid = (int)base64_decode((string)$_GET['erid']);
} else {
	// There was no request ID
	$requestid = null;
	$status = "failed";
}

$surveyTeamContact = rmt_get_survey_team_contact($link, (int)$requestid, $_SESSION['lang']);
$surveyTeamEmail = trim((string)($surveyTeamContact['email'] ?? 'aaact-aatia@ssc-spc.gc.ca'));
$surveyTeamLabel = trim((string)($surveyTeamContact['label'] ?? 'Accessibility, Accommodation and Adaptive Computer Technology (AAACT)'));

if ($surveyTeamEmail === '') {
	$surveyTeamEmail = 'aaact-aatia@ssc-spc.gc.ca';
}
if ($surveyTeamLabel === '') {
	$surveyTeamLabel = 'Accessibility, Accommodation and Adaptive Computer Technology (AAACT)';
}

// Check if this survey was already completed
// Construct SQL statement
$sql = "SELECT id FROM tblcss WHERE requestid='$requestid'";
$result = mysqli_query($link, $sql);
//List it
if (mysqli_num_rows($result) > 0) {
	// Already completed!
	$status = "complete";
}

// Load config
require_once 'includes/config.php';

// Page-specific metadata
$pageTitle = $langFile['client_survey_page_title'];
$pageDescription = '';

include 'includes/template/head.php';
include 'includes/template/header.php';
?>
<main role="main" property="mainContentOfPage" class="container">
	<?php if ($status == 'complete') { ?>
		<h1 property="name" id="wb-cont"><?= htmlspecialchars($langFile['client_survey_complete_heading']) ?></h1>
	<?php } else { ?>
		<h1 property="name" id="wb-cont"><?= htmlspecialchars($langFile['client_survey_heading']) ?></h1>
	<?php } ?>

	<?php
	if ($status == 'failed') {
	?>
		<section class="alert alert-danger">
			<h2><?= htmlspecialchars($langFile['client_survey_failed_heading']) ?></h2>
			<ul>
				<li><?= htmlspecialchars($langFile['client_survey_failed_message']) ?></li>
			</ul>
		</section>
	<?php
	} elseif ($status == 'incomplete') {
	?>
		<section class="alert alert-danger">
			<h2><?= htmlspecialchars($langFile['client_survey_failed_heading']) ?></h2>
			<ul>
				<li><?= htmlspecialchars($langFile['client_survey_incomplete_message']) ?></li>
			</ul>
		</section>
	<?php
	} elseif ($status == 'complete') {
	?>
			<p>
				<?= sprintf(
					htmlspecialchars($langFile['client_survey_complete_message_with_team']),
					'<a href="mailto:' . htmlspecialchars($surveyTeamEmail) . '">' . htmlspecialchars($surveyTeamLabel) . '</a>'
				) ?>
			</p>
		<?php
	}
	if ($status != 'failed' and $status != 'complete') {

		// Construct SQL statement
		$sql = "SELECT id,requestid,title FROM tbltriage WHERE id='$requestid'";
		$result = mysqli_query($link, $sql);
		//List it
		if (mysqli_num_rows($result) > 0) {
			while ($row = mysqli_fetch_array($result)) {
				$rid = $row['requestid'];
				$rtitle = $row['title'];
			}
		?>

			<h2>a11y-<?php echo $rid ?> - <?php echo $rtitle ?></h2>

			<p><?= htmlspecialchars($langFile['client_survey_intro']) ?></p>

			<form method="post" action="/client-survey.php?lang=<?= $_SESSION['lang'] ?>">
				<input type="hidden" id="requestid" name="requestid" value="<?php echo $requestid ?>">

				<fieldset>
					<legend><?= htmlspecialchars($langFile['client_survey_legend']) ?></legend>
					<div class="form-group mrgn-tp-lg">
						<label for="satisfaction"><?= htmlspecialchars($langFile['client_survey_overall']) ?></label>
						<select class="form-control" id="satisfaction" name="satisfaction">
							<option label="<?= htmlspecialchars($langFile['client_survey_overall']) ?>"></option>
							<option value="10">10</option>
							<option value="9">9</option>
							<option value="8">8</option>
							<option value="7">7</option>
							<option value="6">6</option>
							<option value="5">5</option>
							<option value="4">4</option>
							<option value="3">3</option>
							<option value="2">2</option>
							<option value="1">1</option>
						</select>
					</div>
					<div class="form-group mrgn-tp-lg">
						<label for="response"><?= htmlspecialchars($langFile['client_survey_response_time']) ?></label>
						<select class="form-control" name="response" id="response">
							<option label="<?= htmlspecialchars($langFile['client_survey_response_time']) ?>"></option>
							<option value="10">10</option>
							<option value="9">9</option>
							<option value="8">8</option>
							<option value="7">7</option>
							<option value="6">6</option>
							<option value="5">5</option>
							<option value="4">4</option>
							<option value="3">3</option>
							<option value="2">2</option>
							<option value="1">1</option>
						</select>
					</div>
				</fieldset>
				<div class="form-group mrgn-tp-lg">
					<label for="comments"><?= htmlspecialchars($langFile['client_survey_comments_label']) ?></label>
					<textarea class="form-control full-width expand" name="comments" id="comments"></textarea>
				</div>

				<div class="form-group form-buttons">
					<button type="submit" class="btn btn-default"><?= htmlspecialchars($langFile['client_survey_submit']) ?></button>
				</div>
			</form>
		<?php } else {
		?>
			<section class="alert alert-danger">
				<h2><?= htmlspecialchars($langFile['client_survey_failed_heading']) ?></h2>
				<ul>
					<li><?= htmlspecialchars($langFile['client_survey_failed_message']) ?></li>
				</ul>
			</section>
		<?php
		} ?>
	<?php } ?>
	<?php include 'includes/template/page-details.php'; ?>
</main>
<?php
include 'includes/template/footer.php';
include 'includes/template/scripts.php';

// Close connection
mysqli_close($link);
