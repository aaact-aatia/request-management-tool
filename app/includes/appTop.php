<!-- <h1 property="name" id="wb-cont"> This is localhost or a dev environment </h1> -->
<?php
$lang_code = $_SESSION['lang'] ?? 'en';
$is_authenticated = !empty($_SESSION['pid']);

// Build language toggle URL (preserves existing app behavior)
$newtoggledisabled = false;
$newtoggle = '';
$currenturl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
if ((strpos($currenturl, 'openrequest2-en.php')) !== false) {
	$newtoggledisabled = true;
} else {
	if ((strpos($currenturl, '?lang=en')) !== false) {
		$newtoggle = str_replace('?lang=en', '?lang=fr', $currenturl);
	} elseif ((strpos($currenturl, '&lang=en')) !== false) {
		$newtoggle = str_replace('&lang=en', '&lang=fr', $currenturl);
	} elseif ((strpos($currenturl, '?lang=fr')) !== false) {
		$newtoggle = str_replace('?lang=fr', '?lang=en', $currenturl);
	} elseif ((strpos($currenturl, '&lang=fr')) !== false) {
		$newtoggle = str_replace('&lang=fr', '&lang=en', $currenturl);
	} elseif ((strpos($currenturl, 'lang=')) === false && (strpos($currenturl, '.php')) !== false) {
		$newtoggle = (strpos($currenturl, '?') !== false) ? $currenturl . '&lang=fr' : $currenturl . '?lang=fr';
	} else {
		$newtoggle = str_replace('-en.php', '-fr.php', $currenturl);
	}
}

$other_lang = $lang_code === 'fr' ? 'en' : 'fr';
$other_lang_text = $other_lang === 'fr' ? 'Francais' : 'English';

$app_name = $lang_code === 'fr' ? 'Outil de gestion des demandes (OGD)' : 'Request Management Tool (RMT)';
$app_home = '/openrequest.php?lang=' . $lang_code;

$org_title = $lang_code === 'fr'
	? 'Accessibilite, adaptation et technologie informatique adaptee (AATIA)'
	: 'Accessibility, Accommodation and Adaptive Computer Technology (AAACT)';
$org_href = $lang_code === 'fr'
	? 'https://www.canada.ca/fr/services-partages/services/employes-accessibilite/programme-aatia.html'
	: 'https://www.canada.ca/en/shared-services/services/employees-accessibility/aaact-program.html';

$account_settings_text = $lang_code === 'fr' ? 'Parametres du compte' : 'Account settings';
$sign_in_text = $lang_code === 'fr' ? 'Ouvrir une session' : 'Sign in';
$sign_out_text = $lang_code === 'fr' ? 'Fermer la session' : 'Sign out';
$breadcrumbs_heading = $lang_code === 'fr' ? 'Vous etes ici :' : 'You are here:';
$skip_to_content = $lang_code === 'fr' ? 'Passer au contenu principal' : 'Skip to main content';
$skip_to_about = $lang_code === 'fr' ? 'Passer a "A propos de cette application Web"' : 'Skip to "About this Web application"';
$switch_basic = $lang_code === 'fr' ? 'Passer a la version HTML simplifiee' : 'Switch to basic HTML version';
?>

<nav aria-label="Skip links">
	<ul id="wb-tphp">
		<li class="wb-slc"><a class="wb-sl" href="#wb-cont"><?= htmlspecialchars($skip_to_content) ?></a></li>
		<li class="wb-slc visible-xs visible-sm visible-md visible-lg"><a class="wb-sl" href="#wb-info"><?= htmlspecialchars($skip_to_about) ?></a></li>
		<li class="wb-slc"><a class="wb-sl" href="?lang=<?= htmlspecialchars($lang_code) ?>&amp;wbdisable=true" rel="alternate"><?= htmlspecialchars($switch_basic) ?></a></li>
	</ul>
</nav>

