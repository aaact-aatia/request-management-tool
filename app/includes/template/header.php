<?php
if (isset($_SERVER['SCRIPT_FILENAME']) && realpath(__FILE__) === realpath((string) $_SERVER['SCRIPT_FILENAME'])) {
    http_response_code(404);
    exit();
}

/**
 * Template: Application Header
 * Displays app name, navigation, breadcrumbs, and language toggle
 */

require_once(__DIR__ . '/../config.php');

$config = get_app_config();
$langCode = $_SESSION['lang'] ?? 'en';
$otherLanguage = $langCode === 'en' ? 'Français' : 'English';
$otherLang = $langCode === 'en' ? 'fr' : 'en';
$toggleUrl = get_language_toggle_url();
$isAuthenticated = !empty($_SESSION['pid']);

$appName = $config['app']['name'][$langCode];
$orgName = $config['app']['organization'][$langCode];
$orgUrl = $config['app']['organization_url'][$langCode];

// Header-specific language strings
$headerTranslations = [
	'en' => [
		'skip_heading' => 'Skip links',
		'skip_text' => 'Skip to main content',
		'skip_about_text' => 'Skip to "About this Web application"',
		'basic_text' => 'Switch to basic HTML version',
		'language_selection' => 'Language selection',
		'gc_text' => 'Government of Canada',
		'app_name_heading' => 'Name of Web application',
		'account_menu_heading' => 'Account menu',
		'account_settings' => 'Account settings',
		'sign_out' => 'Sign out',
		'sign_in' => 'Sign in',
		'gc_corp_heading' => 'Organisation du gouvernement du Canada',
		'close' => 'Close',
		'close_overlay' => 'Close overlay',
		'close_esc' => 'Close: Menu (escape key)',
		'breadcrumbs_heading' => 'You are here:',
	],
	'fr' => [
		'skip_heading' => 'Ignorer les liens',
		'skip_text' => 'Passer au contenu principal',
		'skip_about_text' => 'Passer à «&nbsp;À propos de cette application Web&nbsp;»',
		'basic_text' => 'Passer à la version HTML simplifiée',
		'language_selection' => 'Sélection de la langue',
		'gc_text' => 'Gouvernement du Canada',
		'app_name_heading' => 'Nom de l\'application Web',
		'account_menu_heading' => 'Menu des paramètres du compte',
		'account_settings' => 'Paramètres du compte',
		'sign_out' => 'Fermer la session',
		'sign_in' => 'Ouvrir une session',
		'gc_corp_heading' => 'Government of Canada Corporate',
		'close' => 'Fermer',
		'close_overlay' => 'Fermer la fenêtre superposée',
		'close_esc' => 'Fermer : Menu (touche d\'échappement)',
		'breadcrumbs_heading' => 'Vous êtes ici :',
	]
];

	$headerLangStrings = $headerTranslations[$langCode];
