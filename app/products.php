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

// Security check - conditional includes based on language
if ($_SESSION['lang'] === 'fr') {
    require('includes/loggedincheck.php');
} else {
    require('includes/loggedincheck.php');
}

// Check if the user has the right priv's
if ($_SESSION['is_superuser'] OR $_SESSION['is_admin']) {
} else {
	$redirectPage = ($_SESSION['lang'] === 'fr') ? '/openrequest.php?status=accessdenied&lang=fr' : '/openrequest.php?status=accessdenied&lang=en';
	header("location:$redirectPage"); 
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
		'en' => 'Products',
		'fr' => 'Produits'
	],
	'description' => [
		'en' => 'Manage adaptive technology products and tools',
		'fr' => 'Gérer les produits et outils de technologie adaptée'
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
			<h1 property="name" id="wb-cont"><?= htmlspecialchars($langFile['products_heading']) ?></h1>
			
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
			
			<div class="pull-right"><a class="wb-lbx btn btn-primary mrgn-bttm-md" href="includes/add-product.php?lang=<?= $_SESSION['lang'] ?>"><?= htmlspecialchars($langFile['products_add_button']) ?></a></div>
			<div class="clearfix"></div>
			
			<?php
			// Determine which name column to use based on language
			$nameColumn = ($_SESSION['lang'] === 'fr') ? 'namefr' : 'nameen';
			$orderColumn = ($_SESSION['lang'] === 'fr') ? 'namefr' : 'nameen';
			
			// Construct SQL statement
			$sql = "SELECT * FROM tblproducts WHERE status = '1' ORDER BY $orderColumn ASC";
			//echo $sql;
			
			$result = mysqli_query($link,$sql);
			//List it
			if(mysqli_num_rows($result)>0){
			?>
			<table class="wb-tables wb-tables-filter table table-striped table-hover" data-wb-tables='{ "ordering" : true }'>
				<thead>
					<tr>
						<th><?= htmlspecialchars($langFile['products_name_column']) ?></th>
						<th><?= htmlspecialchars($langFile['products_actions_column']) ?></th>
					</tr>
				</thead>
				<tbody>
				<?php
					while($row = mysqli_fetch_array($result)){
				?>
					<tr>
						<td><?php echo $row[$nameColumn];?></td>				
						<td>
							<a class="wb-lbx btn btn-primary btn-block" href="includes/edit-product.php?id=<?php echo $row['id'];?>&lang=<?= $_SESSION['lang'] ?>"><?= htmlspecialchars($langFile['products_edit_button']) ?><span class="wb-inv"> <?php echo $row[$nameColumn]; ?></span> <?= htmlspecialchars($langFile['products_product_label']) ?></a> <a class="wb-lbx btn btn-primary btn-block" href="includes/delete-product.php?id=<?php echo $row['id'];?>&lang=<?= $_SESSION['lang'] ?>"><?= htmlspecialchars($langFile['products_delete_button']) ?><span class="wb-inv"> <?php echo $row[$nameColumn]; ?></span> <?= htmlspecialchars($langFile['products_product_label']) ?></a>
						</td>
					</tr>
				<?php } ?>
				</tbody>
			</table>
			
			<?php } else { ?>
			<p><strong><?= htmlspecialchars($langFile['products_no_products']) ?></strong></p>
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
