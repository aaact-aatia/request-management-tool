<?php
/**
 * Edit Request - Files Section
 * Displays file upload and file listing table
 */

$blobStorage = new AzureBlobStorageManager();
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

<h2><?php echo $t['files_heading']; ?></h2>

<div class="form-group">
    <label for="fileToUpload"><span class="field-name"><?php echo $t['upload_file']; ?>:</span></label>
    <input type="file" class="form-control" id="fileToUpload" name="fileToUpload[]" multiple>
</div>

<br><br>

<?php
$result_files = mysqli_query($link, "SELECT * FROM tblfiles WHERE requestid = '$requestid'");
$validImageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'tiff', 'svg', 'ico'];

if (mysqli_num_rows($result_files) > 0) {
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
        while ($file = mysqli_fetch_assoc($result_files)) {
            $fileExtension = strtolower($file['type']);
            echo "<tr>";
            echo "<td><input type='checkbox' class='fileCheckbox' value='" . $file['name'] . "'></td>";
            echo "<td>";
            
            if (in_array($fileExtension, $validImageExtensions)) {
                echo "<a href='#' class='image-link' data-src='" . $blobStorage->getFileUrl($file['code']) . "' download>" . $file['name'] . "</a>";
            } else {
                echo "<a href='" . $blobStorage->getFileUrl($file['code']) . "' download>" . $file['name'] . "</a>";
            }
            
            echo "</td>";
            echo "<td>" . $file['type'] . "</td>";
            echo "<td>" . $file['size'] . " KB</td>";
            echo "<td>" . $file['date'] . "</td>";
            echo "<td>";
            echo "<a href='#' class='btn btn-primary download-btn' data-name='" . htmlspecialchars($file['name'], ENT_QUOTES, 'UTF-8') . "' data-file='" . $file['code'] . "'>{$t['download']}</a> ";
            echo "<a class='btn btn-danger delete-btn' style='color:white;' data-file='" . $file['code'] . "'>{$t['delete']}</a>";
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
} else {
    echo "<p>{$t['no_files_found']}</p>";
}
?>
