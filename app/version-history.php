<?php 
// Start session
require_once __DIR__ . '/includes/session_start.php';

// Translations
$translations = [
	'en' => [
		'page_title' => 'Release Version History - Request Management Tool - IT Accessibility Office',
		'heading' => 'RMT Release Version History',
		'v1_3_title' => 'Version 1.3 - Release Date: 2025-05-05',
		'v1_3_intro' => 'This release introduces advanced features for files management, reporting, email automation, and a new "Version History" page to consolidate release information.',
		'file_upload' => 'File Upload:',
		'file_upload_desc' => 'While creating a request, users can upload multiple files to provide additional context for each request.',
		'view_request' => 'View Request:',
		'view_request_desc' => 'Download or delete documents/images within a request.',
		'edit_request' => 'Edit Request:',
		'edit_request_desc' => 'Download, delete, or add new documents/images.',
		'reporting_updates' => 'Reporting System Updates:',
		'reporting_desc' => 'The reporting system has been updated to provide detailed insights into workflow analysis and track performance effectively',
		'status_timing' => 'Status Timing:',
		'status_timing_desc' => 'Track the time spent in each status for every request.',
		'detailed_stats' => 'Detailed Statistics:',
		'detailed_stats_desc' => 'View occurrences, averages, and performance metrics to optimize processes.',
		'email_notifications' => 'Email Notifications:',
		'email_notif_desc' => 'Email notifications are sent to clients at key stages of the request lifecycle:',
		'email_created' => 'When a request is created.',
		'email_status' => 'When a request has a status change.',
		'email_closed' => 'When a request is closed.',
		'version_history_page' => 'New "Version History" Page:',
		'version_history_desc' => 'A new page has been added to consolidate all release versions in an organized format:',
		'collapsible' => 'Includes collapsible sections for easier navigation of version updates.',
		'connected_users' => 'Connected users can view detailed descriptions of functionalities based on their access level.',
		'v1_2_title' => 'Version 1.2 - Release Date: TBD',
		'v1_2_intro' => 'This release brings a series of updates aimed at improving functionality, workflows, and reporting accuracy.',
		'new_status' => 'New Status:',
		'v1_2_status_desc' => '"Information Gathering" is introduced as the default for new web/document audit requests.',
		'search_capabilities' => 'Search Capabilities:',
		'search_desc' => 'Users can now search for requests exceeding a year old.',
		'status_change_log' => 'Status Change Log:',
		'status_log_desc' => 'Status changes are recorded for SLA calculations and better reporting.',
		'category_rename' => 'Category Renaming:',
		'category_desc' => '"ICT accessibility audits (assessments)" renamed to "Accessibility audits (assessments)".',
		'v1_1_title' => 'Version 1.1 - Release Date: TBD',
		'v1_1_intro' => 'This release enhances search functionalities and introduces a new status for tracking.',
		'v1_1_status_desc' => '"Return to client" added for better request tracking.',
		'v1_0_1_title' => 'Version 1.0.1 - Release Date: TBD',
		'v1_0_1_intro' => 'This release introduces updates to improve the user experience and add functionalities.',
		'privacy_statement' => 'Privacy Statement:',
		'privacy_desc' => 'Added for ICT Accessibility requests.',
		'new_buttons' => 'New Buttons:',
		'clone_buttons' => '"Clone" and "Clone & Close" for task management.',
		'adaptive_tech' => 'Adaptive Technology Support Updates:',
		'added' => 'Added: "Pixie" and "Tint & Track".',
		'removed' => 'Removed: "Worksafe Sam" and "Scribe MediaLexie".'
	],
	'fr' => [
		'page_title' => 'Historique des versions - Outil de gestion des demandes - Bureau de l\'accessibilité de la TI',
		'heading' => 'Historique des versions de l\'OGD',
		'v1_3_title' => 'Version 1.3 - Date de publication : 2025-05-05',
		'v1_3_intro' => 'Cette version introduit des fonctionnalités avancées pour la gestion des fichiers, les rapports, l\'automatisation des courriels et une nouvelle page « Historique des versions » pour consolider les informations de publication.',
		'file_upload' => 'Téléversement de fichiers :',
		'file_upload_desc' => 'Lors de la création d\'une demande, les utilisateurs peuvent téléverser plusieurs fichiers pour fournir un contexte supplémentaire pour chaque demande.',
		'view_request' => 'Afficher la demande :',
		'view_request_desc' => 'Télécharger ou supprimer des documents/images dans une demande.',
		'edit_request' => 'Modifier la demande :',
		'edit_request_desc' => 'Télécharger, supprimer ou ajouter de nouveaux documents/images.',
		'reporting_updates' => 'Mises à jour du système de rapports :',
		'reporting_desc' => 'Le système de rapports a été mis à jour pour fournir des informations détaillées sur l\'analyse du flux de travail et suivre efficacement les performances',
		'status_timing' => 'Chronométrage des statuts :',
		'status_timing_desc' => 'Suivre le temps passé dans chaque statut pour chaque demande.',
		'detailed_stats' => 'Statistiques détaillées :',
		'detailed_stats_desc' => 'Afficher les occurrences, les moyennes et les mesures de performance pour optimiser les processus.',
		'email_notifications' => 'Notifications par courriel :',
		'email_notif_desc' => 'Des notifications par courriel sont envoyées aux clients aux étapes clés du cycle de vie de la demande :',
		'email_created' => 'Lorsqu\'une demande est créée.',
		'email_status' => 'Lorsqu\'une demande change de statut.',
		'email_closed' => 'Lorsqu\'une demande est fermée.',
		'version_history_page' => 'Nouvelle page « Historique des versions » :',
		'version_history_desc' => 'Une nouvelle page a été ajoutée pour consolider toutes les versions dans un format organisé :',
		'collapsible' => 'Comprend des sections réductibles pour faciliter la navigation des mises à jour de version.',
		'connected_users' => 'Les utilisateurs connectés peuvent afficher des descriptions détaillées des fonctionnalités en fonction de leur niveau d\'accès.',
		'v1_2_title' => 'Version 1.2 - Date de publication : À déterminer',
		'v1_2_intro' => 'Cette version apporte une série de mises à jour visant à améliorer la fonctionnalité, les flux de travail et la précision des rapports.',
		'new_status' => 'Nouveau statut :',
		'v1_2_status_desc' => '« Collecte d\'informations » est introduit comme valeur par défaut pour les nouvelles demandes d\'audit Web/document.',
		'search_capabilities' => 'Capacités de recherche :',
		'search_desc' => 'Les utilisateurs peuvent maintenant rechercher des demandes datant de plus d\'un an.',
		'status_change_log' => 'Journal des changements de statut :',
		'status_log_desc' => 'Les changements de statut sont enregistrés pour les calculs de NdS et de meilleurs rapports.',
		'category_rename' => 'Renommage de catégorie :',
		'category_desc' => '« Audits d\'accessibilité TIC (évaluations) » renommé en « Audits d\'accessibilité (évaluations) ».',
		'v1_1_title' => 'Version 1.1 - Date de publication : À déterminer',
		'v1_1_intro' => 'Cette version améliore les fonctionnalités de recherche et introduit un nouveau statut pour le suivi.',
		'v1_1_status_desc' => '« Retour au client » ajouté pour un meilleur suivi des demandes.',
		'v1_0_1_title' => 'Version 1.0.1 - Date de publication : À déterminer',
		'v1_0_1_intro' => 'Cette version introduit des mises à jour pour améliorer l\'expérience utilisateur et ajouter des fonctionnalités.',
		'privacy_statement' => 'Déclaration de confidentialité :',
		'privacy_desc' => 'Ajoutée pour les demandes d\'accessibilité TIC.',
		'new_buttons' => 'Nouveaux boutons :',
		'clone_buttons' => '« Cloner » et « Cloner et fermer » pour la gestion des tâches.',
		'adaptive_tech' => 'Mises à jour du support des technologies adaptatives :',
		'added' => 'Ajoutés : « Pixie » et « Tint & Track ».',
		'removed' => 'Retirés : « Worksafe Sam » et « Scribe MediaLexie ».'
	]
];

