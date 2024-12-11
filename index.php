<?php
require_once 'config.php';

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $response = ['success' => false, 'message' => ''];
    
    try {
        $file = $_FILES['file'];
        $expiration_days = isset($_POST['expiration']) ? (int)$_POST['expiration'] : 7;
        
        // Validate file
        if (!validateFileSize($file['size'])) {
            throw new Exception('File size exceeds limit of ' . (APP_CONFIG['max_file_size'] / 1024 / 1024) . 'MB');
        }
        
        if (!validateFileExtension($file['name'])) {
            throw new Exception('File type not allowed');
        }
        
        // Generate unique filename
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $new_filename = uniqid() . '.' . $ext;
        $download_code = generateDownloadCode();
        $expires_at = getExpirationDate($expiration_days);
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], APP_CONFIG['upload_dir'] . $new_filename)) {
            $stmt = $db->prepare("INSERT INTO files (filename, original_filename, file_size, mime_type, download_code, expires_at) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$new_filename, $file['name'], $file['size'], $file['type'], $download_code, $expires_at]);
            
            $download_link = APP_CONFIG['base_url'] . '/download.php?code=' . $download_code;
            $response = [
                'success' => true,
                'message' => 'File uploaded successfully!',
                'download_link' => $download_link,
                'expires_at' => $expires_at,
                'file_name' => $file['name'],
                'file_size' => formatFileSize($file['size'])
            ];
        } else {
            throw new Exception('Failed to upload file');
        }
    } catch (Exception $e) {
        $response = ['success' => false, 'message' => $e->getMessage()];
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

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
    <title>SecureShare - Simple & Secure File Sharing</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">
    <!-- Toast Notification -->
    <div id="toast" class="fixed top-4 right-4 transform translate-x-full transition-transform duration-300 z-50">
        <div class="bg-white rounded-lg shadow-lg p-4 flex items-center space-x-3">
            <i class="fas fa-check-circle text-green-500 text-xl"></i>
            <p class="text-gray-700" id="toastMessage"></p>
        </div>
    </div>

    <div class="container mx-auto px-4 py-8 md:py-12">
        <div class="max-w-4xl mx-auto">
            <!-- Header Section -->
            <div class="text-center mb-8">
                <div class="inline-block p-3 bg-white rounded-full shadow-lg mb-4">
                    <i class="fas fa-shield-alt text-4xl text-indigo-500"></i>
                </div>
                <h1 class="text-3xl md:text-4xl font-bold text-gray-800 mb-2">SecureShare</h1>
                <p class="text-gray-600 max-w-lg mx-auto">Share files securely with automatic expiration. Your files are encrypted and automatically deleted when they expire.</p>
            </div>

            <!-- Main Card -->
            <div class="bg-white rounded-2xl shadow-xl p-6 md:p-8">
                <!-- File Upload Area -->
                <div id="uploadArea" class="relative">
                    <div class="upload-zone border-3 border-dashed border-indigo-200 rounded-xl p-6 md:p-10 bg-indigo-50 transition-all duration-300 hover:border-indigo-300">
                        <form id="uploadForm" class="space-y-6">
                            <div class="file-input-wrapper">
                                <input type="file" id="fileInput" class="hidden" required>
                                <label for="fileInput" class="cursor-pointer block">
                                    <div class="flex flex-col items-center space-y-4">
                                        <div class="w-16 h-16 md:w-20 md:h-20 bg-white rounded-full shadow-inner flex items-center justify-center">
                                            <i class="fas fa-cloud-upload-alt text-3xl md:text-4xl text-indigo-500"></i>
                                        </div>
                                        <div class="text-center space-y-2">
                                            <div class="relative">
                                                <p class="text-gray-700 font-medium">
                                                    <span class="hidden md:inline">Drag & drop your file here or </span>
                                                    <span class="text-indigo-600">browse files</span>
                                                </p>
                                            </div>
                                            <div class="space-y-1">
                                                <p class="text-sm text-gray-500">Maximum file size: 100MB</p>
                                                <p class="text-xs text-gray-400">Supported formats: JPG, PNG, PDF, DOC, DOCX, ZIP, RAR</p>
                                            </div>
                                        </div>
                                    </div>
                                </label>
                            </div>

                            <!-- Selected File Preview -->
                            <div id="filePreview" class="hidden">
                                <div class="bg-white rounded-lg p-4 shadow-sm">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-3">
                                            <i class="fas fa-file-alt text-indigo-500 text-xl"></i>
                                            <div>
                                                <p class="font-medium text-gray-700" id="selectedFileName"></p>
                                                <p class="text-sm text-gray-500" id="selectedFileSize"></p>
                                            </div>
                                        </div>
                                        <button type="button" onclick="removeFile()" class="text-gray-400 hover:text-gray-600">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Upload Options -->
                            <div class="flex flex-col md:flex-row md:items-center space-y-4 md:space-y-0 md:space-x-4">
                                <div class="flex-1">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Expiration time:</label>
                                    <select name="expiration" class="w-full p-3 border rounded-lg bg-white shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                        <?php foreach (APP_CONFIG['expiration_options'] as $option): ?>
                                            <option value="<?php echo $option['value']; ?>"<?php echo $option['value'] === 7 ? ' selected' : ''; ?>>
                                                <?php echo $option['label']; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit" class="w-full md:w-auto px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 focus:ring-4 focus:ring-indigo-200 transition duration-200 disabled:opacity-50 disabled:cursor-not-allowed" id="uploadButton" disabled>
                                    <i class="fas fa-upload mr-2"></i>
                                    Upload File
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Upload Progress -->
                    <div id="uploadProgress" class="hidden mt-6 space-y-3">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-600">Uploading...</span>
                            <span class="text-gray-600" id="progressPercentage">0%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-indigo-600 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                        </div>
                    </div>
                </div>

                <!-- Upload Success -->
                <div id="uploadSuccess" class="hidden mt-6">
                    <div class="bg-green-50 border border-green-200 rounded-lg p-6">
                        <div class="flex items-center mb-4">
                            <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center mr-3">
                                <i class="fas fa-check text-green-500"></i>
                            </div>
                            <h3 class="text-lg font-semibold text-green-800">File uploaded successfully!</h3>
                        </div>
                        <div class="space-y-4">
                            <div class="bg-white rounded-lg p-4 border border-gray-100">
                                <div class="flex items-center space-x-3 mb-3">
                                    <i class="fas fa-file-alt text-indigo-500"></i>
                                    <div>
                                        <p class="font-medium text-gray-700" id="uploadedFileName"></p>
                                        <p class="text-sm text-gray-500" id="uploadedFileSize"></p>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600 mb-2">Share this secure link:</p>
                                <div class="flex items-center space-x-2">
                                    <div class="flex-1 relative">
                                        <input type="text" id="downloadLink" class="w-full p-3 pr-10 border rounded-lg bg-white shadow-sm" readonly>
                                        <button onclick="copyLink()" class="absolute right-2 top-1/2 transform -translate-y-1/2 p-2 text-gray-400 hover:text-gray-600">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                </div>
                                <p class="text-sm text-gray-500 mt-2">
                                    <i class="far fa-clock mr-1"></i>
                                    Expires: <span id="expirationDate"></span>
                                </p>
                            </div>
                            <button onclick="resetUpload()" class="w-full md:w-auto px-6 py-3 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 focus:ring-4 focus:ring-gray-200 transition duration-200">
                                <i class="fas fa-arrow-left mr-2"></i>
                                Upload Another File
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const dropArea = document.querySelector('.upload-zone');
        const fileInput = document.querySelector('#fileInput');
        const uploadButton = document.querySelector('#uploadButton');
        const progressBar = document.querySelector('#uploadProgress div');
        const progressPercentage = document.querySelector('#progressPercentage');
        const filePreview = document.querySelector('#filePreview');
        const uploadArea = document.querySelector('#uploadArea');
        const uploadSuccess = document.querySelector('#uploadSuccess');
        const uploadProgress = document.querySelector('#uploadProgress');

        // Drag and drop handling
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropArea.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            dropArea.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropArea.addEventListener(eventName, unhighlight, false);
        });

        function highlight(e) {
            dropArea.classList.add('border-indigo-400', 'bg-indigo-100');
        }

        function unhighlight(e) {
            dropArea.classList.remove('border-indigo-400', 'bg-indigo-100');
        }

        dropArea.addEventListener('drop', handleDrop, false);

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            fileInput.files = files;
            updateFilePreview();
        }

        // File input & type handling
        fileInput.addEventListener('change', updateFilePreview);

        function updateFilePreview() {
            const file = fileInput.files[0];
            if (file) {
                document.getElementById('selectedFileName').textContent = file.name;
                document.getElementById('selectedFileSize').textContent = formatFileSize(file.size);
                filePreview.classList.remove('hidden');
                uploadButton.disabled = false;
            } else {
                filePreview.classList.add('hidden');
                uploadButton.disabled = true;
            }
        }

        function removeFile() {
            fileInput.value = '';
            filePreview.classList.add('hidden');
            uploadButton.disabled = true;
        }

        function formatFileSize(bytes) {
            const units = ['B', 'KB', 'MB', 'GB'];
            let size = bytes;
            let unitIndex = 0;
            
            while (size >= 1024 && unitIndex < units.length - 1) {
                size /= 1024;
                unitIndex++;
            }

return `${size.toFixed(2)} ${units[unitIndex]}`;
        }

        // Form submission with progress
        document.getElementById('uploadForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData();
            const file = fileInput.files[0];
            
            if (!file) {
                showToast('Please select a file first', 'error');
                return;
            }
            
            formData.append('file', file);
            formData.append('expiration', document.querySelector('select[name="expiration"]').value);
            
            // Show progress and hide file preview
            uploadProgress.classList.remove('hidden');
            uploadButton.disabled = true;
            
            try {
                const xhr = new XMLHttpRequest();
                
                xhr.upload.onprogress = function(e) {
                    if (e.lengthComputable) {
                        const percentComplete = Math.round((e.loaded / e.total) * 100);
                        progressBar.style.width = percentComplete + '%';
                        progressPercentage.textContent = percentComplete + '%';
                    }
                };
                
                xhr.onload = function() {
                    if (xhr.status === 200) {
                        const result = JSON.parse(xhr.responseText);
                        if (result.success) {
                            // Update success view
                            document.getElementById('downloadLink').value = result.download_link.replace('download.php', 'preview.php');
                            document.getElementById('expirationDate').textContent = new Date(result.expires_at).toLocaleString();
                            document.getElementById('uploadedFileName').textContent = result.file_name;
                            document.getElementById('uploadedFileSize').textContent = result.file_size;
                            
                            // Show success view
                            uploadSuccess.classList.remove('hidden');
                            uploadArea.classList.add('hidden');
                            
                            showToast('File uploaded successfully!', 'success');
                        } else {
                            showToast(result.message, 'error');
                            resetUploadProgress();
                        }
                    } else {
                        showToast('An error occurred during upload', 'error');
                        resetUploadProgress();
                    }
                };
                
                xhr.onerror = function() {
                    showToast('Network error occurred', 'error');
                    resetUploadProgress();
                };
                
                xhr.open('POST', '', true);
                xhr.send(formData);
                
            } catch (error) {
                showToast('An error occurred during upload', 'error');
                resetUploadProgress();
            }
        });

        function resetUploadProgress() {
            uploadProgress.classList.add('hidden');
            uploadButton.disabled = false;
            progressBar.style.width = '0%';
            progressPercentage.textContent = '0%';
        }

        function resetUpload() {
            // Reset form and views
            document.getElementById('uploadForm').reset();
            uploadSuccess.classList.add('hidden');
            uploadArea.classList.remove('hidden');
            filePreview.classList.add('hidden');
            resetUploadProgress();
            uploadButton.disabled = true;
        }

        function copyLink() {
            const downloadLink = document.getElementById('downloadLink');
            downloadLink.select();
            document.execCommand('copy');
            
            showToast('Link copied to clipboard!', 'success');
        }

        // Toast notification system
        let toastTimeout;
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            const toastMessage = document.getElementById('toastMessage');
            const icon = toast.querySelector('i');
            
            // Update toast content
            toastMessage.textContent = message;
            
            // Update icon based on type
            icon.className = type === 'success' 
                ? 'fas fa-check-circle text-green-500 text-xl'
                : 'fas fa-exclamation-circle text-red-500 text-xl';
            
            // Clear any existing timeout
            clearTimeout(toastTimeout);
            
            // Show toast
            toast.classList.remove('translate-x-full');
            
            // Hide toast after 3 seconds
            toastTimeout = setTimeout(() => {
                toast.classList.add('translate-x-full');
            }, 3000);
        }

        // Add keyboard support for accessibility
        dropArea.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                fileInput.click();
            }
        });

        // Prevent accidental file drops outside the drop zone
        window.addEventListener('dragover', (e) => {
            e.preventDefault();
            e.stopPropagation();
        });

        window.addEventListener('drop', (e) => {
            e.preventDefault();
            e.stopPropagation();
        });
    </script>

    <!-- Accessibility Enhancement -->
    <script>
        // Add ARIA labels and roles
        document.addEventListener('DOMContentLoaded', () => {
            const dropZone = document.querySelector('.upload-zone');
            dropZone.setAttribute('role', 'button');
            dropZone.setAttribute('aria-label', 'Drop zone for file upload or click to select file');
            
            const downloadLink = document.getElementById('downloadLink');
            downloadLink.setAttribute('aria-label', 'Secure download link');
            
            const copyButton = document.querySelector('button[onclick="copyLink()"]');
            copyButton.setAttribute('aria-label', 'Copy download link to clipboard');
        });
    </script>
</body>
</html>
