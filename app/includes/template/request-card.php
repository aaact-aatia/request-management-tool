<?php
/**
 * Reusable request card renderer.
 *
 * Expects an associative array in $requestCard:
 * - tags, panelClass, requestUrl, requestCode, title
 * - statusPrefix, statusText, statusLabelClass
 * - slaLabel (optional), slaAlertClass
 * - bodyHtml, footerHtml (optional pre-rendered HTML)
 */

if (empty($requestCard) || !is_array($requestCard)) {
	return;
}

$tags = $requestCard['tags'] ?? '';
$panelClass = $requestCard['panelClass'] ?? 'panel-default';
$requestUrl = $requestCard['requestUrl'] ?? '#';
$requestCode = $requestCard['requestCode'] ?? '';
$title = $requestCard['title'] ?? '';
$statusPrefix = $requestCard['statusPrefix'] ?? '';
$statusText = $requestCard['statusText'] ?? '';
$statusLabelClass = $requestCard['statusLabelClass'] ?? 'label-default';
$slaLabel = $requestCard['slaLabel'] ?? '';
$slaAlertClass = $requestCard['slaAlertClass'] ?? 'alert-warning';
$bodyHtml = $requestCard['bodyHtml'] ?? '';
$footerHtml = $requestCard['footerHtml'] ?? '';
?>
<div class="col-sm-6 col-md-4 mrgn-bttm-md" data-wb-tags="<?= htmlspecialchars($tags) ?>">
	<div class="panel <?= htmlspecialchars($panelClass) ?> hght-inhrt">
		<div class="panel-heading">
			<h3 class="h5 mrgn-tp-sm">
				<a href="<?= htmlspecialchars($requestUrl) ?>"><?= htmlspecialchars($requestCode) ?></a>
			</h3>
			<p><?= htmlspecialchars($title) ?></p>
			<p><?= htmlspecialchars($statusPrefix) ?>: <span class="label <?= htmlspecialchars($statusLabelClass) ?>"><?= htmlspecialchars($statusText) ?></span></p>
		</div>
		<div class="panel-body">
			<?php if (!empty($slaLabel)): ?>
				<div class="alert <?= htmlspecialchars($slaAlertClass) ?>">
					<p><?= htmlspecialchars($slaLabel) ?></p>
				</div>
			<?php endif; ?>
			<?= $bodyHtml ?>
		</div>
		<?php if (!empty($footerHtml)): ?>
		<div class="panel-footer">
			<?= $footerHtml ?>
		</div>
		<?php endif; ?>
	</div>
</div>