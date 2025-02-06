<?php
/**
 * File Download Handler
 * 
 * This script handles secure file downloads based on unique download codes.
 * It verifies the download code, checks file existence and expiration,
 * and streams the file to the user with appropriate headers.
 
 * Dependencies:
 * - config.php: Contains database connection and APP_CONFIG settings
 * - Requires a 'files' table with columns: download_code, filename, mime_type, expires_at
 */

require_once 'config.php';

// Validate download code presence
if (!isset($_GET['code']) || empty($_GET['code'])) {
    header('HTTP/1.0 404 Not Found');
    exit();
}

// Sanitize the download code
$download_code = trim($_GET['code']);

try {
    // Query to fetch file details
    // Only returns files that haven't expired (expires_at > NOW()) or have no expiration (expires_at IS NULL)
    $stmt = $db->prepare("
        SELECT * FROM files 
        WHERE download_code = ? 
        AND (expires_at > NOW() OR expires_at IS NULL)
    ");
    $stmt->execute([$download_code]);
    $file = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check if file record exists
    if (!$file) {
        header('HTTP/1.0 404 Not Found');
        exit();
    }

    // Construct full filepath
    $filepath = APP_CONFIG['upload_dir'] . $file['filename'];

    // Verify file exists on disk
    if (!file_exists($filepath) || !is_readable($filepath)) {
        error_log("File not found or not readable: " . $filepath);
        header('HTTP/1.0 404 Not Found');
        exit();
    }

    // Prevent caching of downloads
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache');

    // Set content type based on file's mime type
    header('Content-Type: ' . $file['mime_type']);
    
    // Set content length if file size can be determined
    if (filesize($filepath)) {
        header('Content-Length: ' . filesize($filepath));
    }

    // Optional: Set content disposition if you want to suggest a filename
    // header('Content-Disposition: attachment; filename="' . basename($file['filename']) . '"');

    // Output file contents
    readfile($filepath);
    exit();

} catch (PDOException $e) {
    error_log("Database error in download handler: " . $e->getMessage());
    header('HTTP/1.0 500 Internal Server Error');
    exit();
} catch (Exception $e) {
    error_log("Unexpected error in download handler: " . $e->getMessage());
    header('HTTP/1.0 500 Internal Server Error');
    exit();
}
?>