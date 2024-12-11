<?php
require_once 'config.php';

if (!isset($_GET['code']) || empty($_GET['code'])) {
    header('HTTP/1.0 404 Not Found');
    exit();
}

$download_code = trim($_GET['code']);

try {
    $stmt = $db->prepare("
        SELECT * FROM files 
        WHERE download_code = ? 
        AND (expires_at > NOW() OR expires_at IS NULL)
    ");
    $stmt->execute([$download_code]);
    $file = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$file) {
        header('HTTP/1.0 404 Not Found');
        exit();
    }
    
    $filepath = APP_CONFIG['upload_dir'] . $file['filename'];
    
    if (!file_exists($filepath)) {
        header('HTTP/1.0 404 Not Found');
        exit();
    }
    
    // Set appropriate content type
    header('Content-Type: ' . $file['mime_type']);
    
    // Output file
    readfile($filepath);
    exit();
    
} catch (PDOException $e) {
    error_log("Database error in preview.php: " . $e->getMessage());
    header('HTTP/1.0 500 Internal Server Error');
    exit();
}
?>
