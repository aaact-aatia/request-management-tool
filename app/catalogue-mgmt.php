<?php
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
$lang = require("lang/{$_SESSION['lang']}.php");

// Grab HTTPS check
require('includes/httpscheck.php');

// Conditional includes
if ($_SESSION['lang'] === 'fr') {
    require('includes/loggedincheck.php');
} else {
    require('includes/loggedincheck.php');
}

// Check if the user has the right priv's
if ($_SESSION['atype'] != 1) {
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
$catalogueid = $_GET['id'];

// Determine which name column to use
$nameColumn = ($_SESSION['lang'] === 'fr') ? 'namefr' : 'nameen';

// Grab the catalogue name
$sql = "SELECT * FROM tblcatalogue WHERE id='$catalogueid'";
$result = mysqli_query($link,$sql);
if(mysqli_num_rows($result)>0) {
	while($row = mysqli_fetch_array($result)) {
		$cataloguename = $row[$nameColumn];
	}
}

$pageTitle = $cataloguename . ' - ' . $lang['catalogue_mgmt_heading_suffix'];
$pageDescription = '';

include 'includes/template/head.php';
?>
	<?php include 'includes/template/header.php'; ?>
		<main role="main" property="mainContentOfPage" class="container">
			<h1 property="name" id="wb-cont"><?php echo htmlspecialchars($cataloguename) ?> - <?= htmlspecialchars($lang['catalogue_mgmt_heading_suffix']) ?></h1>
			
			<?php 
			if ($status == 'success') {
			?>
			<section class="alert alert-success">
				<h2><?= htmlspecialchars($lang['success_heading']) ?></h2>
				<ul>
					<li><?= htmlspecialchars($lang['success_message']) ?></li>
				</ul>
			</section>
			<?php
			} elseif ($status == 'failed') {
			?>
			<section class="alert alert-danger">
				<h2><?= htmlspecialchars($lang['alert_failed_heading']) ?></h2>
				<ul>
					<li><?= htmlspecialchars($lang['failed_message']) ?></li>
				</ul>
			</section>
			<?php
			}
			?>
			
			<div class="pull-right"><a class="wb-lbx btn btn-primary mrgn-bttm-md" href="includes/add-service.php?id=<?php echo $catalogueid ?>"><?= htmlspecialchars($lang['catalogue_mgmt_add_button']) ?></a></div>
			<div class="clearfix"></div>
			
			<?php
			// Construct SQL statement
			$sql = "SELECT * FROM tblservices WHERE catalogueid = '$catalogueid' AND status = '1' ORDER BY {$nameColumn} ASC";
			//echo $sql;
			
			$result = mysqli_query($link,$sql);
			//List it
			if(mysqli_num_rows($result)>0){
			?>
			<table class="wb-tables wb-tables-filter table table-striped table-hover" data-wb-tables='{ "ordering" : true }'>
				<thead>
					<tr>
						<th><?= htmlspecialchars($lang['catalogue_mgmt_service_name_column']) ?></th>
						<th><?= htmlspecialchars($lang['catalogue_mgmt_subservices_column']) ?></th>
						<th><?= htmlspecialchars($lang['actions_column']) ?></th>
					</tr>
				</thead>
				<tbody>
				<?php
					while($row = mysqli_fetch_array($result)){
						
					// Get serviceid
					$serviceid = $row['id'];
				?>
					<tr>
						<td><?php echo htmlspecialchars($row[$nameColumn]); if (!empty($row['sds'])) { echo ' <small class="label label-success">' . (int)$row['sds'] . ' ' . ($_SESSION['lang'] === 'fr' ? 'jour(s)' : 'day(s)') . '</small>'; } ?></td>
						<td>
						<?php 
						// Grab the services related to this catalogue item
						$sql2 = "SELECT * FROM tblsubservices WHERE serviceid = '$serviceid' AND status = '1' ORDER BY {$nameColumn} ASC";
						$result2 = mysqli_query($link,$sql2);	
						if(mysqli_num_rows($result2)>0){
						?>
							<ul>
						<?php
							while($row2 = mysqli_fetch_array($result2)){
						?>
								<li><?php echo htmlspecialchars($row2[$nameColumn]); if (!empty($row2['sds'])) { echo ' <small class="label label-success">' . (int)$row2['sds'] . ' ' . ($_SESSION['lang'] === 'fr' ? 'jour(s)' : 'day(s)') . '</small>'; } ?></li>
						<?php } ?>
							</ul>
						<?php } else {
							echo htmlspecialchars($lang['catalogue_mgmt_no_subservices']);
						} ?>
						</td>					
						<td>
							<a class="wb-lbx btn btn-primary btn-block" href="includes/edit-service.php?id=<?php echo $row['id'];?>&cid=<?php echo $catalogueid ?>"><?= htmlspecialchars($lang['edit_button']) ?><span class="wb-inv"> <?php echo htmlspecialchars($row[$nameColumn]) ?></span> <?= htmlspecialchars($lang['catalogue_mgmt_service_label']) ?></a> <a class="wb-lbx btn btn-primary btn-block" href="includes/delete-service.php?id=<?php echo $row['id'];?>&cid=<?php echo $catalogueid ?>"><?= htmlspecialchars($lang['delete_button']) ?><span class="wb-inv"> <?php echo htmlspecialchars($row[$nameColumn]) ?></span> <?= htmlspecialchars($lang['catalogue_mgmt_service_label']) ?></a> <a class="btn btn-primary btn-block" href="/catalogue-sub-mgmt.php?id=<?php echo $serviceid ?>&cid=<?php echo $catalogueid ?>&lang=<?= $_SESSION['lang'] ?>"><?= htmlspecialchars($lang['catalogue_mgmt_view_subservices']) ?></a>
						</td>
					</tr>
				<?php } ?>
				</tbody>
			</table>
			
			<?php } else { ?>
			<p><strong><?= htmlspecialchars($lang['catalogue_mgmt_no_services']) ?></strong></p>
			<?php } ?>
			
			<div class="mrgn-tp-md"><a class="btn btn-primary" href="/catalogue.php?lang=<?= $_SESSION['lang'] ?>"><?= htmlspecialchars($lang['catalogue_mgmt_back_button']) ?></a></div>
			<?php include 'includes/template/page-details.php'; ?>
		</main>
		<?php include 'includes/template/footer.php'; ?>
		<?php include 'includes/template/scripts.php'; ?>
	</body>
</html>
<?php
// Close connection
mysqli_close($link);
