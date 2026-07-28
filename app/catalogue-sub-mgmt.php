<?php
/**
 * Catalogue Sub-management - Manage subservices for a service
 */

// Grab MySQL connection (includes session management)
require('sql.php');
/** @var mysqli $link */

// Handle language from query string or session
if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'fr'])) {
    $_SESSION['lang'] = $_GET['lang'];
}
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en';
}

// Load language file
$lang = $_SESSION['lang'];
$langFile = require("lang/{$_SESSION['lang']}.php");

// Grab HTTPS check
require('includes/httpscheck.php');

// Check login
require('includes/loggedincheck.php');

// Check if the user has the right priv's
if (!($_SESSION['is_superuser'] OR $_SESSION['is_admin'])) {
	$redirectPage = ($_SESSION['lang'] === 'fr') ? 'openrequest-fr.php' : 'openrequest-en.php';
	header("location:/{$redirectPage}?status=accessdenied"); 
	exit();
}

// Check if there is a status
if (!empty($_GET['status'])){
	$status = $_GET['status'];
}
else{
	$status = "";
}

// Now first get the ID
$serviceid = $_GET['id'];
$catalogueid = $_GET['cid'];

// Determine which name column to use
$nameColumn = ($_SESSION['lang'] === 'fr') ? 'namefr' : 'nameen';

// Initialize variables
$cataloguename = '';
$servicename = '';

// Grab the catalogue name
$sql = "SELECT * FROM tblcatalogue WHERE id='$catalogueid'";
$result = mysqli_query($link,$sql);
if(mysqli_num_rows($result)>0) {
	while($row = mysqli_fetch_array($result)) {
		$cataloguename = $row[$nameColumn];
	}
}

// Grab the service name
$sql = "SELECT * FROM tblservices WHERE id='$serviceid'";
$result = mysqli_query($link,$sql);
if(mysqli_num_rows($result)>0) {
	while($row = mysqli_fetch_array($result)) {
		$servicename = $row[$nameColumn];
	}
}

// Load config
require_once 'includes/config.php';

// Page-specific metadata
$pageTitle = htmlspecialchars($servicename) . ' - ' . htmlspecialchars($cataloguename) . ' - ' . $langFile['catalogue_sub_mgmt_title_suffix'];
$pageDescription = '';

include 'includes/template/head.php';
include 'includes/template/header.php';
?>
		<main role="main" property="mainContentOfPage" class="container">
			<h1 property="name" id="wb-cont"><?php echo htmlspecialchars($servicename) ?> - <?php echo htmlspecialchars($cataloguename) ?> - <?= htmlspecialchars($langFile['catalogue_sub_mgmt_heading_suffix']) ?></h1>
			
			<?php 
			if ($status == 'success') {
			?>
			<section class="alert alert-success">
				<h2><?= htmlspecialchars($langFile['success_heading']) ?></h2>
				<ul>
					<li><?= htmlspecialchars($langFile['success_message']) ?></li>
				</ul>
			</section>
			<?php
			} elseif ($status == 'failed') {
			?>
			<section class="alert alert-danger">
				<h2><?= htmlspecialchars($langFile['alert_failed_heading']) ?></h2>
				<ul>
					<li><?= htmlspecialchars($langFile['failed_message']) ?></li>
				</ul>
			</section>
			<?php
			}
			?>
			
			<div class="pull-right"><a class="wb-lbx btn btn-primary mrgn-bttm-md" href="includes/add-subservice.php?id=<?php echo $serviceid ?>&cid=<?php echo $catalogueid ?>"><?= htmlspecialchars($langFile['catalogue_sub_mgmt_add_button']) ?></a></div>
			<div class="clearfix"></div>
			
			<?php
			// Construct SQL statement
			$sql = "SELECT * FROM tblsubservices WHERE serviceid = '$serviceid' AND status = '1' ORDER BY {$nameColumn} ASC";
			//echo $sql;
			
			$result = mysqli_query($link,$sql);
			//List it
			if(mysqli_num_rows($result)>0){
			?>
			<table class="wb-tables wb-tables-filter table table-striped table-hover" data-wb-tables='{ "ordering" : true }'>
				<thead>
				<tr>
					<th><?= htmlspecialchars($langFile['catalogue_sub_mgmt_subservice_name_column']) ?></th>
					<th><?= htmlspecialchars($langFile['actions_column']) ?></th>
				</tr>
				</thead>
				<tbody>
				<?php
					while($row = mysqli_fetch_array($result)){
						
					// Get serviceid
					$subserviceid = $row['id'];
				?>
					<tr>
						<td><?php echo htmlspecialchars($row[$nameColumn]); if (!empty($row['sds'])) { echo ' <small class="label label-success">' . (int)$row['sds'] . ' ' . ($_SESSION['lang'] === 'fr' ? 'jour(s)' : 'day(s)') . '</small>'; } ?></td>
						<td>
							<a class="wb-lbx btn btn-primary btn-block" href="includes/edit-subservice.php?id=<?php echo $row['id'];?>&sid=<?php echo $serviceid ?>&cid=<?php echo $catalogueid ?>"><?= htmlspecialchars($langFile['edit_button']) ?><span class="wb-inv"> <?php echo htmlspecialchars($row[$nameColumn]) ?></span> <?= htmlspecialchars($langFile['catalogue_sub_mgmt_subservice_label']) ?></a> <a class="wb-lbx btn btn-primary btn-block" href="includes/delete-subservice.php?id=<?php echo $row['id'];?>&sid=<?php echo $serviceid ?>&cid=<?php echo $catalogueid ?>"><?= htmlspecialchars($langFile['delete_button']) ?><span class="wb-inv"> <?php echo htmlspecialchars($row[$nameColumn]) ?></span> <?= htmlspecialchars($langFile['catalogue_sub_mgmt_subservice_label']) ?></a>
						</td>
					</tr>
				<?php } ?>
				</tbody>
			</table>
			
			<?php } else { ?>
			<p><strong><?= htmlspecialchars($langFile['catalogue_sub_mgmt_no_subservices']) ?></strong></p>
			<?php } ?>
			
			<div class="mrgn-tp-md"><a class="btn btn-primary" href="/catalogue-mgmt.php?id=<?php echo $catalogueid ?>&lang=<?= $_SESSION['lang'] ?>"><?= htmlspecialchars($langFile['catalogue_sub_mgmt_back_service']) ?></a> <a class="btn btn-primary" href="/catalogue.php?lang=<?= $_SESSION['lang'] ?>"><?= htmlspecialchars($langFile['catalogue_sub_mgmt_back_catalogue']) ?></a></div>
			
<?php include 'includes/template/page-details.php'; ?>
		</main>
<?php 
include 'includes/template/footer.php';
include 'includes/template/scripts.php';

// Close connection
mysqli_close($link);
