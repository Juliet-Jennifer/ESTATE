<?php
namespace App\Services;

use app\Utils\Response;
use app\Utils\Logger;

class FileUploadService {
    public static function uploadImage($file, $directory = 'properties') {
        return self::uploadFile($file, $directory, ALLOWED_IMAGE_TYPES);
    }

    public static function uploadDocument($file, $directory = 'documents') {
        return self::uploadFile($file, $directory, ALLOWED_DOCUMENT_TYPES);
    }

    private static function uploadFile($file, $directory, $allowedTypes) {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            Response::error('File upload failed', 'UPLOAD_ERROR');
        }

        // Check file size
        if ($file['size'] > MAX_UPLOAD_SIZE) {
            Response::error('File too large', 'FILE_TOO_LARGE');
        }

        // Verify MIME type using magic bytes
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedTypes)) {
            Response::error('Invalid file type', 'INVALID_FILE_TYPE');
        }

        // Sanitize filename
        $originalName = pathinfo($file['name'], PATHINFO_FILENAME);
        $sanitizedName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $originalName);
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        
        // Generate unique filename
        $filename = $sanitizedName . '_' . uniqid() . '.' . $extension;
        $uploadPath = UPLOAD_PATH . $directory . '/' . date('Y/m/');
        
        // Create directory if it doesn't exist
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        $fullPath = $uploadPath . $filename;

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
            Logger::error('Failed to move uploaded file', ['file' => $file['name']]);
            Response::error('Failed to save file', 'UPLOAD_SAVE_ERROR');
        }

        // Generate WebP version for images
        if (strpos($mimeType, 'image/') === 0 && $mimeType !== 'image/webp') {
            self::generateWebP($fullPath, $uploadPath . pathinfo($filename, PATHINFO_FILENAME) . '.webp');
        }

        Logger::info('File uploaded successfully', [
            'filename' => $filename,
            'path' => $fullPath,
            'size' => $file['size']
        ]);

        return [
            'filename' => $filename,
            'path' => $fullPath,
            'url' => '/uploads/' . $directory . '/' . date('Y/m/') . $filename,
            'size' => $file['size'],
            'mime_type' => $mimeType
        ];
    }

    private static function generateWebP($sourcePath, $destinationPath) {
        try {
            $image = null;
            $extension = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));

            switch ($extension) {
                case 'jpg':
                case 'jpeg':
                    $image = imagecreatefromjpeg($sourcePath);
                    break;
                case 'png':
                    $image = imagecreatefrompng($sourcePath);
                    break;
                default:
                    return false;
            }

            if ($image) {
                imagewebp($image, $destinationPath, 80);
                imagedestroy($image);
                return true;
            }
        } catch (\Exception $e) {
            Logger::warning('WebP conversion failed', ['error' => $e->getMessage()]);
        }

        return false;
    }

    public static function deleteFile($filePath) {
        if (file_exists($filePath)) {
            return unlink($filePath);
        }
        return false;
    }
}