<?php
/**
 * Consolidated Bilingual Dashboard - Active Requests
 * 
 * This page replaces the separate indexonly-en.php and indexonly-fr.php files
 * by using a language file system. The language is determined by $_SESSION['lang'].
 * 
 * @package RMT
 * @since 2.0.0
 */

// Grab MySQL connection (includes session management)
require('sql.php');
/** @var mysqli $link */
require('includes/helpers.php');

// Handle language from query string or session
if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'fr'])) {
	$_SESSION['lang'] = $_GET['lang'];
}

// Set default language if not set
if (!isset($_SESSION['lang']) || !in_array($_SESSION['lang'], ['en', 'fr'])) {
	$_SESSION['lang'] = 'en';
}

// Load language file
$langFile = require("lang/{$_SESSION['lang']}.php");
require('includes/sla-calculator.php');

// Grab HTTPS check
require('includes/httpscheck.php');

// Security check
if ($_SESSION['lang'] === 'fr') {
	require('includes/loggedincheck.php');
} else {
	require('includes/loggedincheck.php');
}

// Include file for calculating business days
require('includes/calculate-bdays.php');

// Determine database column for name fields
$nameColumn = ($_SESSION['lang'] === 'fr') ? 'namefr' : 'nameen';
$showTeamRequests = isset($_GET['show_team']) && $_GET['show_team'] === '1';
$showClosedRequests = isset($_GET['show_closed']) && $_GET['show_closed'] === '1';

// =============================================================================
// PAGE FRONTMATTER - Define page metadata
// =============================================================================
$page = [
	'title' => [
		'en' => 'My requests',
		'fr' => 'Mes demandes'
	],
	'description' => [
		'en' => 'View requests assigned to me, with optional team and closed request views',
		'fr' => 'Voir les demandes qui me sont assignées, avec des options pour l’équipe et les demandes fermées'
	]
];

// Store language code for templates (header.php needs $lang)
$lang = $_SESSION['lang'];

// Extract values for current language
$pageTitle = $page['title'][$lang];
$pageDescription = $page['description'][$lang];
$extraStyles = '
	.wb-eqht-grd .panel.hght-inhrt {
		display: flex;
		flex-direction: column;
	}
	.wb-eqht-grd .panel.hght-inhrt .panel-body {
		flex: 1 1 auto;
	}
';

