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
$notifyMode = function_exists('app_notify_mode') ? app_notify_mode() : 'live';
$showEnvironmentBanner = function_exists('app_is_production') ? !app_is_production() : false;
$notifyRedirectEmail = function_exists('app_notify_redirect_recipient') ? app_notify_redirect_recipient() : null;

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
		'dev_notice_label' => 'Development environment notice',
		'dev_notice_prefix' => 'Development environment.',
		'dev_notice_redirect' => 'Email notifications are redirected to a safe test recipient.',
		'dev_notice_redirect_email' => 'Email notifications are redirected to:',
		'dev_notice_disabled' => 'Email notifications are disabled.',
		'dev_notice_live' => 'Email notifications are being sent to their intended recipients.',
		'dev_preview_title' => 'Notification preview (development)',
		'dev_preview_client' => 'Client',
		'dev_preview_internal' => 'Team',
		'dev_preview_general' => 'Recipient',
		'dev_preview_role_team' => 'Team',
		'dev_preview_role_manager' => 'Manager',
		'dev_preview_role_team_lead' => 'Team lead',
		'dev_preview_role_admin' => 'Admin',
		'dev_preview_role_assignee' => 'Assignee',
		'dev_preview_role_user' => 'Employee',
		'dev_preview_disabled' => 'Disabled mode: would send to',
		'dev_preview_sent' => 'Sent to',
		'dev_preview_intended' => 'intended',
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
		'dev_notice_label' => 'Avis sur l\'environnement de développement',
		'dev_notice_prefix' => 'Environnement de développement.',
		'dev_notice_redirect' => 'Les notifications par courriel sont redirigées vers un destinataire d\'essai sécuritaire.',
		'dev_notice_redirect_email' => 'Les notifications par courriel sont redirigées vers :',
		'dev_notice_disabled' => 'Les notifications par courriel sont désactivées.',
		'dev_notice_live' => 'Les notifications par courriel sont envoyées à leurs destinataires prévus.',
		'dev_preview_title' => 'Apercu des notifications (developpement)',
		'dev_preview_client' => 'Client',
		'dev_preview_internal' => 'Equipe',
		'dev_preview_general' => 'Destinataire',
		'dev_preview_role_team' => 'Equipe',
		'dev_preview_role_manager' => 'Gestionnaire',
		'dev_preview_role_team_lead' => 'Chef d equipe',
		'dev_preview_role_admin' => 'Administrateur',
		'dev_preview_role_assignee' => 'Personne assignee',
		'dev_preview_role_user' => 'Employe',
		'dev_preview_disabled' => 'Mode desactive : envoi prevu a',
		'dev_preview_sent' => 'Envoye a',
		'dev_preview_intended' => 'prevu',
	]
];

	$headerLangStrings = $headerTranslations[$langCode];
	$devNotificationPreviewEntries = function_exists('app_dev_notification_preview_consume')
		? app_dev_notification_preview_consume()
		: [];
	$notifyModeMessage = $headerLangStrings['dev_notice_live'];
	if ($notifyMode === 'redirect') {
		$notifyModeMessage = $headerLangStrings['dev_notice_redirect'];
		if (!empty($notifyRedirectEmail)) {
			$notifyModeMessage = $headerLangStrings['dev_notice_redirect_email'] . ' ' . $notifyRedirectEmail;
		}
	} elseif ($notifyMode === 'disabled') {
		$notifyModeMessage = $headerLangStrings['dev_notice_disabled'];
	}
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

<?php if ($showEnvironmentBanner): ?>
<div class="container mrgn-tp-md">
	<section class="alert alert-warning" aria-label="<?= htmlspecialchars($headerLangStrings['dev_notice_label']) ?>">
		<p class="mrgn-bttm-0">
			<strong><?= htmlspecialchars($headerLangStrings['dev_notice_prefix']) ?></strong>
			<?= htmlspecialchars($notifyModeMessage) ?>
		</p>
	</section>
</div>
<?php endif; ?>

