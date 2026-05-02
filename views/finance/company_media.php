<?php
require_once __DIR__ . '/../../app/middlewares/ModuleMiddleware.php';
ModuleMiddleware::requireModule('finance');

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uploadType = $_POST['upload_type'] ?? '';
    $companyId = $_POST['company_id'] ?? 'default';
    
    if (isset($_FILES['media_file']) && $_FILES['media_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['media_file'];
        $allowedTypes = ['image/png', 'image/jpeg', 'image/jpg'];
        
        if (in_array($file['type'], $allowedTypes)) {
            $targetDir = __DIR__ . '/../../storage/company/' . $uploadType . 's/';
            
            // Create directory if it doesn't exist
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }
            
            // Determine file extension
            $extension = ($file['type'] === 'image/png') ? '.png' : '.jpg';
            $targetFile = $targetDir . $companyId . $extension;
            
            // Simple file move without conversion
            if (move_uploaded_file($file['tmp_name'], $targetFile)) {
                $message = ucfirst($uploadType) . ' uploaded successfully for company ID: ' . $companyId;
            } else {
                $error = 'Failed to upload the file.';
            }
        } else {
            $error = 'Only PNG and JPEG files are allowed.';
        }
    } else {
        $error = 'Please select a valid file to upload.';
    }
}

// Get existing files (support both PNG and JPG)
$logoFiles = array_merge(
    glob(__DIR__ . '/../../storage/company/logos/*.png') ?: [],
    glob(__DIR__ . '/../../storage/company/logos/*.jpg') ?: []
);
$sealFiles = array_merge(
    glob(__DIR__ . '/../../storage/company/seals/*.png') ?: [],
    glob(__DIR__ . '/../../storage/company/seals/*.jpg') ?: []
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company Media Management</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { text-align: center; margin-bottom: 30px; }
        .upload-section { margin-bottom: 30px; padding: 20px; border: 1px solid #ddd; border-radius: 6px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #005a87; }
        .message { padding: 10px; margin: 10px 0; border-radius: 4px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .existing-files { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; }
        .file-item { text-align: center; padding: 15px; border: 1px solid #ddd; border-radius: 6px; }
        .file-item img { max-width: 100px; max-height: 100px; object-fit: contain; }
        .back-link { display: inline-block; margin-bottom: 20px; color: #007cba; text-decoration: none; }
        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <a href="/ergon/finance/measurement-sheet" class="back-link">← Back to Measurement Sheets</a>
        
        <div class="header">
            <h1>Company Media Management</h1>
            <p>Upload company logos and seals for measurement sheets</p>
        </div>

        <?php if ($message): ?>
            <div class="message success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="message error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="upload-section">
            <h3>Upload New Media</h3>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="upload_type">Media Type:</label>
                    <select name="upload_type" id="upload_type" required>
                        <option value="">Select Type</option>
                        <option value="logo">Company Logo</option>
                        <option value="seal">Company Seal</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="company_id">Company ID:</label>
                    <input type="text" name="company_id" id="company_id" placeholder="Enter company ID or 'default'" value="default" required>
                    <small>Use 'default' for fallback logo/seal, or specific company ID from database</small>
                </div>
                
                <div class="form-group">
                    <label for="media_file">Select File:</label>
                    <input type="file" name="media_file" id="media_file" accept="image/png,image/jpeg,image/jpg" required>
                    <small>Supported formats: PNG, JPEG. Recommended size: 64x64 pixels</small>
                </div>
                
                <button type="submit">Upload Media</button>
            </form>
        </div>

        <div class="upload-section">
            <h3>Existing Logos</h3>
            <div class="existing-files">
                <?php if (empty($logoFiles)): ?>
                    <p>No logos uploaded yet.</p>
                <?php else: ?>
                    <?php foreach ($logoFiles as $file): ?>
                        <?php 
                        $filename = pathinfo($file, PATHINFO_FILENAME);
                        $extension = pathinfo($file, PATHINFO_EXTENSION);
                        $relativePath = '/ergon/storage/company/logos/' . basename($file);
                        ?>
                        <div class="file-item">
                            <img src="<?= $relativePath ?>" alt="Logo">
                            <p><strong><?= $filename ?></strong></p>
                            <small>Logo (.<?= $extension ?>)</small>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="upload-section">
            <h3>Existing Seals</h3>
            <div class="existing-files">
                <?php if (empty($sealFiles)): ?>
                    <p>No seals uploaded yet.</p>
                <?php else: ?>
                    <?php foreach ($sealFiles as $file): ?>
                        <?php 
                        $filename = pathinfo($file, PATHINFO_FILENAME);
                        $extension = pathinfo($file, PATHINFO_EXTENSION);
                        $relativePath = '/ergon/storage/company/seals/' . basename($file);
                        ?>
                        <div class="file-item">
                            <img src="<?= $relativePath ?>" alt="Seal" style="border-radius: 50%;">
                            <p><strong><?= $filename ?></strong></p>
                            <small>Seal (.<?= $extension ?>)</small>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div style="margin-top: 30px; padding: 15px; background: #e9ecef; border-radius: 6px;">
            <h4>Instructions:</h4>
            <ul>
                <li>Upload a <strong>default.png</strong> logo and seal for fallback use</li>
                <li>Upload company-specific files using the company ID from your database</li>
                <li>Images will be automatically converted to PNG format</li>
                <li>Recommended size: 64x64 pixels for best results</li>
                <li>Files are stored in <code>/storage/company/logos/</code> and <code>/storage/company/seals/</code></li>
            </ul>
        </div>
    </div>
</body>
</html>