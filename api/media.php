 <?php
require_once __DIR__ . '/utils.php';

// Simple file upload handler for blog images
$action = $_GET['action'] ?? 'upload';

switch ($action) {
    case 'upload':
        allow_methods(['POST']);
        
        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            json_response(['ok' => false, 'error' => 'No file uploaded or upload error'], 400);
        }
        
        $file = $_FILES['image'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        
        if (!in_array($file['type'], $allowedTypes)) {
            json_response(['ok' => false, 'error' => 'Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed.'], 400);
        }
        
        // Check file size (max 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            json_response(['ok' => false, 'error' => 'File too large. Maximum size is 5MB.'], 400);
        }
        
        // Create uploads directory if it doesn't exist
        $uploadsDir = __DIR__ . '/../uploads';
        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0755, true);
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('blog_') . '.' . $extension;
        $filepath = $uploadsDir . '/' . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            $imageUrl = 'uploads/' . $filename;
            json_response(['ok' => true, 'data' => ['url' => $imageUrl, 'filename' => $filename]]);
        } else {
            json_response(['ok' => false, 'error' => 'Failed to save uploaded file'], 500);
        }
        break;
        
    case 'list':
        $uploadsDir = __DIR__ . '/../uploads';
        $images = [];
        
        if (is_dir($uploadsDir)) {
            $files = scandir($uploadsDir);
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..' && preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $file)) {
                    $images[] = [
                        'filename' => $file,
                        'url' => 'uploads/' . $file,
                        'size' => filesize($uploadsDir . '/' . $file)
                    ];
                }
            }
        }
        
        json_response(['ok' => true, 'data' => $images]);
        break;
        
    default:
        json_response(['ok' => false, 'error' => 'Unknown action'], 400);
}
?>