?>
<body vocab="https://schema.org/" typeof="WebPage">
	<nav aria-label="<?= $headerLangStrings['skip_heading'] ?>">
		<ul id="wb-tphp">
			<li class="wb-slc"><a class="wb-sl" href="#wb-cont"><?= $headerLangStrings['skip_text'] ?></a></li>
			<li class="wb-slc visible-xs visible-sm visible-md visible-lg"><a class="wb-sl" href="#wb-info"><?= $headerLangStrings['skip_about_text'] ?></a></li>
			<li class="wb-slc"><a class="wb-sl" href="?lang=<?= $langCode ?>&amp;wbdisable=true" rel="alternate"><?= $headerLangStrings['basic_text'] ?></a></li>
		</ul>
	</nav>
	<header aria-label="<?= $headerLangStrings['gc_text'] ?>">
		<div id="wb-bnr" class="container">
			<div class="row">
				<?php if ($toggleUrl): ?>
				<section id="wb-lng" class="col-xs-3 col-sm-12 pull-right text-right">
				<h2 class="wb-inv"><?= $headerLangStrings['language_selection'] ?></h2>
					<ul class="list-inline mrgn-bttm-0">
						<li>
							<a lang="<?= $otherLang ?>" href="<?= $toggleUrl ?>">
								<span class="hidden-xs"><?= $otherLanguage ?></span>
								<abbr title="<?= $otherLanguage ?>" class="visible-xs h3 mrgn-tp-sm mrgn-bttm-0 text-uppercase"><?= $otherLang ?></abbr>
							</a>
						</li>
					</ul>
				</section>
				<?php endif; ?>
				<div class="brand col-xs-9 col-sm-5 col-md-4" property="publisher" typeof="GovernmentOrganization">
					<img src="https://www.canada.ca/etc/designs/canada/wet-boew/assets/sig-blk-<?= $langCode ?>.svg" alt="<?= htmlspecialchars($headerLangStrings['gc_text']) ?>" property="logo">
					<span class="wb-inv"> / <span lang="<?= htmlspecialchars($otherLang) ?>"><?= $langCode === 'en' ? 'Gouvernement du Canada' : 'Government of Canada' ?></span></span>
					<meta property="name" content="<?= htmlspecialchars($headerLangStrings['gc_text']) ?>">
					<meta property="areaServed" typeof="Country" content="Canada">
					<link property="logo" href="https://www.canada.ca/etc/designs/canada/wet-boew/assets/wmms-blk.svg">
				</div>
			</div>
		</div>
		<div class="app-bar">
			<div class="container">
				<div class="row">
					<section class="col-xs-12 col-sm-7">
						<h2 class="wb-inv"><?= htmlspecialchars($headerLangStrings['app_name_heading']) ?></h2>
						<a class="app-name" href="/openrequest.php?lang=<?= $langCode ?>"><?= htmlspecialchars($appName) ?></a>
					</section>
					<nav class="col-sm-5 hidden-xs hidden-print" aria-labelledby="cdts-hiddenAccountMenu">
						<h2 class="wb-inv" id="cdts-hiddenAccountMenu"><?= htmlspecialchars($headerLangStrings['account_menu_heading']) ?></h2>
						<ul class="app-list-account list-unstyled">
							<?php if ($isAuthenticated): ?>
							<li><a href="/settings.php?lang=<?= $langCode ?>" class="btn"><span class="glyphicon glyphicon-cog" aria-hidden="true"></span> <?= htmlspecialchars($headerLangStrings['account_settings']) ?></a></li>
							<li><a href="/signout.php?lang=<?= $langCode ?>" id="cdts-signout-btn" class="btn"><span class="glyphicon glyphicon-off" aria-hidden="true"></span> <?= htmlspecialchars($headerLangStrings['sign_out']) ?></a></li>
							<?php else: ?>
							<li><a href="/signin.php?lang=<?= $langCode ?>" id="cdts-signout-btn" class="btn"><span class="glyphicon glyphicon-off" aria-hidden="true"></span> <?= htmlspecialchars($headerLangStrings['sign_in']) ?></a></li>
							<?php endif; ?>
						</ul>
					</nav>
				</div>
			</div>
		</div>
		<div class="app-bar-mb container visible-xs-block hidden-print">
			<nav aria-labelledby="cdts-hiddenMenuAndSearch">
				<h2 class="wb-inv" id="cdts-hiddenMenuAndSearch">Menu</h2>
				<ul class="app-list-main list-unstyled">
					<li class="wb-mb-links" id="wb-glb-mn"><a href="#mb-pnl" aria-controls="mb-pnl" class="btn overlay-lnk" role="button">Menu</a>
						<h2>Menu</h2>
					</li>
				</ul>
				<div id="mb-pnl" class="wb-overlay modal-content overlay-def wb-panel-r" aria-hidden="true">
					<header class="modal-header">
						<div class="modal-title">Menu</div>
					</header>
					<div class="modal-body">
						<?php if ($toggleUrl): ?>
						<section class="lng-ofr">
							<h3><?= $headerLangStrings['language_selection'] ?></h3>
							<ul class="list-inline">
								<a lang="<?= $otherLang ?>" href="<?= htmlspecialchars($toggleUrl) ?>">
									<span class="hidden-xs"><?= $otherLanguage ?></span>
									<abbr title="<?= $otherLanguage ?>" class="visible-xs h3 mrgn-tp-sm mrgn-bttm-0 text-uppercase"><?= $otherLang ?></abbr>
								</a>
							</ul>
						</section>
						<?php endif; ?>

					</div>
					<div class="modal-footer">
					<button title="<?= htmlspecialchars($headerLangStrings['close_overlay']) ?>" class="btn btn-sm btn-primary pull-left overlay-close" type="button"><?= htmlspecialchars($headerLangStrings['close']) ?><span class="wb-inv"><?= htmlspecialchars($headerLangStrings['close_overlay']) ?></span></button>
				</div>
				<button title="<?= htmlspecialchars($headerLangStrings['close_esc']) ?>" class="mfp-close overlay-close" type="button">×<span class="wb-inv"> <?= htmlspecialchars($headerLangStrings['close_esc']) ?></span></button>
			</div>
			</nav>

			<nav aria-labelledby="cdts-accountMenu">
				<h2 class="wb-inv" id="cdts-accountMenu"><?= htmlspecialchars($headerLangStrings['account_menu_heading']) ?></h2>
				<ul class="app-list-account list-unstyled">
					<?php if ($isAuthenticated): ?>
				<li><a href="/settings.php?lang=<?= $langCode ?>" class="btn"><span class="glyphicon glyphicon-cog" aria-hidden="true"></span> <?= htmlspecialchars($headerLangStrings['account_settings']) ?></a></li>
					<li><a href="/signout.php?lang=<?= $langCode ?>" id="cdts-signout-btn-mobile" class="btn"><span class="glyphicon glyphicon-off" aria-hidden="true"></span> <?= htmlspecialchars($headerLangStrings['sign_out']) ?></a></li>
					<?php else: ?>
					<li><a href="/signin.php?lang=<?= $langCode ?>" id="cdts-signout-btn" class="btn"><span class="glyphicon glyphicon-off" aria-hidden="true"></span> <?= htmlspecialchars($headerLangStrings['sign_in']) ?></a></li>
					<?php endif; ?>
				</ul>
			</nav>

		</div>

		<?php 
		require_once(__DIR__ . '/../helpers.php');
		require(__DIR__ . '/menu.php'); 
		?>

		<nav id="wb-bc" property="breadcrumb" aria-labelledby="breadcrumbPosition">
			<h2 id="breadcrumbPosition"><?= $headerLangStrings['breadcrumbs_heading'] ?></h2>
			<div class="container">
				<ol class="breadcrumb">
					<li>
						<a href="<?= htmlspecialchars($orgUrl) ?>"><?= htmlspecialchars($orgName) ?>
						</a>
					</li>
					<li>
						<a href="/openrequest.php?lang=<?= $langCode ?>"><?= htmlspecialchars($appName) ?>
						</a>
					</li>
				</ol>
			</div>
		</nav>
	</header>

