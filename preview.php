<?php
require_once 'config.php';

function displayError($message) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Preview Error</title>
        <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    </head>
    <body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen flex items-center justify-center">
        <div class="max-w-md w-full mx-4">
            <div class="bg-white rounded-xl shadow-xl p-8 text-center">
                <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-exclamation-triangle text-2xl text-red-500"></i>
                </div>
                <h1 class="text-2xl font-bold text-gray-800 mb-2">Preview Error</h1>
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

// Validate preview code
if (!isset($_GET['code']) || empty($_GET['code'])) {
    displayError('No preview code provided');
}

$preview_code = trim($_GET['code']);

try {
    // Check if file exists and hasn't expired
    $stmt = $db->prepare("
        SELECT * FROM files 
        WHERE download_code = ? 
        AND (expires_at > NOW() OR expires_at IS NULL)
    ");
    $stmt->execute([$preview_code]);
    $file = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$file) {
        displayError('File not found or has expired');
    }
    
    $filepath = APP_CONFIG['upload_dir'] . $file['filename'];
    
    if (!file_exists($filepath)) {
        displayError('File not found on server');
    }

    // Get file extension
    $ext = strtolower(pathinfo($file['original_filename'], PATHINFO_EXTENSION));
    
    // Function to format file size
    function formatFileSize($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>File Preview - SecureShare</title>
        <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    </head>
    <body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen p-4">
        <div class="container mx-auto max-w-4xl">
            <!-- File Info Card -->
            <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
                <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <?php
                        // Show appropriate icon based on file type
                        $icon_class = 'fa-file';
                        switch($ext) {
                            case 'pdf': $icon_class = 'fa-file-pdf'; break;
                            case 'doc': case 'docx': $icon_class = 'fa-file-word'; break;
                            case 'jpg': case 'jpeg': case 'png': $icon_class = 'fa-file-image'; break;
                            case 'zip': case 'rar': $icon_class = 'fa-file-archive'; break;
                        }
                        ?>
                        <div class="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center">
                            <i class="fas <?php echo $icon_class; ?> text-2xl text-indigo-500"></i>
                        </div>
                        <div>
                            <h1 class="text-xl font-semibold text-gray-800"><?php echo htmlspecialchars($file['original_filename']); ?></h1>
                            <p class="text-sm text-gray-500">
                                Size: <?php echo formatFileSize($file['file_size']); ?> • 
                                Expires: <?php echo date('M j, Y g:i A', strtotime($file['expires_at'])); ?>
                            </p>
                        </div>
                    </div>
                    <a href="download.php?code=<?php echo $preview_code; ?>&direct=1" 
                       class="inline-flex items-center px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 focus:ring-4 focus:ring-indigo-200 transition duration-200 whitespace-nowrap">
                        <i class="fas fa-download mr-2"></i>
                        Download File
                    </a>
                </div>
            </div>

            <!-- Preview Area -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="preview-container">
                    <?php
                    // Display preview based on file type
                    if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
                        echo '<img src="download.php?code=' . $preview_code . '&direct=1" 
                                  alt="' . htmlspecialchars($file['original_filename']) . '" 
                                  class="max-w-full h-auto mx-auto rounded-lg">';
                    } elseif ($ext === 'pdf') {
                        echo '<iframe src="download.php?code=' . $preview_code . '&direct=1" 
                                    class="w-full h-[600px] rounded-lg border-0"></iframe>';
                    } else {
                        echo '<div class="text-center py-12">
                                <i class="fas ' . $icon_class . ' text-6xl text-gray-300 mb-4"></i>
                                <p class="text-gray-600">Preview not available for this file type</p>
                                <p class="text-sm text-gray-500 mt-2">Click the download button above to access the file</p>
                              </div>';
                    }
                    ?>
                </div>
            </div>

            <!-- Security Notice -->
            <div class="text-center mt-6 text-sm text-gray-500">
                <p><i class="fas fa-shield-alt mr-1"></i> This file is securely shared through SecureShare</p>
            </div>
        </div>
    </body>
    </html>
    <?php
    
} catch (Exception $e) {
    error_log("Error in preview.php: " . $e->getMessage());
    displayError('An error occurred while processing your preview');
}
?>
