<?php
/**
 * Edit Request - Files Section
 * Displays file upload and file listing table
 */

$blobStorage = new AzureBlobStorageManager();
require_once __DIR__ . '/csrf.php';
?>

<h2><?php echo $t['files_heading']; ?></h2>

<?php if ($status === 'uploadsuccess'): ?>
<section id="upload-status-message" class="alert alert-success" role="status" aria-live="polite" tabindex="-1">
    <h3><?php echo htmlspecialchars($t['upload_success_heading'], ENT_QUOTES, 'UTF-8'); ?></h3>
    <p><?php echo htmlspecialchars($t['upload_success_message'], ENT_QUOTES, 'UTF-8'); ?></p>
</section>
<?php elseif ($status === 'uploadfailed'): ?>
<section id="upload-status-message" class="alert alert-danger" role="alert" aria-live="assertive" tabindex="-1">
    <h3><?php echo htmlspecialchars($t['upload_failed_heading'], ENT_QUOTES, 'UTF-8'); ?></h3>
    <p><?php echo !empty($uploadErrorMessage) ? htmlspecialchars($uploadErrorMessage, ENT_QUOTES, 'UTF-8') : htmlspecialchars($t['upload_failed_message'], ENT_QUOTES, 'UTF-8'); ?></p>
</section>
<?php endif; ?>

<?php if (rmt_file_upload_policy()['enabled']): ?>
<div class="form-group">
    <label for="fileToUpload"><span class="field-name"><?php echo $t['upload_file']; ?>:</span></label>
    <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
        <input
            type="file"
            class="form-control full-width"
            id="fileToUpload"
            name="fileToUpload[]"
            multiple
            accept="<?php echo htmlspecialchars(rmt_file_upload_accept_attribute(), ENT_QUOTES, 'UTF-8'); ?>"
            aria-describedby="fileToUploadHelp"
            <?php echo !empty($uploadErrorMessage) ? 'aria-invalid="true"' : ''; ?>
            style="flex:1 1 320px;"
        >
        <button type="submit" name="form_action" value="upload_files" class="btn btn-primary" formnovalidate><?php echo htmlspecialchars($t['upload_button'], ENT_QUOTES, 'UTF-8'); ?></button>
    </div>
    <p id="fileToUploadHelp" class="small text-muted"><?php echo htmlspecialchars(rmt_file_upload_hint($lang), ENT_QUOTES, 'UTF-8'); ?></p>
</div>
<?php endif; ?>

<br><br>

<?php
$result_files = mysqli_query($link, "SELECT f.*, t.catalogueid, t.serviceid, t.subserviceid, t.workerid FROM tblfiles f INNER JOIN tbltriage t ON t.requestid = f.requestid WHERE f.requestid = '$requestid'");
$validImageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'tiff', 'svg', 'ico'];

$files = [];
$hasImageAttachment = false;

while ($fileRow = mysqli_fetch_assoc($result_files)) {
    $files[] = $fileRow;
    $fileExtension = strtolower($fileRow['type'] ?? '');
    if (in_array($fileExtension, $validImageExtensions, true)) {
        $hasImageAttachment = true;
    }
}

