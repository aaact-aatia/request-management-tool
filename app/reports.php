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
		'en' => 'Reports',
		'fr' => 'Rapports'
	],
	'description' => [
		'en' => 'View detailed reports and analytics',
		'fr' => 'Voir les rapports détaillés et les analyses'
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
			<h1 property="name" id="wb-cont"><?= htmlspecialchars($langFile['reports_heading']) ?></h1>
			
			<?php 
			if ($status == 'statuserror') {
			?>
			<section class="alert alert-danger">
				<h2><?= htmlspecialchars($langFile['reports_incomplete_heading']) ?></h2>
				<ul>
					<li><?= htmlspecialchars($langFile['reports_incomplete_message']) ?></li>
				</ul>
			</section>
			<?php
			}
			?>
			
			<h2><?= htmlspecialchars($langFile['reports_generate_heading']) ?></h2>
			
			<form method="post" action="/report-status.php?lang=<?= $_SESSION['lang'] ?>">
			<div class="row">
				<div class="col-xs-6">
					<div class="form-group">
						<label for="sdate"><span class="field-name"><?= htmlspecialchars($langFile['reports_start_date']) ?> <strong>(<?= htmlspecialchars($langFile['required']) ?>)</strong></span></label>
						<input type="date" class="form-control" id="sdate" name="sdate" min="<?php echo date('Y-m-d', strtotime('-6 years'));?>" max="<?php echo date('Y-m-d');?>" required />
					</div>
				</div>
				<div class="col-xs-6">
					<div class="form-group">
						<label for="edate"><span class="field-name"><?= htmlspecialchars($langFile['reports_end_date']) ?> <strong>(<?= htmlspecialchars($langFile['required']) ?>)</strong></span></label>
						<input type="date" class="form-control" id="edate" name="edate" min="<?php echo date('Y-m-d', strtotime('-6 years'));?>" max="<?php echo date('Y-m-d');?>" required />
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-xs-6">
					<div class="form-group">
						<fieldset class="gc-chckbxrdio">
							<legend><?= htmlspecialchars($langFile['reports_catalogue_name']) ?> <strong>(<?= htmlspecialchars($langFile['reports_optional']) ?>)</strong></legend>
							<ul class="list-unstyled list-inline">
							<?php 
							// Determine which name column to use based on language
							$nameColumn = ($_SESSION['lang'] === 'fr') ? 'namefr' : 'nameen';
							$orderColumn = ($_SESSION['lang'] === 'fr') ? 'namefr' : 'nameen';
							
							$sql2 = "SELECT * FROM tblcatalogue WHERE status='1' ORDER BY $orderColumn ASC";
							$result2 = mysqli_query($link,$sql2);	
							while($row2 = mysqli_fetch_array($result2)){
							?>
								<li class="checkbox">
									<input name="catalogueid[]" id="cat-<?php echo $row2['id']; ?>" type="checkbox" value="<?php echo $row2['id']; ?>" />
									<label for="cat-<?php echo $row2['id']; ?>"><?php echo $row2[$nameColumn]; ?></label>
								</li>
							<?php
							}
							?>
							</ul>
						</fieldset>
					</div>
				</div>
			</div>
			<div class="form-group form-buttons">
				<button type="submit" class="btn btn-default"><?= htmlspecialchars($langFile['reports_generate_button']) ?></button>
			</div>
			</form>
			
			<h2><?= htmlspecialchars($langFile['reports_client_survey_heading']) ?></h2>
			
			<div class="pull-left">
				<p><a class="btn btn-primary btn-block" href="client-survey-results.php?lang=<?= $_SESSION['lang'] ?>"><?= htmlspecialchars($langFile['reports_client_survey_view_results']) ?></a> <a class="btn btn-primary btn-block" href="client-survey-pending.php?lang=<?= $_SESSION['lang'] ?>"><?= htmlspecialchars($langFile['reports_client_survey_pending']) ?></a></p>
			</div>
			
			<?php include 'includes/template/page-details.php'; ?>
		</main>
		
		<?php include 'includes/template/footer.php'; include 'includes/template/scripts.php'; ?>
	</body>
</html>
<?php
// Close connection
mysqli_close($link);
?>
