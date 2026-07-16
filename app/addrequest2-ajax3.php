<?php
/**
 * Open Request Cascade — Tier 3
 *
 * Receives ?v1={value} where value is one of:
 *   - A numeric tblsubservices.id  → show alert (if set), then checklist gate or Continue
 *   - 'checklist_yes'              → service-level checklist passed → Continue
 *   - 'checklist_no'               → service-level checklist failed → Warning
 *   - '__other__'                  → "Other" option selected → freeform notes textarea
 */
require_once __DIR__ . '/includes/session_start.php';
require('sql.php');
/** @var mysqli $link */

require_once __DIR__ . '/vendor/autoload.php';
use League\CommonMark\CommonMarkConverter;
$mdConverter = new CommonMarkConverter(['html_input' => 'strip', 'allow_unsafe_links' => false]);

$lang    = $_SESSION['lang'] ?? 'en';
$isFr    = $lang === 'fr';

if (!isset($_GET['v1']) || $_GET['v1'] === '') {
    exit;
}
$val = trim($_GET['v1']);

$continueLabel   = $isFr ? 'Continuer' : 'Continue';
$placeholderText = $isFr ? 'Faites votre choix' : 'Make your selection';
$checklistYes    = $isFr ? 'Oui' : 'Yes';
$checklistNo     = $isFr ? 'Non' : 'No';
$checklistLabel  = $isFr ? 'Avez-vous complété' : 'Have you completed the';
$checklistRequired = $isFr ? 'obligatoire' : 'required';
$warningText     = $isFr
    ? 'Veuillez compléter la liste de contrôle ou corriger tous les échecs précédents avant de continuer.'
    : 'Please complete the checklist or correct all previous failures before continuing.';

// ------------------------------------------------------------------
// Service-level checklist answer (from ajax2 checklist gate)
// ------------------------------------------------------------------
if ($val === 'checklist_yes') {
    ?>
    <div class="form-group form-buttons">
        <button type="submit" class="btn btn-primary"><?= $continueLabel ?></button>
    </div>
    <?php
    mysqli_close($link);
    exit;
}

if ($val === 'checklist_no') {
    ?>
    <div class="alert alert-warning">
        <p tabindex="0"><?= htmlspecialchars($warningText) ?></p>
    </div>
    <?php
    mysqli_close($link);
    exit;
}

// ------------------------------------------------------------------
// "Other" option — show freeform notes textarea + Continue
// ------------------------------------------------------------------
if ($val === '__other__') {
    $notesLabel = $isFr
        ? 'Décrivez votre demande : <strong>(requis)</strong>'
        : 'Describe your request: <strong>(required)</strong>';
    ?>
    <input type="hidden" name="subserviceid" value="0">
    <div class="form-group">
        <label for="clientnotes">
            <span class="field-name"><?= $notesLabel ?></span>
        </label>
        <textarea class="form-control" id="clientnotes" name="clientnotes"
                  rows="5" required></textarea>
    </div>
    <div class="form-group form-buttons">
        <button type="submit" class="btn btn-primary"><?= $continueLabel ?></button>
    </div>
    <?php
    mysqli_close($link);
    exit;
}

// ------------------------------------------------------------------
// Numeric subservice ID
// ------------------------------------------------------------------
$subserviceid = (int) $val;
if ($subserviceid <= 0) {
    mysqli_close($link);
    exit;
}

$stmt = $link->prepare(
    'SELECT id, nameen, namefr, is_guidance_only,
            guidance_text_en, guidance_text_fr,
            alert_text_en, alert_text_fr,
            needs_checklist, checklist_name_en, checklist_name_fr,
            checklist_url_en, checklist_url_fr,
            needs_sprint_fields
     FROM tblsubservices WHERE id = ? AND status = 1 LIMIT 1'
);
$stmt->bind_param('i', $subserviceid);
$stmt->execute();
$sub = $stmt->get_result()->fetch_assoc();
$stmt->close();
mysqli_close($link);

if (!$sub) {
    exit;
}

// Guidance-only subservice
if ($sub['is_guidance_only']) {
    $html = htmlspecialchars_decode(
        $isFr ? $sub['guidance_text_fr'] : $sub['guidance_text_en'],
        ENT_QUOTES
    );
    ?>
    <section class="alert alert-info" role="status" aria-live="polite" tabindex="-1">
        <?= $html ?>
    </section>
    <?php
    exit;
}

// Alert text — rendered as Markdown, informational, does not block submission
$alertHtml = $isFr ? $sub['alert_text_fr'] : $sub['alert_text_en'];
if ($alertHtml) {
    echo '<div class="alert alert-info">'
        . $mdConverter->convert($alertHtml)->getContent()
        . '</div>';
}

// Needs checklist gate → show yes/no dropdown, ajax4 handles the answer
if ($sub['needs_checklist']) {
    $nameEn = $sub['checklist_name_en'];
    $nameFr = $sub['checklist_name_fr'];
    $urlEn  = $sub['checklist_url_en'];
    $urlFr  = $sub['checklist_url_fr'];
    $checklistDisplay = $isFr
        ? ($urlFr ? '<a href="' . htmlspecialchars($urlFr) . '" target="_blank" rel="noopener noreferrer">' . htmlspecialchars($nameFr) . '</a>' : htmlspecialchars($nameFr))
        : ($urlEn ? '<a href="' . htmlspecialchars($urlEn) . '" target="_blank" rel="noopener noreferrer">' . htmlspecialchars($nameEn) . '</a>' : htmlspecialchars($nameEn));
    ?>
    <div class="form-group">
        <label for="subserviceid2">
            <span class="field-name">
                <?= $checklistLabel ?> <?= $checklistDisplay ?>?
                <strong>(<?= $checklistRequired ?>)</strong>
            </span>
        </label>
        <select class="form-control" id="subserviceid2" name="subserviceid2"
                onchange="ajax4(this.value)" required>
            <option value=""><?= $placeholderText ?></option>
            <option value="checklist_yes"><?= $checklistYes ?></option>
            <option value="checklist_no"><?= $checklistNo ?></option>
        </select>
    </div>
    <?php
    exit;
}

// No checklist needed → Continue
?>
<div class="form-group form-buttons">
    <button type="submit" class="btn btn-primary"><?= $continueLabel ?></button>
</div>
