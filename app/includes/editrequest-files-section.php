<?php
/**
 * Edit Request - Files Section
 * Displays file upload and file listing table
 */

$blobStorage = new AzureBlobStorageManager();
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

<div class="form-group">
    <label for="fileToUpload"><span class="field-name"><?php echo $t['upload_file']; ?>:</span></label>
    <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
        <input
            type="file"
            class="form-control"
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

<br><br>

<?php
$result_files = mysqli_query($link, "SELECT * FROM tblfiles WHERE requestid = '$requestid'");
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
            rmt_allow_file_download_code((string) $file['code']);
            echo "<tr>";
            echo "<td><input type='checkbox' class='fileCheckbox' value='" . $file['name'] . "'></td>";
            echo "<td>";
            
            if (in_array($fileExtension, $validImageExtensions)) {
                echo "<a href='#' class='image-link' data-src='" . $blobStorage->getInlineFileUrl((string) $file['code']) . "'>" . $file['name'] . "</a>";
            } else {
                echo "<a href='" . $blobStorage->getFileUrl((string) $file['code']) . "' download>" . $file['name'] . "</a>";
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
            echo "<a href='#' class='btn btn-primary download-btn' data-name='" . htmlspecialchars($file['name'], ENT_QUOTES, 'UTF-8') . "' data-file='" . $file['code'] . "'>{$t['download']}</a> ";
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
<a class="btn btn-primary" style="color:white;" id="downloadAll"><?php echo $t['download_all']; ?></a>
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

<div class="image-preview" id="imagePreview" role="dialog" aria-modal="true" aria-labelledby="imagePreviewTitle" aria-hidden="true">
    <h2 id="imagePreviewTitle" class="sr-only"><?php echo $lang === 'fr' ? 'Aperçu de l\'image' : 'Image preview'; ?></h2>
    <button class="close-btn" id="closePreview" aria-label="<?php echo $lang === 'fr' ? 'Fermer l\'aperçu' : 'Close preview'; ?>">&times;</button>
    <img id="previewImage" src="" alt="">
    <p id="imageAnnouncement" class="sr-only" aria-live="assertive"></p>
</div>
<?php
}

} else {
    echo "<p>{$t['no_files_found']}</p>";
}
?>
