<?php
/**
 * Open Request Cascade — Tier 2: Subservices (or checklist gate) for a Service
 *
 * Receives ?v1={serviceid} (numeric tblservices.id, or 0 = "no match").
 *
 * Logic:
 *   - serviceid = 0  → freeform notes textarea + Continue
 *   - is_guidance_only → guidance panel (no form)
 *   - has subservices  → subservice <select> (with optional alert_text above)
 *   - no subservices, needs_checklist → checklist yes/no gate (ajax3 handles result)
 *   - no subservices, no checklist    → optional alert_text + Continue button
 */
require_once __DIR__ . '/includes/session_start.php';
require('sql.php');
/** @var mysqli $link */

require_once __DIR__ . '/vendor/autoload.php';
use League\CommonMark\CommonMarkConverter;
$mdConverter = new CommonMarkConverter(['html_input' => 'strip', 'allow_unsafe_links' => false]);

/** Render a string as Markdown if it looks like Markdown, otherwise return as-is */
function renderGuidanceText(string $raw, CommonMarkConverter $conv): string {
    if ($raw === '') return '';
    return $conv->convert($raw)->getContent();
}

$lang    = $_SESSION['lang'] ?? 'en';
$nameCol = $lang === 'fr' ? 'namefr' : 'nameen';
$isFr    = $lang === 'fr';

if (!isset($_GET['v1']) || $_GET['v1'] === '') {
    exit;
}
$serviceid = (int) $_GET['v1'];

// Shared strings
$continueLabel     = $isFr ? 'Continuer' : 'Continue';
$placeholderText   = $isFr ? 'Faites votre choix' : 'Make your selection';
$typeOfServiceLabel = $isFr
    ? 'Type de service : <strong>(requis)</strong>'
    : 'Type of service: <strong>(required)</strong>';
$selectTypeLabel   = $isFr
    ? 'Sélectionnez le type de demande : <strong>(requis)</strong>'
    : 'Select the type of request: <strong>(required)</strong>';
$notesLabel        = $isFr
    ? 'Détails de la demande : <strong>(requis)</strong>'
    : 'Request details: <strong>(required)</strong>';
$checklistYes      = $isFr ? 'Oui' : 'Yes';
$checklistNo       = $isFr ? 'Non' : 'No';
$checklistLabel    = $isFr ? 'Avez-vous complété' : 'Have you completed the';
$checklistRequired = $isFr ? 'obligatoire' : 'required';

// ------------------------------------------------------------------
// serviceid = 0: "no match" — freeform notes
// ------------------------------------------------------------------
if ($serviceid === 0) {
    ?>
    <input type="hidden" name="subserviceid" value="0">
    <div class="form-group">
        <label for="clientnotes">
            <span class="field-name"><?= $notesLabel ?></span>
        </label>
        <textarea class="form-control" id="clientnotes" name="clientnotes"
                  cols="50" rows="8" required></textarea>
    </div>
    <div class="form-group form-buttons">
        <button type="submit" class="btn btn-primary"><?= $continueLabel ?></button>
    </div>
    <?php
    mysqli_close($link);
    exit;
}

// ------------------------------------------------------------------
// Fetch service row with flags
// ------------------------------------------------------------------
$svcStmt = $link->prepare(
    'SELECT id, nameen, namefr, is_guidance_only,
            guidance_text_en, guidance_text_fr,
            alert_text_en, alert_text_fr,
            needs_checklist, checklist_name_en, checklist_name_fr,
            checklist_url_en, checklist_url_fr,
            has_other_option
     FROM tblservices WHERE id = ? AND status = 1 LIMIT 1'
);
$svcStmt->bind_param('i', $serviceid);
$svcStmt->execute();
$svc = $svcStmt->get_result()->fetch_assoc();
$svcStmt->close();

if (!$svc) {
    mysqli_close($link);
    exit;
}