// Grab MySQL connection (starts session)
require('sql.php');
/** @var mysqli $link */

require('includes/config.php');
require('BlobStorage.php');
// Grab HTTPS check
require('includes/httpscheck.php');

// Include file for calculating business days
require('includes/calculate-bdays.php');

// Handle language from query string or session
if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'fr'])) {
    $_SESSION['lang'] = $_GET['lang'];
}

// Set default language
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en';
}

// Store language code
$lang = $_SESSION['lang'];

// Get translations for current language
$t = $translations[$lang];

// =============================================================================
// PAGE FRONTMATTER - Define page metadata
// =============================================================================
$page = [
	'title' => [
		'en' => 'Release Version History',
		'fr' => 'Historique des versions'
	],
	'description' => [
		'en' => 'RMT Release Version History and changelog',
		'fr' => 'Historique des versions et journal des modifications de l\'OGD'
	]
];

// Extract values for current language
$pageTitle = $page['title'][$lang];
$pageDescription = $page['description'][$lang];

// Include template head
include 'includes/template/head.php';
?>
	<?php include 'includes/template/header.php'; ?>

  <main role="main" property="mainContentOfPage" class="container">
    <h1 property="name" id="wb-cont" class="mrgn-bttm-md"><?= $t['heading'] ?></h1>

    <details>
  <summary><?= $t['v1_3_title'] ?></summary>
  <p><?= $t['v1_3_intro'] ?></p>
  <ul>
    <li><b><?= $t['file_upload'] ?></b>
      <p><?= $t['file_upload_desc'] ?></p>
      <?php
        if ($_SESSION['atype'] >= 1) { // Connected users only
          echo '<ul>
                  <li><b>' . $t['view_request'] . '</b> ' . $t['view_request_desc'] . '</li>
                  <li><b>' . $t['edit_request'] . '</b> ' . $t['edit_request_desc'] . '</li>
                </ul>';
        }
      ?>
    </li>
    <li><b><?= $t['reporting_updates'] ?></b>
    <p><?= $t['reporting_desc'] ?></p>
      <?php
        if ($_SESSION['atype'] >= 1) {
          echo '<ul>
                  <li><b>' . $t['status_timing'] . '</b> ' . $t['status_timing_desc'] . '</li>
                  <li><b>' . $t['detailed_stats'] . '</b> ' . $t['detailed_stats_desc'] . '</li>
                </ul>';
        }
      ?>
    </li>
    <li><b><?= $t['email_notifications'] ?></b>
    <p><?= $t['email_notif_desc'] ?></p>
    <ul>
        <li><?= $t['email_created'] ?></li>
        <li><?= $t['email_status'] ?></li>
        <li><?= $t['email_closed'] ?></li>
    </ul>
