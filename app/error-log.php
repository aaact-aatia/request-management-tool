<?php
/**
 * Error Log - Maintenance Notice
 */

// Start session
require_once __DIR__ . '/includes/session_start.php';

// Grab HTTPS check
require('includes/httpscheck.php');

// Check if there is a status
if (!empty($_GET['status'])){
	$status = $_GET['status'];
}
else{
	$status = "";
}

// Load config
require_once 'includes/config.php';

// Page-specific metadata
$pageTitle = 'Maintenance Notice';
$pageDescription = '';

include 'includes/template/head.php';
include 'includes/template/header.php';
?>
    <main role="main" property="mainContentOfPage" class="container">
        <div class="row mrgn-tp-lg">
            <section class="col-md-12">
                <h2>Maintenance Notice</h2>
                <p>Please note that our website will be undergoing maintenance from 12 PM to 2 PM. We kindly ask that
                    you refrain from accessing the site until after 2 PM.</p>
            </section>
            <section class="col-md-12" lang="fr">
                <h2>Avis de maintenance</h2>
                <p>Veuillez noter que notre site web sera en maintenance de 12h à 14h. Nous vous prions de bien vouloir
                    éviter d’accéder au site jusqu’à après 14h.</p>
            </section>
        </div>
<?php include 'includes/template/page-details.php'; ?>
    </main>
<?php 
include 'includes/template/footer.php';
include 'includes/template/scripts.php';