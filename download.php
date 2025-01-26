<?php
// db
require_once 'config.php';

function displayError($message) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Download Error</title>
        <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    </head>
    <body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen flex items-center justify-center">
        <div class="max-w-md w-full mx-4">
            <div class="bg-white rounded-xl shadow-xl p-8 text-center">
                <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-exclamation-triangle text-2xl text-red-500"></i>
                </div>
                <h1 class="text-2xl font-bold text-gray-800 mb-2">Download Error</h1>
                <p class="text-gray-600 mb-6"><?php echo htmlspecialchars($message); ?></p>
                <a href="<?php echo APP_CONFIG['base_url']; ?>" 
                   class="inline-flex items-center justify-center px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 focus:ring-4 focus:ring-indigo-200 transition duration-200">
                    <i class="fas fa-home mr-2"></i>
                    Return to Homepage
                </a>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// Validate download code
if (!isset($_GET['direct']) && empty($_GET['direct'])) {
    header('Location: preview.php?code=' . $download_code);
    exit();
}

$download_code = trim($_GET['code']);

try {
    // Check if file exists and hasn't expired
    $stmt = $db->prepare("
        SELECT * FROM files 
        WHERE download_code = ? 
        AND (expires_at > NOW() OR expires_at IS NULL)
    ");
    $stmt->execute([$download_code]);
    $file = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$file) {
        displayError('File not found or has expired');
    }
    
    $filepath = APP_CONFIG['upload_dir'] . $file['filename'];
    
    if (!file_exists($filepath)) {
        // Log error for administrator
        error_log("File not found on server: {$filepath}");
        displayError('File not found on server');
    }
    
    // Update download count
    $stmt = $db->prepare("UPDATE files SET downloads = downloads + 1 WHERE id = ?");
    $stmt->execute([$file['id']]);
    
    // Clean filename for headers
    $filename = preg_replace('/[^a-zA-Z0-9\-\_\.]/', '_', $file['original_filename']);
    
    // Set headers for download
    header('Content-Type: ' . $file['mime_type']);
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . $file['file_size']);
    header('Cache-Control: private, no-cache, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Output file in chunks to handle large files
    if ($handle = fopen($filepath, 'rb')) {
        while (!feof($handle) && connection_status() == 0) {
            print(fread($handle, 8192));
            flush();
        }
        fclose($handle);
    }
    
    // Log successful download
    error_log("Successful download: {$file['original_filename']} (Code: {$download_code})");
    exit();
    
} catch (PDOException $e) {
    error_log("Database error in download.php: " . $e->getMessage());
    displayError('Database error occurred while processing your download');
} catch (Exception $e) {
    error_log("General error in download.php: " . $e->getMessage());
    displayError('An error occurred while processing your download');
}

// If we somehow get here, show an error
displayError('Unexpected error occurred');
?>
