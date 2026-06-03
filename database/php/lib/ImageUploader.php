<?php
require_once __DIR__ . '/bootstrap.php';

class ImageUploader {

    private static ?string $lastError = null;

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

    public static function consumeLastError(): ?string {
        $error = self::$lastError;
        self::$lastError = null;
        return $error;
    }

    public static function upload(string $fileKey, string $type): ?string {
        self::$lastError = null;

        if (!isset($_FILES[$fileKey])) {
            return null;
        }

        $file = $_FILES[$fileKey];
        if ($file['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            self::$lastError = self::uploadErrorMessage((int) $file['error']);
            return null;
        }

        $maxBytes = nufinds_report_upload_max_bytes();
        if (($file['size'] ?? 0) > $maxBytes) {
            self::$lastError = 'Image must be ' . nufinds_report_upload_max_label() . ' or smaller.';
            return null;
        }

        $uploadDir = self::uploadDir($type);
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, self::$allowedExtensions, true)) {
            self::$lastError = 'Please upload a JPG, PNG, GIF, or WebP image.';
            return null;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) {
            self::$lastError = 'Unable to upload image. Please try again.';
            return null;
        }

        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, self::$allowedMimes, true)) {
            self::$lastError = 'Please upload a JPG, PNG, GIF, or WebP image.';
            return null;
        }

        $filename = $type . '_' . uniqid('', true) . '.' . $ext;
        if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
            return 'uploads/' . $type . '/' . $filename;
        }

        self::$lastError = 'Unable to upload image. Please try again.';
        return null;
    }

    private static function uploadErrorMessage(int $code): string {
        if (in_array($code, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
            return 'Image must be ' . nufinds_report_upload_max_label() . ' or smaller.';
        }

        return 'Unable to upload image. Please try again.';
    }
}