<?php if ($showEnvironmentBanner && !empty($devNotificationPreviewEntries)): ?>
<div class="container mrgn-tp-md">
	<section class="alert alert-info" aria-label="<?= htmlspecialchars($headerLangStrings['dev_preview_title']) ?>">
		<p><strong><?= htmlspecialchars($headerLangStrings['dev_preview_title']) ?></strong></p>
		<ul class="mrgn-bttm-0">
			<?php foreach ($devNotificationPreviewEntries as $previewEntry): ?>
				<?php
				$recipientType = (string) ($previewEntry['recipientType'] ?? 'general');
				$recipientRole = trim((string) ($previewEntry['recipientRole'] ?? ''));
				$intendedRecipient = (string) ($previewEntry['intendedRecipient'] ?? '');
				$finalRecipient = (string) ($previewEntry['finalRecipient'] ?? '');
				$previewResult = (string) ($previewEntry['result'] ?? 'attempted');
				$hasCustomRecipientLabel = false;

				if ($recipientType === 'internal' && $recipientRole === '' && isset($link) && ($link instanceof mysqli)) {
					$emailToClassify = $finalRecipient !== '' ? $finalRecipient : $intendedRecipient;
					if ($emailToClassify !== '') {
						$escapedEmail = mysqli_real_escape_string($link, $emailToClassify);
						$roleNameField = $langCode === 'fr' ? 'namefr' : 'nameen';
						$roleResult = mysqli_query($link, "
							SELECT u.atype, at.$roleNameField AS role_name
							FROM tblusers u
							LEFT JOIN tblaccounttype at ON at.id = u.atype
							WHERE LOWER(u.email) = LOWER('$escapedEmail') AND u.status = '1'
							ORDER BY u.id DESC
							LIMIT 1
						");
						$roleRow = $roleResult ? mysqli_fetch_assoc($roleResult) : null;
						if (!empty($roleRow)) {
							$recipientRole = 'user';
							$roleName = trim((string) ($roleRow['role_name'] ?? ''));
							if ($roleName !== '') {
								$recipientLabel = $roleName;
								$hasCustomRecipientLabel = true;
							}
						}
					}
				}

				if (!isset($recipientLabel)) {
					$recipientLabel = $headerLangStrings['dev_preview_general'];
				}
				$roleLabelKey = 'dev_preview_role_' . $recipientRole;
				if (!$hasCustomRecipientLabel && $recipientRole !== '' && isset($headerLangStrings[$roleLabelKey])) {
					$recipientLabel = $headerLangStrings[$roleLabelKey];
				} elseif (!$hasCustomRecipientLabel && $recipientType === 'client') {
					$recipientLabel = $headerLangStrings['dev_preview_client'];
				} elseif (!$hasCustomRecipientLabel && $recipientType === 'internal') {
					$recipientLabel = $headerLangStrings['dev_preview_internal'];
				}

				if ($previewResult === 'disabled') {
					$detail = $headerLangStrings['dev_preview_disabled'] . ' ' . $intendedRecipient;
				} elseif ($finalRecipient !== '' && strcasecmp($finalRecipient, $intendedRecipient) !== 0) {
					$detail = $headerLangStrings['dev_preview_sent'] . ' ' . $finalRecipient . ' (' . $headerLangStrings['dev_preview_intended'] . ': ' . $intendedRecipient . ')';
				} else {
					$detail = $headerLangStrings['dev_preview_sent'] . ' ' . ($finalRecipient !== '' ? $finalRecipient : $intendedRecipient);
				}
				?>
				<li><strong><?= htmlspecialchars($recipientLabel) ?>:</strong> <?= htmlspecialchars($detail) ?></li>
			<?php endforeach; ?>
		</ul>
	</section>
</div>
<?php endif; ?>

<?php
// Show logged-in user info and account type testing notice
if (!empty($_SESSION['pid'])):
	/** @var mysqli $link */
?>
<div class="container">
    <p>
		<strong><?= $langCode === 'en' ? 'You are logged in as:' : 'Vous êtes connecté en tant que :' ?></strong>
        <?= htmlspecialchars($_SESSION['firstname'] . ' (' . $_SESSION['email'] . ')') ?>
        <?php 
        // Show testing notice if superuser and atype != 1 (meaning they're testing)
        if ($_SESSION['is_superuser'] == 1 && $_SESSION['atype'] != 1) {
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
