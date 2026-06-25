<?php
require_once('sql.php');
require_once('BlobStorage.php');

/** @var mysqli $link */

require_once __DIR__ . '/includes/session_start.php';

// Ensure the 'code' parameter is provided
if (isset($_GET['code'])) {

    $fileCode = mysqli_real_escape_string($link, $_GET['code']); // Sanitize the code

    // Fetch the file details from the database
    $result_files = mysqli_query($link, "SELECT * FROM tblfiles WHERE code = '$fileCode'");
    if (!$result_files) {
        error_log("Error in SQL query: " . mysqli_error($link)); // Log the error for debugging
        http_response_code(500); // Internal server error
        exit;
    }

    if (mysqli_num_rows($result_files) > 0) {

        // Fetch the file details
        $file = mysqli_fetch_array($result_files);
        $originalFileName = $file['name']; // The name of the file

        $sanitizedFileName = sanitizeFileName($originalFileName); // Sanitize the file name

        $blobStorage = new AzureBlobStorageManager();
        $filePath = $blobStorage->getFileUrl($fileCode);

        if ($filePath === '') {
            error_log("File download requested, but no file storage backend is configured.");
            http_response_code(404);
            exit;
        }

        // Set headers to prompt a file download
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $sanitizedFileName . '"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Stream the file directly to the user
        ob_end_clean(); // End and discard the buffer to prevent extra output
        readfile($filePath); // This will send the file content directly to the browser

        exit;
    } else {
        http_response_code(404); // File not found
        exit;
    }
} else {
    http_response_code(400); // Bad request
    exit;
}

// Function to sanitize the file name
function sanitizeFileName($fileName) {
    // Replace spaces, apostrophes, and dashes with underscores
    $sanitizedName = preg_replace('/[\'\s-]/', '_', $fileName);
    // Remove any other special characters
    $sanitizedName = preg_replace('/[^A-Za-z0-9_.]/', '', $sanitizedName);
    return $sanitizedName;
}
?>
