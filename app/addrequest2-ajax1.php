<?php
/**
 * Open Request Cascade — Tier 1: Services for a Catalogue
 *
 * Receives ?v1={catalogueid} (numeric tblcatalogue.id).
 * Returns a service <select> driven by tblservices, OR a guidance panel
 * if the catalogue has is_guidance_only=1.
 */
require_once __DIR__ . '/includes/session_start.php';
require('sql.php');
/** @var mysqli $link */

require_once __DIR__ . '/vendor/autoload.php';
use League\CommonMark\CommonMarkConverter;
$mdConverter = new CommonMarkConverter(['html_input' => 'strip', 'allow_unsafe_links' => false]);

$lang    = $_SESSION['lang'] ?? 'en';
$nameCol = $lang === 'fr' ? 'namefr' : 'nameen';
$isFr    = $lang === 'fr';

if (empty($_GET['v1'])) {
    exit;
}
$catalogueid = (int) $_GET['v1'];

// ------------------------------------------------------------------
// Check catalogue flags
// ------------------------------------------------------------------
$catStmt = $link->prepare(
    'SELECT is_guidance_only, guidance_text_en, guidance_text_fr
     FROM tblcatalogue WHERE id = ? AND status = 1 LIMIT 1'
);
$catStmt->bind_param('i', $catalogueid);
$catStmt->execute();
$cat = $catStmt->get_result()->fetch_assoc();
$catStmt->close();

if (!$cat) {
    exit;
}

// Guidance-only catalogue: show panel, no request form
if ($cat['is_guidance_only']) {
    $raw = $isFr ? $cat['guidance_text_fr'] : $cat['guidance_text_en'];
    $html = $raw ? $mdConverter->convert($raw)->getContent() : '';
    ?>
    <section class="alert alert-info" role="status" aria-live="polite" tabindex="-1">
        <?= $html ?>
    </section>
    <?php
    mysqli_close($link);
    exit;
}

// ------------------------------------------------------------------
// Query active services for this catalogue
// ------------------------------------------------------------------
$stmt = $link->prepare(
    "SELECT id, nameen, namefr
     FROM tblservices
     WHERE catalogueid = ? AND status = 1
     ORDER BY $nameCol ASC"
);
$stmt->bind_param('i', $catalogueid);
$stmt->execute();
$services = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
mysqli_close($link);

if (empty($services)) {
    exit;
}

$labelText       = $isFr
    ? 'Lequel des choix ci-dessous décrit le mieux votre demande? <strong>(requis)</strong>'
    : 'Which of the choices below best describes your request? <strong>(required)</strong>';
$placeholderText = $isFr ? 'Faites votre choix' : 'Make your selection';
$noMatchText     = $isFr
    ? 'Les choix listés ne correspondent pas à ma demande.'
    : 'The choices listed do not match my request.';
?>
<label for="serviceid">
    <span class="field-name"><?= $labelText ?></span>
</label>
<select class="form-control" id="serviceid" name="serviceid"
        onchange="ajax2(this.value)" required>
    <option value=""><?= $placeholderText ?></option>
    <?php foreach ($services as $svc): ?>
    <option value="<?= (int) $svc['id'] ?>">
        <?= htmlspecialchars($svc[$nameCol]) ?>
    </option>
    <?php endforeach; ?>
    <option value="0"><?= htmlspecialchars($noMatchText) ?></option>
</select>