<?php
// Show development environment banner if applicable
if (isset($cenvironment) && $cenvironment == 1):
?>
<div style="padding:15px;position:fixed;bottom:0;right:0;width:35%;z-index:9999999;background-color:#FFF;border-style:solid;">
    <div style="padding:5px;">
        <div class="alert alert-danger" style="margin:0 auto;">
			<h3><?= $langCode === 'en' ? 'Development environment' : 'Environnement de développement' ?></h3>
            <p>
				<?= $langCode === 'en' 
                    ? 'You are currently viewing the development environment, no changes will be made in production. Use account settings to switch back to production.'
                    : 'Vous consultez actuellement l\'environnement de développement, aucune modification ne sera apportée en production. Utilisez les paramètres du compte pour revenir à la production.'
                ?>
            </p>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
// Show logged-in user info and account type testing notice
if (!empty($_SESSION['pid'])):
?>
<div class="container">
    <p>
		<strong><?= $langCode === 'en' ? 'You are logged in as:' : 'Vous êtes connecté en tant que :' ?></strong>
        <?= htmlspecialchars($_SESSION['firstname'] . ' (' . $_SESSION['email'] . ')') ?>
        <?php 
        if ($_SESSION['is_superuser'] == 1 && $_SESSION['atype'] != ($_SESSION['primary_atype'] ?? $_SESSION['atype'])) {
            // Get the current testing account type name
            $testAtype = $_SESSION['atype'];
			$nameField = ($langCode === 'fr') ? 'namefr' : 'nameen';
            $result = mysqli_query($link, "SELECT {$nameField} FROM tblaccounttype WHERE id = '{$testAtype}'");
            if ($row = mysqli_fetch_array($result)) {
                echo ' <span style="color: #6d5003; font-weight: bold;">| 🔧 ';
				echo ($langCode === 'en' ? 'Testing as: ' : 'Tester en tant que : ');
                echo htmlspecialchars($row[$nameField]) . '</span>';
            }
        }
        ?>
    </p>
</div>
<?php endif; ?>
