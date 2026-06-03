<?php
require_once __DIR__ . '/bootstrap.php';

class ImageUploader {

    private static array $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    private static array $allowedMimes = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
    ];

    public static function uploadDir(string $type): string {
        return NUFINDS_APP_ROOT . '/uploads/' . $type . '/';
    }

    public static function upload(string $fileKey, string $type): ?string {
        if (!isset($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $uploadDir = self::uploadDir($type);
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $ext = strtolower(pathinfo($_FILES[$fileKey]['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, self::$allowedExtensions, true)) {
            return null;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) {
            return null;
        }

        $mime = finfo_file($finfo, $_FILES[$fileKey]['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, self::$allowedMimes, true)) {
            return null;
        }

        $filename = $type . '_' . uniqid('', true) . '.' . $ext;
        if (move_uploaded_file($_FILES[$fileKey]['tmp_name'], $uploadDir . $filename)) {
            return 'uploads/' . $type . '/' . $filename;
        }

        return null;
    }
}
