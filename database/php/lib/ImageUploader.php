<?php
class ImageUploader {

    private static array $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    public static function upload(string $fileKey, string $type): ?string {
        if (!isset($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $uploadDir = __DIR__ . '/../../uploads/' . $type . '/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $ext = strtolower(pathinfo($_FILES[$fileKey]['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, self::$allowedExtensions)) {
            return null;
        }

        $filename = $type . '_' . uniqid() . '.' . $ext;
        if (move_uploaded_file($_FILES[$fileKey]['tmp_name'], $uploadDir . $filename)) {
        return '/uploads/' . $type . '/' . $filename;
        }

        return null;
    }
}