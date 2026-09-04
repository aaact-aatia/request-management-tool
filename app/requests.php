<?php
/**
 * Consolidated Bilingual Dashboard - Requests
 *
 * This page is the consolidated requests dashboard
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
$effectiveAtype = (int)($_SESSION['atype'] ?? 0);
$isEmployeeAccount = $effectiveAtype === 5;
$isTeamScopedAccount = in_array($effectiveAtype, [3, 4], true);
$isAdministrativeAccount = !isRoleTestMode() && (
	!empty($_SESSION['is_superuser']) || !empty($_SESSION['is_admin']) || in_array($effectiveAtype, [1, 2], true)
);
$showOtherTeamRequests = $isTeamScopedAccount && isset($_GET['show_other_team']) && $_GET['show_other_team'] === '1';
$showTeamRequests = $isEmployeeAccount && isset($_GET['show_team']) && $_GET['show_team'] === '1';
$showClosedRequests = ($isTeamScopedAccount || $isAdministrativeAccount || $isEmployeeAccount)
	&& isset($_GET['show_closed']) && $_GET['show_closed'] === '1';
$selectedStatusValue = $_GET['status_filter_server'] ?? ($_GET['status_filter'] ?? '');
$selectedStatus = 0;
if (preg_match('/^status-(\d+)$/', (string)$selectedStatusValue, $statusMatch)) {
	$selectedStatus = (int)$statusMatch[1];
} elseif (ctype_digit((string)$selectedStatusValue)) {
	$selectedStatus = (int)$selectedStatusValue;
}
$statusIsClosed = in_array($selectedStatus, [4, 5, 6], true);
$showUnassigned = $selectedStatusValue === 'unassigned';
$selectedCatalogue = isset($_GET['catalogue_filter']) ? (int)$_GET['catalogue_filter'] : 0;
$priorityFilter = isset($_GET['priority_filter']) && in_array($_GET['priority_filter'], ['survey_sent', 'survey_answered', 'escalation', 'close_to_sla'], true)
	? $_GET['priority_filter']
	: '';
$sort = $_GET['sort'] ?? 'submitted_desc';
$sortOptions = [
	'submitted_desc' => 'requestid DESC',
	'submitted_asc' => 'requestid ASC',
	'updated_desc' => 'dateupdated DESC, requestid DESC',
	'updated_asc' => 'dateupdated ASC, requestid ASC',
];
if (!isset($sortOptions[$sort])) {
	$sort = 'submitted_desc';
}
$teamIds = [];
if (($isTeamScopedAccount && !$showOtherTeamRequests) || $isEmployeeAccount) {
	$teamIds = $isTeamScopedAccount ? getEffectiveTeamIds($link) : getEffectiveEmployeeTeamIds($link);
}
$statusOptionsResult = mysqli_query($link, "SELECT id, $nameColumn FROM tblstatus WHERE status = 1 ORDER BY id");
$catalogueScopeFilter = '';
if (($isTeamScopedAccount && !$showOtherTeamRequests) || $isEmployeeAccount) {
	$teamIdsForCatalogue = array_values(array_filter(array_map('intval', $teamIds), static function ($teamId) {
		return $teamId > 0;
	}));
	$catalogueScopeFilter = empty($teamIdsForCatalogue)
		? ' AND 1 = 0'
		: " AND (contactid IN (" . implode(',', $teamIdsForCatalogue) . ") OR EXISTS (SELECT 1 FROM tblservices s LEFT JOIN tblsubservices ss ON ss.serviceid = s.id AND ss.status = 1 WHERE s.catalogueid = tblcatalogue.id AND COALESCE(NULLIF(ss.contactid, 0), NULLIF(s.contactid, 0), tblcatalogue.contactid) IN (" . implode(',', $teamIdsForCatalogue) . ")))";
}
$catalogueOptionsResult = mysqli_query($link, "SELECT id, $nameColumn FROM tblcatalogue WHERE status = 1$catalogueScopeFilter ORDER BY $nameColumn");

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
			<section class="provisional wb-tagfilter wb-filter" data-wb-filter='{"selector": "[data-wb-tags]", "section": ".wb-tagfilter-items", "uiTemplate": "#rmt-search-filter"}'>
				<h2 class="wb-inv"><?= htmlspecialchars($langFile['requests_filter_options'] ?? 'Filter options') ?></h2>
			<form id="requests-filter-form" method="get" action="requests.php" class="mrgn-bttm-lg">
				<input type="hidden" name="lang" value="<?= htmlspecialchars($_SESSION['lang']) ?>">
				<input type="hidden" name="status_filter_server" value="<?= htmlspecialchars($selectedStatusValue) ?>">
				<div class="row">
					<div class="col-md-6"><div class="form-group"><label for="status-filter"><?= htmlspecialchars($langFile['requests_filter_status']) ?></label><select class="full-width wb-tagfilter-ctrl form-control" id="status-filter" name="status-filter"><option value=""><?= htmlspecialchars($langFile['requests_filter_all']) ?></option><option value="unassigned" <?= $showUnassigned ? 'selected' : '' ?>><?= htmlspecialchars($langFile['requests_filter_unassigned']) ?></option><?php while ($statusOption = mysqli_fetch_assoc($statusOptionsResult)): ?><option value="status-<?= (int)$statusOption['id'] ?>" <?= $selectedStatus === (int)$statusOption['id'] ? 'selected' : '' ?>><?= htmlspecialchars($statusOption[$nameColumn]) ?></option><?php endwhile; ?></select></div></div>
					<div class="col-md-6"><div class="form-group"><label for="catalogue-filter"><?= htmlspecialchars($langFile['requests_filter_service']) ?></label><select class="full-width wb-tagfilter-ctrl form-control" id="catalogue-filter" name="catalogue-filter"><option value=""><?= htmlspecialchars($langFile['requests_filter_all']) ?></option><?php while ($catalogueOption = mysqli_fetch_assoc($catalogueOptionsResult)): ?><option value="cat-<?= (int)$catalogueOption['id'] ?>"><?= htmlspecialchars($catalogueOption[$nameColumn]) ?></option><?php endwhile; ?></select></div></div>
				</div>
				<div class="row">
					<div class="col-md-4"><fieldset class="gc-chckbxrdio"><legend class="mrgn-bttm-0"><?= htmlspecialchars($langFile['indexonly_additional_requests']) ?></legend><ul class="list-unstyled lst-spcd-2"><?php if ($isEmployeeAccount): ?><li class="checkbox"><input type="checkbox" id="show-team-requests" name="show_team" value="1" onchange="this.form.submit()" <?= $showTeamRequests ? 'checked' : '' ?>><label for="show-team-requests"><?= htmlspecialchars($langFile['indexonly_show_full_team']) ?></label></li><?php elseif ($isTeamScopedAccount): ?><li class="checkbox"><input type="checkbox" id="show-other-team-requests" name="show_other_team" value="1" onchange="this.form.submit()" <?= $showOtherTeamRequests ? 'checked' : '' ?>><label for="show-other-team-requests"><?= htmlspecialchars($langFile['show_other_team_requests'] ?? $langFile['indexonly_show_full_team']) ?></label></li><?php endif; ?><?php if ($isEmployeeAccount || $isTeamScopedAccount || $isAdministrativeAccount): ?><li class="checkbox"><input type="checkbox" id="show-closed-requests" name="show_closed" value="1" onchange="this.form.submit()" <?= ($showClosedRequests || $statusIsClosed) ? 'checked' : '' ?>><label for="show-closed-requests"><?= htmlspecialchars($langFile['indexonly_show_closed']) ?></label></li><?php endif; ?></ul></fieldset></div>
					<?php if (!$isEmployeeAccount): ?>
					<div class="col-md-4"><fieldset class="gc-chckbxrdio"><legend class="mrgn-bttm-0"><?= htmlspecialchars($langFile['requests_filter_survey']) ?></legend><ul class="list-unstyled lst-spcd-2"><li class="checkbox"><input type="checkbox" id="survey-sent-filter" name="survey-filter" class="wb-tagfilter-ctrl" value="survey-sent"><label for="survey-sent-filter"><?= htmlspecialchars($langFile['requests_filter_survey_sent']) ?></label></li><li class="checkbox"><input type="checkbox" id="survey-answered-filter" name="survey-filter" class="wb-tagfilter-ctrl" value="survey-answered"><label for="survey-answered-filter"><?= htmlspecialchars($langFile['requests_filter_survey_answered']) ?></label></li></ul></fieldset></div>
					<div class="col-md-4"><fieldset class="gc-chckbxrdio"><legend class="mrgn-bttm-0"><?= htmlspecialchars($langFile['requests_filter_priority']) ?></legend><ul class="list-unstyled lst-spcd-2"><li class="checkbox"><input type="checkbox" id="sla-escalation-filter" name="priority-filter" class="wb-tagfilter-ctrl" value="sla-escalation"><label for="sla-escalation-filter"><?= htmlspecialchars($langFile['requests_filter_escalation']) ?></label></li><li class="checkbox"><input type="checkbox" id="sla-close-filter" name="priority-filter" class="wb-tagfilter-ctrl" value="sla-close"><label for="sla-close-filter"><?= htmlspecialchars($langFile['indexonly_request_close_sla']) ?></label></li></ul></fieldset></div>
					<?php endif; ?>
				</div>
				<div class="row requests-search-filter"><div class="col-md-12"><div class="form-group"><div class="input-group"><label for="rmt-search" class="input-group-addon"><?= htmlspecialchars($langFile['search_label'] ?? $langFile['requests_filter_status']) ?></label><input type="search" class="form-control" id="rmt-search"></div></div></div></div>
			</form>
			<div class="row requests-sort-filter"><div class="col-md-5 col-sm-7"><div class="form-group"><form method="get" action="requests.php" class="form-inline"><input type="hidden" name="lang" value="<?= htmlspecialchars($_SESSION['lang']) ?>"><input type="hidden" name="status_filter_server" value="<?= htmlspecialchars($selectedStatusValue) ?>"><?php if ($showClosedRequests): ?><input type="hidden" name="show_closed" value="1"><?php endif; ?><?php if ($showTeamRequests): ?><input type="hidden" name="show_team" value="1"><?php endif; ?><?php if ($showOtherTeamRequests): ?><input type="hidden" name="show_other_team" value="1"><?php endif; ?><label for="sort-filter" class="mrgn-rght-sm"><?= htmlspecialchars($langFile['requests_sort_by']) ?>:</label><select id="sort-filter" name="sort" class="form-control" onchange="this.form.submit()"><option value="submitted_desc" <?= $sort === 'submitted_desc' ? 'selected' : '' ?>><?= htmlspecialchars($langFile['requests_sort_submitted_newest']) ?></option><option value="submitted_asc" <?= $sort === 'submitted_asc' ? 'selected' : '' ?>><?= htmlspecialchars($langFile['requests_sort_submitted_oldest']) ?></option><option value="updated_desc" <?= $sort === 'updated_desc' ? 'selected' : '' ?>><?= htmlspecialchars($langFile['requests_sort_updated_newest']) ?></option><option value="updated_asc" <?= $sort === 'updated_asc' ? 'selected' : '' ?>><?= htmlspecialchars($langFile['requests_sort_updated_oldest']) ?></option></select></form></div></div></div>
			<div id="rmt-search-filter" class="row">
				<div class="col-sm-12"><p id="rmt-search-filter-info" class="wb-fltr-info mrgn-bttm-sm"><span data-nbitem>0</span> <?= htmlspecialchars($langFile['indexonly_results_out_of'] ?? 'results out of') ?> <span data-total>0</span></p></div>
			</div>
			<?php
			$userid = getEffectiveEmployeeUserId($link);
			// Construct SQL statement
			$workerFilter = $isEmployeeAccount && !$showTeamRequests ? " AND workerid = '$userid'" : '';
			$assignmentFilter = $showUnassigned ? " AND workerid = '0'" : '';
			$statusFilter = $selectedStatus > 0
				? " AND statusid = '$selectedStatus'"
				: ($showClosedRequests ? '' : " AND statusid NOT IN ('4', '5', '6')");
			$catalogueFilter = $selectedCatalogue > 0 ? " AND catalogueid = '$selectedCatalogue'" : '';
			$prioritySqlFilter = $priorityFilter === 'survey_sent'
				? ' AND cssurvey > 0'
				: ($priorityFilter === 'survey_answered' ? ' AND EXISTS (SELECT 1 FROM tblcss WHERE tblcss.requestid = tbltriage.id AND tblcss.status = 1)' : '');
			$sql = "SELECT * FROM tbltriage WHERE status = '1'$workerFilter$assignmentFilter$statusFilter$catalogueFilter$prioritySqlFilter ORDER BY {$sortOptions[$sort]}";
			
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
							if ($priorityFilter === 'escalation' && ($suppressSlaWarning || (!$doverdue && !$overdue))) {
								continue;
							}
							if ($priorityFilter === 'close_to_sla' && ($suppressSlaWarning || !$closedue || $doverdue || $overdue)) {
						continue;
					}
					
					// Personal queues use the assigned worker; team queues use responsible team ownership.
					$canViewRow = $isAdministrativeAccount
						|| ($isTeamScopedAccount && ($showOtherTeamRequests || (!empty($tarraycontactid) && in_array((string)$tarraycontactid, $teamIds, true))))
						|| ($isEmployeeAccount && ($showTeamRequests
							? !empty($tarraycontactid) && in_array((string)$tarraycontactid, $teamIds, true)
							: $userid === (int)$row['workerid']));
					if ($canViewRow) {
						$hasVisibleRows = true;

						// Build tags for client-side filter
                    $hasSurveySent = ((int)($row['cssurvey'] ?? 0) > 0);
                    $hasSurveyAnswered = !empty($surveyAnsweredByRequest[(int)$row['id']]);

                    $cardTags = 'status-' . (int)$statusid;
					if (!empty($catalogueid)) {
						$cardTags .= ' cat-' . (int)$catalogueid;
					}
					if (empty($row['workerid'])) {
						$cardTags .= ' unassigned';
					}
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
						$requestServiceNames = array_filter([$cataloguename, $servicename, $subservicename], static fn($name) => trim((string) $name) !== '');
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
						<dd><?= htmlspecialchars(implode(' / ', $requestServiceNames)) ?></dd>
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
				<div class="wb-tagfilter-noresult requests-noresult">
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
				function applyRequestFilters() {
					var $filter = $('.wb-tagfilter');
					var $items = $filter.find('.wb-tagfilter-items [data-wb-tags]');
					var $status = $('#status-filter');
					var $catalogue = $('#catalogue-filter');
					var search = $('#rmt-search').val().toLowerCase();
					var selectedGroups = {};

					$filter.find('.wb-tagfilter-ctrl:checked').each(function () {
						var group = $(this).attr('name') || this.id;
						(selectedGroups[group] = selectedGroups[group] || []).push(this.value);
					});
					var statusValue = $status.val();
					if (statusValue) selectedGroups.status = [statusValue];
					if ($catalogue.val()) selectedGroups.catalogue = [$catalogue.val()];

					$items.each(function () {
						var $item = $(this);
						var tags = String($item.data('wb-tags') || '').split(/\s+/);
						var textMatches = !search || $item.text().toLowerCase().indexOf(search) !== -1;
						var optionMatches = Object.keys(selectedGroups).every(function (group) {
							return selectedGroups[group].some(function (tag) { return tags.indexOf(tag) !== -1; });
						});
						$item.removeClass('wb-tgfltr-out wb-fltr-out').toggle(textMatches && optionMatches);
					});

					var visibleCount = $items.filter(':visible').length;
					var optionFilterActive = Object.keys(selectedGroups).length > 0;
					$filter.find('#rmt-search-filter-info [data-nbitem]').text(visibleCount);
					$filter.find('#rmt-search-filter-info [data-total]').text($items.length);
					$filter.find('.requests-search-filter, .requests-sort-filter').toggle($items.length > 0 && !(optionFilterActive && visibleCount === 0));
					$filter.find('.requests-noresult').toggle($items.length > 0 && visibleCount === 0);
				}

				$(document).on('change', '.wb-tagfilter-ctrl', applyRequestFilters);
				$(document).on('input', '#rmt-search', applyRequestFilters);
				$(document).on('wb-filtered', '.wb-tagfilter', applyRequestFilters);
				$('#status-filter').on('change', function () {
					if (/^status-[456]$/.test(this.value)) {
						this.form.status_filter_server.value = this.value;
						this.form.submit();
					}
				});
				$(applyRequestFilters);
			})(jQuery);
		</script>
	</body>
</html>
<?php
// Close connection
mysqli_close($link);
