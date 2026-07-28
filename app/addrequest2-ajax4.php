<?php
/**
 * Open Request Cascade — Tier 4: Subservice checklist yes/no result
 *
 * Receives ?v1=checklist_yes or ?v1=checklist_no.
 * Shows Continue button on yes; warning on no.
 */
require_once __DIR__ . '/includes/session_start.php';
require('sql.php');
/** @var mysqli $link */

$lang = $_SESSION['lang'] ?? 'en';
$isFr = $lang === 'fr';
mysqli_close($link);

$val = trim($_GET['v1'] ?? '');

$continueLabel = $isFr ? 'Continuer' : 'Continue';
$warningText   = $isFr
    ? 'Veuillez compléter la liste de contrôle ou corriger tous les échecs précédents avant de continuer.'
    : 'Please complete the checklist or correct all previous failures before continuing.';

if ($val === 'checklist_yes') {
    ?>
    <div class="form-group form-buttons">
        <button type="submit" class="btn btn-primary"><?= $continueLabel ?></button>
    </div>
    <?php
} elseif ($val === 'checklist_no') {
    ?>
    <div class="alert alert-warning">
        <p tabindex="0"><?= htmlspecialchars($warningText) ?></p>
    </div>
    <?php
}
