<?php
/**
 * Open Request Cascade — Tier 1: Services for a Catalogue
 *
 * Receives ?v1={catalogueid} (numeric tblcatalogue.id).
 *
 * Resolution order (per design doc):
 *   1. Published attached custom flow  (intake_flow_id non-null)
 *   2. Guidance-only
 *   3. Active children (service dropdown)
 *   4. Direct continuation
 */
require_once __DIR__ . '/includes/session_start.php';
require('sql.php');
/** @var mysqli $link */
require_once __DIR__ . '/includes/intake-flow-helpers.php';

require_once __DIR__ . '/vendor/autoload.php';
use League\CommonMark\CommonMarkConverter;
$mdConverter = new CommonMarkConverter(['html_input' => 'strip', 'allow_unsafe_links' => false]);

$lang    = $_SESSION['lang'] ?? 'en';
$nameCol = $lang === 'fr' ? 'namefr' : 'nameen';
$isFr    = $lang === 'fr';

$continueLabel = $isFr ? 'Continuer' : 'Continue';

if (empty($_GET['v1'])) {
    exit;
}
$catalogueid = (int) $_GET['v1'];

// ------------------------------------------------------------------
// Fetch catalogue row
// ------------------------------------------------------------------
$catStmt = $link->prepare(
    'SELECT is_guidance_only, guidance_text_en, guidance_text_fr, intake_flow_id
     FROM tblcatalogue WHERE id = ? AND status = 1 LIMIT 1'
);
$catStmt->bind_param('i', $catalogueid);
$catStmt->execute();
$cat = $catStmt->get_result()->fetch_assoc();
$catStmt->close();

if (!$cat) {
    mysqli_close($link);
    exit;
}

// ------------------------------------------------------------------
// Rule 1: Published custom intake flow — checked FIRST, before any
// other behaviour. Connection is still open here.
// ------------------------------------------------------------------
if (!empty($cat['intake_flow_id'])) {
    $flow = rmt_intake_load_flow($link, (int) $cat['intake_flow_id']);
    mysqli_close($link);
    if ($flow) {
        ?>
        <div class="form-group form-buttons" data-intake-autostart="1">
            <input type="hidden" name="action" value="start">
            <button type="submit" formaction="/intake-flow.php"
                    class="btn btn-primary"><?= htmlspecialchars($continueLabel) ?></button>
        </div>
        <?php
    } else {
        // Flow attached but draft/archived/invalid — fail closed with accessible error
        $errMsg = $isFr
            ? "Ce service n'est pas disponible pour le moment. Veuillez réessayer plus tard ou contacter le Bureau de l'accessibilité de la TI."
            : "This service is currently unavailable. Please try again later or contact the IT Accessibility Office.";
        ?>
        <section class="alert alert-danger" role="alert">
            <p><?= htmlspecialchars($errMsg) ?></p>
        </section>
        <?php
    }
    exit;
}

// ------------------------------------------------------------------
// Rule 2: Guidance-only catalogue
// ------------------------------------------------------------------
if ($cat['is_guidance_only']) {
    $raw  = $isFr ? $cat['guidance_text_fr'] : $cat['guidance_text_en'];
    $html = $raw ? $mdConverter->convert($raw)->getContent() : '';
    mysqli_close($link);
    ?>
    <section class="alert alert-info" role="status" aria-live="polite" tabindex="-1">
        <?= $html ?>
    </section>
    <?php
    exit;
}

// ------------------------------------------------------------------
// Rule 3: Active children — fetch services
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

// Rule 4: No active children, no flow, not guidance-only — direct continuation
if (empty($services)) {
    ?>
    <div class="form-group form-buttons">
        <button type="submit" class="btn btn-primary"><?= htmlspecialchars($continueLabel) ?></button>
    </div>
    <?php
    exit;
}

$labelText       = $isFr
    ? 'Lequel des choix ci-dessous décrit le mieux votre demande ? <strong>(requis)</strong>'
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
