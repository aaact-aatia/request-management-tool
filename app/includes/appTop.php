
 <!-- <h1 property="name" id="wb-cont"> This is localhost or a dev environment </h1> -->
<?php
// Get URL for language toggle

$newtoggledisabled = false;
$currenturl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
if ((strpos($currenturl, 'openrequest2-en.php')) !== false) {
    // No toggle on this page
	$newtoggledisabled = true;
} else {
	// Handle new bilingual pages with ?lang=en parameter
	if ((strpos($currenturl, '?lang=en')) !== false) {
		$newtoggle = str_replace("?lang=en","?lang=fr",$currenturl);
	} elseif ((strpos($currenturl, '&lang=en')) !== false) {
		$newtoggle = str_replace("&lang=en","&lang=fr",$currenturl);
	} elseif ((strpos($currenturl, 'lang=')) === false && (strpos($currenturl, '.php')) !== false) {
		// No lang parameter - add it
		if ((strpos($currenturl, '?')) !== false) {
			$newtoggle = $currenturl . "&lang=fr";
		} else {
			$newtoggle = $currenturl . "?lang=fr";
		}
	} else {
		// Legacy pages with -en.php
		$newtoggle = str_replace("-en.php","-fr.php",$currenturl);
	}
}
if(empty($_SESSION['pid'])){
?>
<!-- Write closure template -->
		<script>
			var defTop = document.getElementById("def-top");
			defTop.outerHTML = wet.builder.appTop({
				"appName": [{"text": "Request Management Tool (RMT)", "href": "/openrequest.php?lang=en"}],
				"signIn": [{"href": "/signin.php?lang=en"}],
				<?php if ($newtoggledisabled==false) {  ?>
				"lngLinks": [{"lang": "fr", "href": "<?php echo $newtoggle ?>", "text": "Français"}],
				<?php } ?>
				"menuPath": "/includes/appmenu.php",
				"breadcrumbs": [{
					"title": "IT Accessibility Office",
					"href": "http://iservice.prv/accessibility"
				}, {
					"title": "Request Management Tool",
					"href": "/openrequest-en.php"
				}]
			});

			
		</script>
<?php
} else {
	// Set a banner if environment is development
	if ($cenvironment==1) {
?>
		<div style="padding:15px;position:fixed;bottom:0;right:0;width: 35%;z-index:9999999;background-color:#FFF;border-style: solid;">
			 <div style="padding: 5px;">
				<div class="alert alert-danger" style="margin:0 auto;">
					<h3>Development environment<h3>
					<p>You are currently viewing the development environment, no changes will be made in production. Use account settings to switch back to production.</p>
				</div>
			</div>
		</div>

		<!--<div class="alert alert-danger">
			<h3>Development environment<h3>
			<p>You are currently viewing the devleopment environment. No changes will be made in production. To switch back to production environment change the settings in your account settings page.</p>
		</div>-->
<?php }?>
<!-- Write closure template -->
		<script>
			var defTop = document.getElementById("def-top");
			defTop.outerHTML = wet.builder.appTop({
				"appName": [{"text": "Request Management Tool (RMT)", "href": "/openrequest.php?lang=en"}],
				"signOut": [{"href": "/signout.php?lang=en"}],
				"appSettings": [{"href": "/settings.php?lang=en"}],
				<?php if ($newtoggledisabled==false) {  ?>
				"lngLinks": [{"lang": "fr", "href": "<?php echo $newtoggle ?>", "text": "Français"}],
				<?php } ?>
				"menuPath": "/includes/appmenu.php",
				"breadcrumbs": [{
					"title": "IT Accessibility Office",
					"href": "http://iservice.prv/accessibility"
				}, {
					"title": "Request Management Tool",
					"href": "/openrequest.php?lang=en"
				}]
			});
		</script>
		<?php if (!empty($_SESSION['pid'])) { ?>
		<div class="container">
			<p>
				<strong>You are logged in as:</strong> 
				<?php echo htmlspecialchars($_SESSION['firstname'] . ' (' . $_SESSION['email'] . ')'); ?>
				<?php 
				if (isset($_SESSION['real_atype']) && $_SESSION['real_atype'] == 1 && $_SESSION['atype'] != $_SESSION['real_atype']) {
					// Get the current testing account type name - use local vars to avoid clobbering caller's $result/$row
					$_testAtype = $_SESSION['atype'];
					$_testAtypeResult = mysqli_query($link, "SELECT nameen FROM tblaccounttype WHERE id = '{$_testAtype}'");
					if ($_testAtypeRow = mysqli_fetch_array($_testAtypeResult)) {
						echo ' <span style="color: #6d5003; font-weight: bold;">| 🔧 Testing as: ' . htmlspecialchars($_testAtypeRow['nameen']) . '</span>';
					}
					unset($_testAtype, $_testAtypeResult, $_testAtypeRow);
				}
				?>
			</p>
		</div>
		<?php } ?>
<?php
}
?>