// Include template head
include 'includes/template/head.php';
?>
	<?php include 'includes/template/header.php'; ?>
		<main role="main" property="mainContentOfPage" class="container">
			<h1 property="name" id="wb-cont"><?= htmlspecialchars($langFile['indexonly_heading']) ?></h1>
			<form method="get" action="indexonly.php" class="mrgn-bttm-lg">
				<input type="hidden" name="lang" value="<?= htmlspecialchars($_SESSION['lang']) ?>">
				<fieldset class="gc-chckbxrdio">
					<legend class="mrgn-bttm-0"><?= htmlspecialchars($langFile['indexonly_additional_requests']) ?></legend>
					<ul class="list-unstyled lst-spcd-2">
						<li class="checkbox"><input type="checkbox" id="show-team-requests" name="show_team" value="1" onchange="this.form.submit()" <?= $showTeamRequests ? 'checked' : '' ?>><label for="show-team-requests"><?= htmlspecialchars($langFile['indexonly_show_full_team']) ?></label></li>
						<li class="checkbox"><input type="checkbox" id="show-closed-requests" name="show_closed" value="1" onchange="this.form.submit()" <?= $showClosedRequests ? 'checked' : '' ?>><label for="show-closed-requests"><?= htmlspecialchars($langFile['indexonly_show_closed']) ?></label></li>
					</ul>
				</fieldset>
			</form>
			<?php
			$userid = getEffectiveEmployeeUserId($link);
			$teamIds = [];
			if ($showTeamRequests) {
				$teamIds = getEffectiveEmployeeTeamIds($link);
			}
			// Construct SQL statement
			$workerFilter = $showTeamRequests ? '' : " AND workerid = '$userid'";
			$statusFilter = $showClosedRequests ? '' : " AND statusid NOT IN ('4', '5', '6')";
			$sql = "SELECT * FROM tbltriage WHERE status = '1'$workerFilter$statusFilter ORDER BY requestid DESC";
			
			$result = mysqli_query($link,$sql);
			$surveyAnsweredByRequest = [];
			$surveyResult = mysqli_query($link, "SELECT DISTINCT requestid FROM tblcss WHERE status = 1");
			if ($surveyResult) {
				while ($surveyRow = mysqli_fetch_assoc($surveyResult)) {
					$surveyAnsweredByRequest[(int)$surveyRow['requestid']] = true;
				}
			}
			//List it
			if(mysqli_num_rows($result)>0){
			?>
			<section class="provisional wb-tagfilter wb-filter" data-wb-filter='{"selector": "[data-wb-tags]", "section": ".wb-tagfilter-items", "uiTemplate": "#indexonly-filter-ui"}'>
				<h2 class="wb-inv"><?= ($_SESSION['lang'] === 'fr') ? 'Options de filtrage' : 'Filter options' ?></h2>
				<div id="indexonly-filter-ui" class="row">
					<div class="col-sm-12">
						<p id="indexonly-filter-info" class="wb-fltr-info mrgn-bttm-sm"><span data-nbitem><?= (int)mysqli_num_rows($result) ?></span> <?= htmlspecialchars($langFile['indexonly_results_out_of'] ?? (($_SESSION['lang'] === 'fr') ? 'résultats sur' : 'results out of')) ?> <span data-total><?= (int)mysqli_num_rows($result) ?></span></p>
					</div>
					<div class="col-md-12">
						<div class="form-group">
							<div class="input-group">
								<label for="indexonly-search" class="input-group-addon"><?= ($_SESSION['lang'] === 'fr') ? 'Filtrer' : 'Filter' ?></label>
								<input type="search" class="form-control" id="indexonly-search">
							</div>
						</div>
					</div>
				</div>
				<div class="row wb-eqht-grd wb-tagfilter-items">
				<?php
				$hasVisibleRows = false;
				while($row = mysqli_fetch_array($result)){
					// Check if clientlname or clientfname is not empty
					$clientfname = $row['clientfname'];
					$clientlname = $row['clientlname'];
					$clientname = "";
					if (!empty($clientfname) AND !empty($clientlname)) {
						$clientname = $clientfname . " " . $clientlname;
					}					
					// We need to calculate if ticket is close to SLA (or on the date) or if past SLA and grab the names
					$subserviceid = $row['subserviceid'];
					$serviceid = $row['serviceid'];
					$catalogueid = $row['catalogueid'];
					$statusid = $row['statusid'];
					$subservicename = "";
					$servicename= "";
					$cataloguename = "";
					$tarraycontactid = "";
					
					$sla = 0;
					$dsla = 0;
					$overdue = false;
					$doverdue = false;
					$closedue = false;
										
					if (!empty($subserviceid)) {
						// Sub-service is not empty so grab the name
						$result2 = mysqli_query($link, "SELECT $nameColumn,sds,contactid FROM tblsubservices WHERE id = '$subserviceid'");
						$row2 = mysqli_fetch_array($result2);
						if (!empty($row2)) 
						{
							$subservicename = $row2[0];
							$sla = $row2[1];
							$dsla = $sla * 2;
							$tarraycontactid = $row2[2];
						}
					}
					
					if (!empty($serviceid)) {
						// Sub-service is not empty so grab the name
						$result2 = mysqli_query($link, "SELECT $nameColumn,sds,contactid FROM tblservices WHERE id = '$serviceid'");
						$row2 = mysqli_fetch_array($result2);
						$servicename = $row2 ? $row2[0] : '';
						if ($sla==0) {
							if ($serviceid==21 || $serviceid==22 || $serviceid==23 || $serviceid==24) {
								$sla = 15;
								$dsla = $sla * 2;
							} else {
								$sla = $row2 ? $row2[1] : 0;
								$dsla = $sla * 2;
							}
						}
						if (empty($tarraycontactid)) {
							$tarraycontactid = $row2 ? $row2[2] : 0;
						}						
					}
					$tarraycontactid = rmt_resolve_responsible_team_id($link, (int) $catalogueid, (int) $serviceid, (int) $subserviceid);
					
					if (!empty($catalogueid)) {
						// Sub-service is not empty so grab the name
						$result2 = mysqli_query($link, "SELECT $nameColumn FROM tblcatalogue WHERE id = '$catalogueid'");
						$row2 = mysqli_fetch_array($result2);
						$cataloguename = $row2 ? $row2[0] : '';
					}
					$subservicename = rmt_request_hierarchy_name($row, 'subservice', $lang, $subservicename);
					$servicename = rmt_request_hierarchy_name($row, 'service', $lang, $servicename);
					$cataloguename = rmt_request_hierarchy_name($row, 'catalogue', $lang, $cataloguename);
					
					// Grab the date it was received
					$slatimer = $row['slatimer'];
					if ($slatimer=="" OR is_null($slatimer)) {
						$datereceived = $row['datereceived'];
					} else {
						$datereceived = $slatimer;
					}
					$ndatereceived = date('Y-m-d H:i:s', strtotime($datereceived . ' +1 day'));
					 
					// Calculate the business days
					$cBdays = calculateSLA($link, $row['requestid'], $ndatereceived);

					$sla2 = $sla - 1;
					// Now check if the SLA is close
					if ($cBdays > $dsla) {
						$doverdue = true;
					}
					if ($cBdays > $sla) {
						$overdue = true;
					}
					if ($cBdays == $sla) {
						$closedue = true;
					}
					
					if ($cBdays >= $sla2) {
						$closedue = true;
					}

					$suppressSlaWarning = rmt_is_resolved_status_id($link, $statusid) || in_array((int)$statusid, [5, 6], true);
					
					// Personal queues use the assigned worker; team queues use responsible team ownership.
					$canViewRow = $showTeamRequests
						? !empty($tarraycontactid) && in_array((string)$tarraycontactid, $teamIds, true)
						: $userid === (int)$row['workerid'];
					if ($canViewRow) {
						$hasVisibleRows = true;

						// Build tags for client-side filter
                    $hasSurveySent = ((int)($row['cssurvey'] ?? 0) > 0);
                    $hasSurveyAnswered = !empty($surveyAnsweredByRequest[(int)$row['id']]);

                    $cardTags = 'status-' . (int)$statusid;
                    if ($hasSurveySent) {
                        $cardTags .= ' survey-sent';
                    }
                    if ($hasSurveyAnswered) {
                        $cardTags .= ' survey-answered';
                    }
						if (!$suppressSlaWarning && ($doverdue || $overdue)) {
                        $cardTags .= ' sla-escalation';
						} elseif (!$suppressSlaWarning && $closedue) {
                        $cardTags .= ' sla-close';
                    }

						if (!$suppressSlaWarning && ($doverdue || $overdue)) {
							$panelClass = 'panel-danger';
							$slaLabel = htmlspecialchars($langFile['indexonly_escalation_required']);
						} elseif (!$suppressSlaWarning && $closedue) {
							$panelClass = 'panel-warning';
							$slaLabel = htmlspecialchars($langFile['indexonly_request_close_sla']);
						} else {
							$panelClass = 'panel-default';
							$slaLabel = '';
						}

						// Status label style
						$statusLabelClasses = [
							1  => 'label-primary',
							2  => 'label-info',
							3  => 'label-warning',
							5  => 'label-success',
							6  => 'label-default',
							7  => 'label-warning',
							10 => 'label-default',
							11 => 'label-default',
							12 => 'label-default',
						];
						$statusLabelClass = $statusLabelClasses[(int)$statusid] ?? 'label-default';

						$workerName = '';
						if (!empty($row['workerid'])) {
							$result2 = mysqli_query($link, "SELECT firstname, lastname FROM tblusers WHERE id = '" . $row['workerid'] . "'");
							$row2 = mysqli_fetch_array($result2);
							if (!empty($row2)) {
								$workerName = htmlspecialchars($row2[0] . ' ' . $row2[1]);
							}
						}
				?>
					<?php
					$result2 = mysqli_query($link, "SELECT $nameColumn FROM tblstatus WHERE id = '$statusid'");
					$row2 = mysqli_fetch_array($result2);
					$statusname = $row2[0] ?? '';

					ob_start();
					?>
					<dl>
						<dt><?= htmlspecialchars($langFile['indexonly_col_client']) ?>:</dt>
						<dd><?= htmlspecialchars($clientname) ?></dd>
						<dt><?= htmlspecialchars($langFile['indexonly_col_service']) ?>:</dt>
						<dd><?= htmlspecialchars($cataloguename) ?> / <?= htmlspecialchars($servicename) ?><?php if (!empty($subservicename)) { echo ' / ' . htmlspecialchars($subservicename); } ?></dd>
						<dt><?= ($_SESSION['lang'] === 'fr') ? 'Date de soumission' : 'Submitted date' ?>:</dt>
						<dd><?= date('Y-m-d', strtotime($row['datereceived'])) ?></dd>
						<?php if (!empty($workerName)): ?>
							<dt><?= ($_SESSION['lang'] === 'fr') ? 'Attribué à' : 'Assigned to' ?>:</dt>
							<dd><?= $workerName ?></dd>
						<?php endif; ?>
					</dl>
					<?php
					$cardBodyHtml = ob_get_clean();

					ob_start();
					?>
						<?php $canEditThisRequest = canEditRequests() && (!$showTeamRequests || $userid === (int)$row['workerid']); ?>
						<?php if ($canEditThisRequest) { ?>
					<a class="btn btn-default btn-block" href="editrequest.php?lang=<?= $_SESSION['lang'] ?>&erid=<?= base64_encode($row['id']) ?>&reqid=<?= urlencode('a11y-' . ($row['requestid'] ?? '')) ?>"><span class="glyphicon glyphicon-pencil" aria-hidden="true"></span><span class="mrgn-lft-sm"><?= htmlspecialchars($langFile['indexonly_edit']) ?></span><span class="wb-inv"> a11y-<?= htmlspecialchars($row['requestid']) ?> <?= htmlspecialchars($langFile['indexonly_request']) ?></span></a>
					<?php } ?>
						<?php if (canDeleteRequests()) { ?>
						<a class="wb-lbx lbx-modal btn btn-danger btn-block" href="includes/delete-request.php?id=<?= $row['id'] ?>"><span class="glyphicon glyphicon-trash" aria-hidden="true"></span><span class="mrgn-lft-sm"><?= htmlspecialchars($langFile['indexonly_delete']) ?></span><span class="wb-inv"> a11y-<?= htmlspecialchars($row['requestid']) ?> <?= htmlspecialchars($langFile['indexonly_request']) ?></span></a>
						<?php } ?>
					<?php if (canCloneRequests()) { ?>
						<a class="btn btn-primary btn-block" href="clonerequest.php?lang=<?= $_SESSION['lang'] ?>&erid=<?= base64_encode($row['id']) ?>&toClose=2"><?= htmlspecialchars($langFile['indexonly_clone']) ?> <span class="wb-inv">a11y-<?= htmlspecialchars($row['requestid']) ?> <?= htmlspecialchars($langFile['indexonly_request']) ?></span></a>
						<a class="btn btn-primary btn-block" href="clonerequest.php?lang=<?= $_SESSION['lang'] ?>&erid=<?= base64_encode($row['id']) ?>&toClose=1"><?= htmlspecialchars($langFile['indexonly_clone_close']) ?> <span class="wb-inv">a11y-<?= htmlspecialchars($row['requestid']) ?> <?= htmlspecialchars($langFile['indexonly_request']) ?></span></a>
					<?php } ?>
					<?php
					$cardFooterHtml = ob_get_clean();

					$requestCard = [
						'tags' => $cardTags,
						'panelClass' => $panelClass,
						'requestUrl' => 'viewrequest.php?lang=' . $_SESSION['lang'] . '&erid=' . base64_encode($row['id']) . '&reqid=' . urlencode('a11y-' . ($row['requestid'] ?? '')),
						'requestCode' => 'a11y-' . ($row['requestid'] ?? ''),
						'title' => (string) ($row['title'] ?? ''),
						'statusPrefix' => $langFile['indexonly_col_status'],
						'statusText' => $statusname,
						'statusLabelClass' => $statusLabelClass,
						'surveyPrefix' => $langFile['indexonly_col_survey'] ?? (($_SESSION['lang'] === 'fr') ? 'Sondage' : 'Survey'),
						'surveySentLabel' => $langFile['indexonly_survey_sent'] ?? (($_SESSION['lang'] === 'fr') ? 'Envoyé' : 'Sent'),
						'surveyAnsweredLabel' => $langFile['indexonly_survey_answered'] ?? (($_SESSION['lang'] === 'fr') ? 'Répondu' : 'Answered'),
						'showSurveySent' => $hasSurveySent,
						'showSurveyAnswered' => $hasSurveyAnswered,
						'slaLabel' => $slaLabel,
						'slaAlertClass' => ($panelClass === 'panel-danger') ? 'alert-danger' : 'alert-warning',
						'bodyHtml' => $cardBodyHtml,
						'footerHtml' => $cardFooterHtml,
					];
					include 'includes/template/request-card.php';
					?>
			<?php
					}
				} ?>
				</div>
				<?php if ($hasVisibleRows): ?>
				<div class="wb-tagfilter-noresult">
					<p><?= ($_SESSION['lang'] === 'fr') ? 'Aucune demande ne correspond au filtre sélectionné.' : 'No requests match the selected filter.' ?></p>
				</div>
				<?php else: ?>
				<p><strong><?= htmlspecialchars($langFile['indexonly_no_requests']) ?></strong></p>
				<?php endif; ?>
			</section>
			
			<?php } else { ?>
			<p><strong><?= htmlspecialchars($langFile['indexonly_no_requests']) ?></strong></p>
			<?php } ?>
			
			<?php include 'includes/template/page-details.php'; ?>
		</main>
		
		<?php include 'includes/template/footer.php'; include 'includes/template/scripts.php'; ?>
		<script>
			(function ($) {
				$(document).on('wb-filtered', '.wb-tagfilter', function () {
					var $filter = $(this);
					var $items = $filter.find('.wb-tagfilter-items [data-wb-tags]');
					var visibleCount = $items.filter(':not(.wb-tgfltr-out):not(.wb-fltr-out)').length;

					$filter.find('#indexonly-filter-info [data-nbitem]').text(visibleCount);
					$filter.find('#indexonly-filter-info [data-total]').text($items.length);
				});
			})(jQuery);
		</script>
	</body>
</html>
<?php
// Close connection
mysqli_close($link);