<header aria-label="Government of Canada">
	<div id="wb-bnr" class="container">
		<div class="row">
			<?php if ($newtoggledisabled === false && $newtoggle !== '') { ?>
			<section id="wb-lng" class="col-xs-3 col-sm-12 pull-right text-right">
				<h2 class="wb-inv">Language selection</h2>
				<ul class="list-inline mrgn-bttm-0">
					<li>
						<a lang="<?= htmlspecialchars($other_lang) ?>" href="<?= htmlspecialchars($newtoggle) ?>">
							<span class="hidden-xs"><?= htmlspecialchars($other_lang_text) ?></span>
							<abbr title="<?= htmlspecialchars($other_lang_text) ?>" class="visible-xs h3 mrgn-tp-sm mrgn-bttm-0 text-uppercase"><?= htmlspecialchars($other_lang) ?></abbr>
						</a>
					</li>
				</ul>
			</section>
			<?php } ?>
			<div class="brand col-xs-9 col-sm-5 col-md-4" property="publisher" typeof="GovernmentOrganization">
				<img src="https://www.canada.ca/etc/designs/canada/wet-boew/assets/sig-blk-<?= htmlspecialchars($lang_code) ?>.svg" alt="Government of Canada" property="logo">
				<span class="wb-inv"> / <span lang="<?= htmlspecialchars($other_lang) ?>"><?= $lang_code === 'fr' ? 'Government of Canada' : 'Gouvernement du Canada' ?></span></span>
			</div>
		</div>
	</div>

	<div class="app-bar">
		<div class="container">
			<div class="row">
				<section class="col-xs-12 col-sm-7">
					<h2 class="wb-inv">Name of Web application</h2>
					<a class="app-name" href="<?= htmlspecialchars($app_home) ?>"><?= htmlspecialchars($app_name) ?></a>
				</section>
				<nav class="col-sm-5 hidden-xs hidden-print" aria-label="Account menu">
					<ul class="app-list-account list-unstyled">
						<?php if ($is_authenticated) { ?>
						<li><a href="/settings.php?lang=<?= htmlspecialchars($lang_code) ?>" class="btn"><span class="glyphicon glyphicon-cog" aria-hidden="true"></span> <?= htmlspecialchars($account_settings_text) ?></a></li>
						<li><a href="/signout.php?lang=<?= htmlspecialchars($lang_code) ?>" class="btn"><span class="glyphicon glyphicon-off" aria-hidden="true"></span> <?= htmlspecialchars($sign_out_text) ?></a></li>
						<?php } else { ?>
						<li><a href="/signin.php?lang=<?= htmlspecialchars($lang_code) ?>" class="btn"><span class="glyphicon glyphicon-off" aria-hidden="true"></span> <?= htmlspecialchars($sign_in_text) ?></a></li>
						<?php } ?>
					</ul>
				</nav>
			</div>
		</div>
	</div>

	<?php require(__DIR__ . '/template/menu.php'); ?>

	<nav id="wb-bc" property="breadcrumb" aria-labelledby="breadcrumbPosition">
		<h2 id="breadcrumbPosition"><?= htmlspecialchars($breadcrumbs_heading) ?></h2>
		<div class="container">
			<ol class="breadcrumb">
				<li><a href="<?= htmlspecialchars($org_href) ?>"><?= htmlspecialchars($org_title) ?></a></li>
				<li><a href="<?= htmlspecialchars($app_home) ?>"><?= htmlspecialchars($lang_code === 'fr' ? 'Outil de gestion des demandes' : 'Request Management Tool') ?></a></li>
			</ol>
		</div>
	</nav>
</header>

<?php
if (!empty($is_authenticated) && isset($cenvironment) && $cenvironment == 1) {
?>
	<div style="padding:15px;position:fixed;bottom:0;right:0;width:35%;z-index:9999999;background-color:#FFF;border-style:solid;">
		<div style="padding:5px;">
			<div class="alert alert-danger" style="margin:0 auto;">
				<h3><?= $lang_code === 'fr' ? 'Environnement de developpement' : 'Development environment' ?></h3>
				<p><?= $lang_code === 'fr' ? 'Vous consultez actuellement l\'environnement de developpement, aucune modification ne sera apportee en production. Utilisez les parametres du compte pour revenir a la production.' : 'You are currently viewing the development environment, no changes will be made in production. Use account settings to switch back to production.' ?></p>
			</div>
		</div>
	</div>
<?php } ?>

<?php if ($is_authenticated) { ?>
<div class="container">
	<p>
		<strong><?= $lang_code === 'fr' ? 'Vous etes connecte en tant que :' : 'You are logged in as:' ?></strong>
		<?= htmlspecialchars($_SESSION['firstname'] . ' (' . $_SESSION['email'] . ')') ?>
		<?php
		if (isset($_SESSION['real_atype']) && $_SESSION['real_atype'] == 1 && $_SESSION['atype'] != $_SESSION['real_atype']) {
			$_testAtype = $_SESSION['atype'];
			$_nameField = $lang_code === 'fr' ? 'namefr' : 'nameen';
			$_label = $lang_code === 'fr' ? 'Tester en tant que : ' : 'Testing as: ';
			$_testAtypeResult = mysqli_query($link, "SELECT {$_nameField} FROM tblaccounttype WHERE id = '{$_testAtype}'");
			if ($_testAtypeRow = mysqli_fetch_array($_testAtypeResult)) {
				echo ' <span style="color: #6d5003; font-weight: bold;">| ' . htmlspecialchars($_label) . htmlspecialchars($_testAtypeRow[$_nameField]) . '</span>';
			}
			unset($_testAtype, $_nameField, $_label, $_testAtypeResult, $_testAtypeRow);
		}
		?>
	</p>
</div>
<?php } ?>