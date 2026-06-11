<?php
/**
 * Consolidated Bilingual Teams Management Page
 *
 * This page uses the shared language file system.
 * The language is determined by $_SESSION['lang'].
 *
 * @package RMT
 * @since 2.0.0
 */

// Start session
if (session_status() != PHP_SESSION_ACTIVE)
{
	session_start();
}

// Grab HTTPS check
require('includes/httpscheck.php');

// Grab MySQL connection
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
$langFile = require("lang/{$_SESSION['lang']}.php");

// Security check
if ($_SESSION['lang'] === 'fr') {
	require('includes/loggedincheck.php');
} else {
	require('includes/loggedincheck.php');
}

// Check if the user has the right priv's
if ($_SESSION['atype'] == 1 OR $_SESSION['atype'] == 2) {
} else {
	header("location:/openrequest.php?lang={$_SESSION['lang']}&status=accessdenied"); 
	exit();
}

// Check if there is a status
if (!empty($_GET['status'])){
	$status = $_GET['status'];
}
else{
	$status = "";
}

// =============================================================================
// PAGE FRONTMATTER - Define page metadata
// =============================================================================
$page = [
	'title' => [
		'en' => 'Teams',
		'fr' => 'Équipes'
	],
	'description' => [
		'en' => 'Manage teams and escalation information',
		'fr' => 'Gérer les équipes et les informations d\'escalade'
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
			<h1 property="name" id="wb-cont"><?= htmlspecialchars($langFile['teams_heading']) ?></h1>
			
			<?php 
			if ($status == 'success') {
			?>
			<section class="alert alert-success">
				<h2><?= htmlspecialchars($langFile['success_heading']) ?></h2>
				<ul>
					<li><?= htmlspecialchars($langFile['teams_success_message']) ?></li>
				</ul>
			</section>
			<?php
			} elseif ($status == 'failed') {
			?>
			<section class="alert alert-danger">
				<h2><?= htmlspecialchars($langFile['failed_heading']) ?></h2>
				<ul>
					<li><?= htmlspecialchars($langFile['teams_failed_message']) ?></li>
				</ul>
			</section>
			<?php
			}
			?>
			
			<div class="pull-right"><a class="wb-lbx btn btn-primary mrgn-bttm-md" href="includes/add-teams.php"><?= htmlspecialchars($langFile['teams_add_button']) ?></a></div>
			<div class="clearfix"></div>
			
			<?php
			// Determine which field to use for team name based on language
			$teamNameField = ($_SESSION['lang'] === 'fr') ? 'namefr' : 'nameen';
			
			// Construct SQL statement
			$sql = "SELECT * FROM tblteams ORDER BY $teamNameField ASC";
			//echo $sql;
			
			$result = mysqli_query($link,$sql);
			//List it
			if(mysqli_num_rows($result)>0){
			?>
			<table class="wb-tables wb-tables-filter table table-striped table-hover" data-wb-tables='{ "ordering" : true }'>
				<thead>
					<tr>
						<th><?= htmlspecialchars($langFile['teams_name_column']) ?></th>
						<th><?= htmlspecialchars($langFile['teams_email_column']) ?></th>
						<?php if ($_SESSION['atype'] == 1) { ?>
						<th><?= htmlspecialchars($langFile['teams_actions']) ?></th>
						<?php } ?>
					</tr>
				</thead>
				<tbody>
				<?php
					while($row = mysqli_fetch_array($result)){
				?>
					<tr>
						<td><?php echo $row[$teamNameField];?></td>
						<td><?php echo $row['email'];?></td>
						<?php if ($_SESSION['atype'] == 1) { ?>
						<td>
							<a class="wb-lbx btn btn-primary btn-block" href="includes/edit-teams.php?id=<?php echo $row['id'];?>&lang=<?php echo $lang;?>"><?= htmlspecialchars($langFile['teams_edit']) ?><span class="wb-inv"> <?php echo $row[$teamNameField] ?></span> <?= htmlspecialchars($langFile['teams_team_label']) ?></a><?php if ($_SESSION['atype']=='1') {?> <a class="wb-lbx btn btn-primary btn-block" href="includes/delete-teams.php?id=<?php echo $row['id'];?>&lang=<?php echo $lang;?>"><?= htmlspecialchars($langFile['teams_delete']) ?><span class="wb-inv"> <?php echo $row[$teamNameField] ?></span> <?= htmlspecialchars($langFile['teams_team_label']) ?></a><?php } ?>
						</td>
						<?php } ?>
					</tr>
				<?php } ?>
			</tbody>
			</table>
			
			<?php } else { ?>
			<p><strong><?= htmlspecialchars($langFile['teams_no_teams']) ?></strong></p>
			<?php } ?>
			
			<?php include 'includes/template/page-details.php'; ?>
		</main>
		
		<?php include 'includes/template/footer.php'; include 'includes/template/scripts.php'; ?>
	</body>
</html>
<?php
// Close connection
mysqli_close($link);
?>
