<?php
// Get URL for language toggle
$newtoggledisabled = false;
$currenturl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
if ((strpos($currenturl, 'openrequest2-fr.php')) !== false) {
    // No toggle on this page
	$newtoggledisabled = true;
} else {
	// Handle new bilingual pages with ?lang=fr parameter
	if ((strpos($currenturl, '?lang=fr')) !== false) {
		$newtoggle = str_replace("?lang=fr","?lang=en",$currenturl);
	} elseif ((strpos($currenturl, '&lang=fr')) !== false) {
		$newtoggle = str_replace("&lang=fr","&lang=en",$currenturl);
	} elseif ((strpos($currenturl, 'lang=')) === false && (strpos($currenturl, '.php')) !== false) {
		// No lang parameter - add it
		if ((strpos($currenturl, '?')) !== false) {
			$newtoggle = $currenturl . "&lang=en";
		} else {
			$newtoggle = $currenturl . "?lang=en";
		}
	} else {
		// Legacy pages with -fr.php
		$newtoggle = str_replace("-fr.php","-en.php",$currenturl);
	}
}
if(empty($_SESSION['pid'])){
?>
<!-- Write closure template -->
		<script>
			var defTop = document.getElementById("def-top");
			defTop.outerHTML = wet.builder.appTop({
				"appName": [{"text": "Outil de gestion des demandes (OGD)", "href": "/openrequest.php?lang=fr"}],
				"signIn": [{"href": "/signin.php?lang=fr"}],
				<?php if ($newtoggledisabled==false) {  ?>
				"lngLinks": [{"lang": "en", "href": "<?php echo $newtoggle ?>", "text": "English"}],
				<?php } ?>
				"menuPath": "/includes/appmenu.php",
				"breadcrumbs": [{
					"title": "Accessibilité, adaptation et technologie informatique adaptée (AATIA)",
					"href": "https://www.canada.ca/fr/services-partages/services/employes-accessibilite/programme-aatia.html"
				}, {
					"title": "Outil de gestion des demandes",
					"href": "/openrequest-fr.php"
				}]
			});
			$('#def-top').trigger('wb-init.wb');
		</script>
<?php
} else {
	// Set a banner if environment is development
	if ($cenvironment==1) {
?>
		<div style="padding:15px;position:fixed;bottom:0;right:0;width: 35%;z-index:9999999;background-color:#FFF;border-style: solid;">
			 <div style="padding: 5px;">
				<div class="alert alert-danger" style="margin:0 auto;">
					<h3>Environnement de développement<h3>
					<p>Vous consultez actuellement l'environnement de développement, aucune modification ne sera apportée en production. Utilisez les paramètres du compte pour revenir à la production.</p>
				</div>
			</div>
		</div>

		<!--<div class="alert alert-danger">
			<h3>Development environment<h3>
			<p>You are currently viewing the devleopment environment. No changes will be made in production. To switch back to production environment change the settings in your account settings page.</p>
		</div>-->
<?php } ?>

<!-- Write closure template -->
		<script>
			var defTop = document.getElementById("def-top");
			defTop.outerHTML = wet.builder.appTop({
				"appName": [{"text": "Outil de gestion des demandes (OGD)", "href": "/openrequest.php?lang=fr"}],
				"signOut": [{"href": "/signout.php?lang=fr"}],
				"appSettings": [{"href": "/settings.php?lang=fr"}],
				<?php if ($newtoggledisabled==false) {  ?>
				"lngLinks": [{"lang": "en", "href": "<?php echo $newtoggle ?>", "text": "English"}],
				<?php } ?>
				"menuPath": "/includes/appmenu.php",
				"breadcrumbs": [{
					"title": "Accessibilité, adaptation et technologie informatique adaptée (AATIA)",
					"href": "https://www.canada.ca/fr/services-partages/services/employes-accessibilite/programme-aatia.html"
				}, {
					"title": "Outil de gestion des demandes",
					"href": "/openrequest.php?lang=fr"
				}]
			});
		</script>
		<?php if (!empty($_SESSION['pid'])) { ?>
		<div class="container">
			<p>
				<strong>Vous êtes connecté en tant que :</strong> 
				<?php echo htmlspecialchars($_SESSION['firstname'] . ' (' . $_SESSION['email'] . ')'); ?>
				<?php 
				if (isset($_SESSION['real_atype']) && $_SESSION['real_atype'] == 1 && $_SESSION['atype'] != $_SESSION['real_atype']) {
					// Get the current testing account type name
					$testAtype = $_SESSION['atype'];
					$result = mysqli_query($link, "SELECT namefr FROM tblaccounttype WHERE id = '{$testAtype}'");
					if ($row = mysqli_fetch_array($result)) {
						echo ' <span style="color: #6d5003; font-weight: bold;">| 🔧 Tester en tant que : ' . htmlspecialchars($row['namefr']) . '</span>';
					}
				}
				?>
			</p>
		</div>
		<?php } ?>
<?php
}
?>