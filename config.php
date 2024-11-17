<?php
// Database Configuration
const DB_CONFIG = [
    'host' => 'localhost',
    'name' => 'your_db',
    'user' => 'db_user',
    'pass' => ''
];

// Application Configuration
const APP_CONFIG = [
    'upload_dir' => __DIR__ . '/uploads/',
    'max_file_size' => 100 * 1024 * 1024, // 100MB
    'base_url' => 'http://example.com',
    'allowed_extensions' => ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'zip', 'rar'],
    'expiration_options' => [
        ['value' => 1, 'label' => '24 hours'],
        ['value' => 7, 'label' => '7 days'],
        ['value' => 30, 'label' => '30 days']
    ]
];

// Initialize Database Connection
function initDatabase() {
    try {
        $db = new PDO(
            "mysql:host=" . DB_CONFIG['host'] . ";dbname=" . DB_CONFIG['name'],
            DB_CONFIG['user'],
            DB_CONFIG['pass']
        );
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $db;
    } catch(PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}

// Initialize Application
function initApplication() {
    // Create uploads directory if it doesn't exist
    if (!file_exists(APP_CONFIG['upload_dir'])) {
        mkdir(APP_CONFIG['upload_dir'], 0777, true);
    }
    
    // Initialize database
    $db = initDatabase();
    
    // Create table if it doesn't exist
    $db->exec("CREATE TABLE IF NOT EXISTS files (
        id INT AUTO_INCREMENT PRIMARY KEY,
        filename VARCHAR(255) NOT NULL,
        original_filename VARCHAR(255) NOT NULL,
        file_size BIGINT NOT NULL,
        mime_type VARCHAR(100) NOT NULL,
        download_code VARCHAR(32) NOT NULL UNIQUE,
        expires_at DATETIME,
        downloads INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    return $db;
}

// Helper Functions
function generateDownloadCode() {
    return md5(uniqid() . random_bytes(10));
}

function getExpirationDate($days) {
    return date('Y-m-d H:i:s', strtotime("+$days days"));
}

function validateFileSize($size) {
    return $size <= APP_CONFIG['max_file_size'];
}

function validateFileExtension($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($ext, APP_CONFIG['allowed_extensions']);
}

// Initialize the application and database connection
$db = initApplication();