// ------------------------------------------------------------------
// Guidance-only service: show panel, no form
// ------------------------------------------------------------------
if ($svc['is_guidance_only']) {
    $html = htmlspecialchars_decode(
        $isFr ? $svc['guidance_text_fr'] : $svc['guidance_text_en'],
        ENT_QUOTES
    );
    ?>
    <section class="alert alert-info" role="status" aria-live="polite" tabindex="-1">
        <?= $html ?>
    </section>
    <?php
    mysqli_close($link);
    exit;
}

// ------------------------------------------------------------------
// Render alert_text as Markdown — informational panel, does not block submission
// ------------------------------------------------------------------
$alertHtml = $isFr ? $svc['alert_text_fr'] : $svc['alert_text_en'];
if ($alertHtml) {
    echo '<div class="alert alert-info">'
        . $mdConverter->convert($alertHtml)->getContent()
        . '</div>';
}

// ------------------------------------------------------------------
// Check for subservices
// ------------------------------------------------------------------
$subStmt = $link->prepare(
    "SELECT id, nameen, namefr
     FROM tblsubservices WHERE serviceid = ? AND status = 1
     ORDER BY $nameCol ASC"
);
$subStmt->bind_param('i', $serviceid);
$subStmt->execute();
$subservices = $subStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$subStmt->close();
mysqli_close($link);

// ------------------------------------------------------------------
// Has subservices → render subservice dropdown
// ------------------------------------------------------------------
if (!empty($subservices)) {
    $otherLabel = $isFr ? 'Autre' : 'Other';
    ?>
    <label for="subserviceid">
        <span class="field-name"><?= count($subservices) > 2 ? $typeOfServiceLabel : $selectTypeLabel ?></span>
    </label>
    <select class="form-control" id="subserviceid" name="subserviceid"
            onchange="ajax3(this.value)" required>
        <option value=""><?= $placeholderText ?></option>
        <?php foreach ($subservices as $sub): ?>
        <option value="<?= (int) $sub['id'] ?>">
            <?= htmlspecialchars($sub[$nameCol]) ?>
        </option>
        <?php endforeach; ?>
        <?php if (!empty($svc['has_other_option'])): ?>
        <option value="__other__"><?= htmlspecialchars($otherLabel) ?></option>
        <?php endif; ?>
    </select>
    <?php
    exit;
}

// ------------------------------------------------------------------
// No subservices + needs_checklist → service-level checklist gate
// Answer is stored in subserviceid2; ajax3 shows Continue or Warning
// ------------------------------------------------------------------
if ($svc['needs_checklist']) {
    $nameEn = $svc['checklist_name_en'];
    $nameFr = $svc['checklist_name_fr'];
    $urlEn  = $svc['checklist_url_en'];
    $urlFr  = $svc['checklist_url_fr'];
    $checklistDisplay = $isFr
        ? ($urlFr ? '<a href="' . htmlspecialchars($urlFr) . '" target="_blank" rel="noopener noreferrer">' . htmlspecialchars($nameFr) . '</a>' : htmlspecialchars($nameFr))
        : ($urlEn ? '<a href="' . htmlspecialchars($urlEn) . '" target="_blank" rel="noopener noreferrer">' . htmlspecialchars($nameEn) . '</a>' : htmlspecialchars($nameEn));
    ?>
    <input type="hidden" name="subserviceid" value="0">
    <div class="form-group">
        <label for="subserviceid2">
            <span class="field-name">
                <?= $checklistLabel ?> <?= $checklistDisplay ?>?
                <strong>(<?= $checklistRequired ?>)</strong>
            </span>
        </label>
        <select class="form-control" id="subserviceid2" name="subserviceid2"
                onchange="ajax3(this.value)" required>
            <option value=""><?= $placeholderText ?></option>
            <option value="checklist_yes"><?= $checklistYes ?></option>
            <option value="checklist_no"><?= $checklistNo ?></option>
        </select>
    </div>
    <?php
    exit;
}

// ------------------------------------------------------------------
// No subservices, no checklist → Continue button
// (alert_text already rendered above if present)
// ------------------------------------------------------------------
?>
<input type="hidden" name="subserviceid" value="0">
<div class="form-group form-buttons">
    <button type="submit" class="btn btn-primary"><?= $continueLabel ?></button>
</div>
