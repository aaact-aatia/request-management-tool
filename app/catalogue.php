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
$langFile = require("lang/{$_SESSION['lang']}.php");

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

// Determine which name column to use
$nameColumn = ($_SESSION['lang'] === 'fr') ? 'namefr' : 'nameen';

// =============================================================================
// PAGE FRONTMATTER - Define page metadata
// =============================================================================
$page = [
	'title' => [
		'en' => 'Catalogue',
		'fr' => 'Catalogue'
	],
	'description' => [
		'en' => 'Manage service catalogue and request categories',
		'fr' => 'Gérer le catalogue de services et les catégories de demandes'
	]
];

// Store language code for templates (header.php needs $lang)
$lang = $_SESSION['lang'];

// Extract values for current language
$pageTitle = $page['title'][$lang];
$pageDescription = $page['description'][$lang];

// Include template head
include 'includes/template/head.php';
?>
	<?php include 'includes/template/header.php'; ?>
		<main role="main" property="mainContentOfPage" class="container">
			<h1 property="name" id="wb-cont"><?= htmlspecialchars($langFile['catalogue_heading']) ?></h1>
			
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
			
			<div class="pull-right"><a class="wb-lbx btn btn-primary mrgn-bttm-md" href="includes/add-catalogue.php"><?= htmlspecialchars($langFile['catalogue_add_button']) ?></a></div>
			<div class="clearfix"></div>
			
			<?php
			// Construct SQL statement
			$sql = "SELECT * FROM tblcatalogue WHERE status = '1' ORDER BY {$nameColumn} ASC";
			//echo $sql;
			
			$result = mysqli_query($link,$sql);
			//List it
			if(mysqli_num_rows($result)>0){
			?>
			<table class="wb-tables wb-tables-filter table table-striped table-hover" data-wb-tables='{ "ordering" : true }'>
				<thead>
					<tr>
						<th><?= htmlspecialchars($langFile['catalogue_name_column']) ?></th>
						<th><?= htmlspecialchars($langFile['catalogue_services_column']) ?></th>
						<th><?= htmlspecialchars($langFile['actions_column']) ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
						while($row = mysqli_fetch_array($result)){
							
						// Get catalogueid
						$catalogueid = $row['id'];
					?>
						<tr>
							<td><?php echo htmlspecialchars($row[$nameColumn]);?><br /><?php if ($row['survey']==0) { echo '<span class="glyphicon glyphicon-warning-sign"></span> ' . htmlspecialchars($langFile['catalogue_surveys_not_sent']); } else { echo '<span class="glyphicon glyphicon-ok"></span> ' . htmlspecialchars($langFile['catalogue_surveys_sent']); }?></td>
							<td>
							<?php 
							// Grab the services related to this catalogue item
							$sql2 = "SELECT * FROM tblservices WHERE catalogueid = '$catalogueid' AND status = '1' ORDER BY {$nameColumn} ASC";
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
								echo htmlspecialchars($langFile['catalogue_no_services']);
							} ?>
							</td>					
							<td>
								<a class="wb-lbx btn btn-primary btn-block" href="includes/edit-catalogue.php?id=<?php echo $row['id'];?>"><?= htmlspecialchars($langFile['edit_button']) ?><span class="wb-inv"> <?php echo htmlspecialchars($row[$nameColumn]) ?></span> <?= htmlspecialchars($langFile['catalogue_item_label']) ?></a> <a class="wb-lbx btn btn-primary btn-block" href="includes/delete-catalogue.php?id=<?php echo $row['id'];?>"><?= htmlspecialchars($langFile['delete_button']) ?><span class="wb-inv"> <?php echo htmlspecialchars($row[$nameColumn]) ?></span> <?= htmlspecialchars($langFile['catalogue_item_label']) ?></a> <a class="btn btn-primary btn-block" href="/catalogue-mgmt.php?id=<?php echo $catalogueid ?>&lang=<?= $_SESSION['lang'] ?>"><?= htmlspecialchars($langFile['catalogue_view_services']) ?></a>
							</td>
						</tr>
					<?php } ?>
				</tbody>
			</table>
			
			<?php } else { ?>
			<p><strong><?= htmlspecialchars($langFile['catalogue_no_items']) ?></strong></p>
			<?php } ?>
			
			<?php include 'includes/template/page-details.php'; ?>
		</main>
		
		<?php include 'includes/template/footer.php'; include 'includes/template/scripts.php'; ?>
	</body>
</html>
<?php
// Close connection
mysqli_close($link);