if (!empty($files)) {
?>
<table class="wb-tables table" data-wb-tables='{ "ordering": true, "searching": true }' id="fileTable">
    <thead>
        <tr>
            <th><?php echo $t['checkbox']; ?></th>
            <th><?php echo $t['file_name']; ?></th>
            <th><?php echo $t['file_type']; ?></th>
            <th><?php echo $t['file_size']; ?></th>
            <th><?php echo $t['date_submitted']; ?></th>
            <th><?php echo $t['action']; ?></th>
        </tr>
    </thead>
    <tbody>
        <?php
        foreach ($files as $file) {
            $fileExtension = strtolower($file['type']);
            $fileName = htmlspecialchars((string) $file['name'], ENT_QUOTES, 'UTF-8');
            $downloadUrl = htmlspecialchars($blobStorage->getFileUrl((string) $file['code']), ENT_QUOTES, 'UTF-8');
            $selectFileLabel = htmlspecialchars(sprintf($t['select_file'], (string) $file['name']), ENT_QUOTES, 'UTF-8');
            echo "<tr>";
            echo "<td><input type='checkbox' class='fileCheckbox' value='" . $fileName . "' aria-label='" . $selectFileLabel . "'></td>";
            echo "<td>";
            
            if (in_array($fileExtension, $validImageExtensions)) {
                $previewLabel = htmlspecialchars(sprintf($t['preview_image'], (string) $file['name']), ENT_QUOTES, 'UTF-8');
                echo "<button type='button' class='image-link btn btn-link' data-src='" . htmlspecialchars($blobStorage->getInlineFileUrl((string) $file['code']), ENT_QUOTES, 'UTF-8') . "' data-name='" . $fileName . "' aria-label='" . $previewLabel . "'>" . $fileName . "</button>";
            } else {
                echo "<a href='" . $downloadUrl . "'>" . $fileName . "</a>";
            }
            
            echo "</td>";
            echo "<td>" . $file['type'] . "</td>";
            echo "<td>" . $file['size'] . " KB</td>";
            $fileDate = trim((string)($file['dateadded'] ?? $file['date'] ?? ''));
            if ($fileDate === '') {
                $fileDate = $blobStorage->getFileLastModified((string) $file['code']) ?? ($t['na'] ?? 'N/A');
            }
            echo "<td>" . htmlspecialchars((string) $fileDate, ENT_QUOTES, 'UTF-8') . "</td>";
            echo "<td>";
            echo "<a href='" . $downloadUrl . "' class='btn btn-primary download-btn' data-name='" . $fileName . "' data-file='" . htmlspecialchars((string) $file['code'], ENT_QUOTES, 'UTF-8') . "'>{$t['download']}</a> ";
            if (rmt_can_delete_file($link, $file)) {
                echo "<input type='hidden' name='request_id' value='" . (int) $requestuid . "'>";
                echo "<input type='hidden' name='csrf_token' value='" . htmlspecialchars(rmt_csrf_token('file-delete'), ENT_QUOTES, 'UTF-8') . "'>";
                echo "<button type='submit' class='btn btn-danger' formaction='/includes/delete-file.php' formmethod='post' formnovalidate name='file_id' value='" . (int) $file['id'] . "' onclick=\"return confirm('" . htmlspecialchars($t['delete_file_confirmation'], ENT_QUOTES, 'UTF-8') . "');\">" . htmlspecialchars($t['delete'], ENT_QUOTES, 'UTF-8') . "</button>";
            }
            echo "</td>";
            echo "</tr>";
        }
        ?>
    </tbody>
</table>

<br>
<div class="form-group">
    <input type="checkbox" id="selectAll">
    <label for="selectAll"><span class="field-name"><?php echo $t['select_all']; ?></span></label>
</div>
<button type="button" class="btn btn-primary" id="downloadAll" data-no-selection-message="<?php echo htmlspecialchars($t['select_file_to_download'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($t['download_all'], ENT_QUOTES, 'UTF-8'); ?></button>
<?php

if ($hasImageAttachment) {
?>
<style>
.image-preview {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.image-preview img {
    max-width: 90%;
    max-height: 90%;
    border-radius: 10px;
}

.close-btn {
    position: absolute;
    top: 15px;
    right: 20px;
    background: transparent;
    border: none;
    font-size: 30px;
    color: white;
    cursor: pointer;
}

.close-btn:focus {
    outline: 2px solid white;
}

.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    border: 0;
}
</style>

<div class="image-preview" id="imagePreview" role="dialog" aria-modal="true" aria-labelledby="imagePreviewTitle" aria-hidden="true" data-opened-message="<?php echo htmlspecialchars($t['image_preview_opened'], ENT_QUOTES, 'UTF-8'); ?>">
    <h2 id="imagePreviewTitle" class="sr-only"><?php echo htmlspecialchars($t['image_preview_title'], ENT_QUOTES, 'UTF-8'); ?></h2>
    <button type="button" class="close-btn" id="closePreview" aria-label="<?php echo htmlspecialchars($t['close_image_preview'], ENT_QUOTES, 'UTF-8'); ?>">&times;</button>
    <img id="previewImage" src="" alt="">
    <p id="imageAnnouncement" class="sr-only" aria-live="assertive"></p>
</div>
<?php
}

} else {
    echo "<p>{$t['no_files_found']}</p>";
}
?>
