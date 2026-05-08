<?php
/**
 * HOAEXP upload script
 *
 * This script accepts a file upload via HTTP POST and saves it in the
 * directory where the script resides. It expects the uploaded file to be
 * provided under the form field name "file" and will reject any uploads
 * that are not PDF files. Basic sanitization is applied to the file name
 * and the script responds with a 200 status code and 'OK' on success.
 *
 * Note: For additional security you may wish to implement authentication,
 * size limits, or store the uploads outside of the web root.
 */

// Only handle POST requests with a file
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $uploadDir = __DIR__;
    $fileName = basename($_FILES['file']['name']);
    // Sanitize the file name: allow only alphanumeric characters, dashes, underscores and dots
    $fileName = preg_replace('/[^A-Za-z0-9_\.\-]/', '_', $fileName);
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    // Only allow PDF files
    if ($fileExtension !== 'pdf') {
        http_response_code(400);
        echo 'Invalid file type';
        exit;
    }
    // Move the uploaded file into the current directory
    $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $fileName;
    if (move_uploaded_file($_FILES['file']['tmp_name'], $targetPath)) {
        echo 'OK';
    } else {
        http_response_code(500);
        echo 'Upload failed';
    }
} else {
    http_response_code(400);
    echo 'No file';
}