</li>

    <li><b><?= $t['version_history_page'] ?></b>
      <p><?= $t['version_history_desc'] ?></p>
      <ul>
        <li><?= $t['collapsible'] ?></li>
        <li><?= $t['connected_users'] ?></li>
      </ul>
    </li>
  </ul>
</details>

<details>
  <summary><?= $t['v1_2_title'] ?></summary>
  <p><?= $t['v1_2_intro'] ?></p>
  <ul>
    <li><b><?= $t['new_status'] ?></b> <?= $t['v1_2_status_desc'] ?></li>
    <li><b><?= $t['search_capabilities'] ?></b> <?= $t['search_desc'] ?></li>
    <li><b><?= $t['status_change_log'] ?></b> <?= $t['status_log_desc'] ?></li>
    <li><b><?= $t['category_rename'] ?></b> <?= $t['category_desc'] ?></li>
  </ul>
</details>

<details>
  <summary><?= $t['v1_1_title'] ?></summary>
  <p><?= $t['v1_1_intro'] ?></p>
  <ul>
    <li><b><?= $t['new_status'] ?></b> <?= $t['v1_1_status_desc'] ?></li>
  </ul>
</details>

<details>
  <summary><?= $t['v1_0_1_title'] ?></summary>
  <p><?= $t['v1_0_1_intro'] ?></p>
  <ul>
    <li><b><?= $t['privacy_statement'] ?></b> <?= $t['privacy_desc'] ?></li>
    <li><b><?= $t['new_buttons'] ?></b>
      <ul>
        <li><?= $t['clone_buttons'] ?></li>
      </ul>
    </li>
    <li><b><?= $t['adaptive_tech'] ?></b>
      <ul>
        <li><?= $t['added'] ?></li>
        <li><?= $t['removed'] ?></li>
      </ul>
    </li>
  </ul>
</details>

		<?php include 'includes/template/page-details.php'; ?>
	</main>
	
	<?php include 'includes/template/footer.php'; include 'includes/template/scripts.php'; ?>
</body>
</html>
