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
if ($_SESSION['atype'] ==1 OR $_SESSION['atype'] ==2) {
} else {
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
		'en' => 'Sources',
		'fr' => 'Sources'
	],
	'description' => [
		'en' => 'Manage request sources and referral channels',
		'fr' => 'Gérer les sources de demande et les canaux de référence'
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
			<h1 property="name" id="wb-cont"><?= htmlspecialchars($langFile['sources_heading']) ?></h1>
			
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
			
			<div class="pull-right"><a class="wb-lbx btn btn-primary mrgn-bttm-md" href="includes/add-source.php"><?= htmlspecialchars($langFile['sources_add_button']) ?></a></div>
			<div class="clearfix"></div>
			
			<?php
			// Construct SQL statement
			$sql = "SELECT * FROM tblsources WHERE status = '1' ORDER BY {$nameColumn} ASC";
			//echo $sql;
			
			$result = mysqli_query($link,$sql);
			//List it
			if(mysqli_num_rows($result)>0){
			?>
			<table class="wb-tables wb-tables-filter table table-striped table-hover" data-wb-tables='{ "ordering" : true }'>
				<thead>
					<tr>
						<th><?= htmlspecialchars($langFile['sources_name_column']) ?></th>
						<?php if ($_SESSION['atype'] == 1) { ?>
						<th><?= htmlspecialchars($langFile['actions_column']) ?></th>
						<?php } ?>
					</tr>
				</thead>
				<tbody>
				<?php
					while($row = mysqli_fetch_array($result)){
				?>
					<tr>
						<td><?php echo htmlspecialchars($row[$nameColumn]);?></td>	
						<?php if ($_SESSION['atype'] == 1) { ?>
						<td>
							<a class="wb-lbx btn btn-primary btn-block" href="includes/edit-source.php?id=<?php echo $row['id'];?>"><?= htmlspecialchars($langFile['edit_button']) ?><span class="wb-inv"> <?php echo htmlspecialchars($row[$nameColumn]) ?></span> <?= htmlspecialchars($langFile['sources_source_label']) ?></a> <a class="wb-lbx btn btn-primary btn-block" href="includes/delete-source.php?id=<?php echo $row['id'];?>"><?= htmlspecialchars($langFile['delete_button']) ?><span class="wb-inv"> <?php echo htmlspecialchars($row[$nameColumn]) ?></span> <?= htmlspecialchars($langFile['sources_source_label']) ?></a>
						</td>
						<?php } ?>
					</tr>
				<?php } ?>
				</tbody>
			</table>
			
			<?php } else { ?>
			<p><strong><?= htmlspecialchars($langFile['sources_no_sources']) ?></strong></p>
			<?php } ?>
			
			<?php include 'includes/template/page-details.php'; ?>
		</main>
		
		<?php include 'includes/template/footer.php'; include 'includes/template/scripts.php'; ?>
	</body>
</html>
<?php
// Close connection
mysqli_close($link);
