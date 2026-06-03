<?php
/**
 * Shared path helpers for the NUFINDS PHP layer.
 * Load this once from any entry script under database/php/.
 */
define('NUFINDS_PHP_ROOT', dirname(__DIR__));
define('NUFINDS_ROOT', dirname(NUFINDS_PHP_ROOT));
define('NUFINDS_APP_ROOT', dirname(NUFINDS_ROOT));

function nufinds_is_pages_context(): bool
{
    return strpos($_SERVER['SCRIPT_NAME'] ?? '', '/pages/') !== false;
}

function nufinds_script_depth(): int
{
    $script = $_SERVER['SCRIPT_NAME'] ?? '';

    $pagesMarker = '/pages/';
    $pos = strpos($script, $pagesMarker);
    if ($pos !== false) {
        $after = trim(dirname(substr($script, $pos + strlen($pagesMarker))), '/.');
        if ($after === '' || $after === '.') {
            return 0;
        }

        return substr_count($after, '/') + 1;
    }

    $marker = '/database/php/';
    $pos = strpos($script, $marker);
    if ($pos === false) {
        return 0;
    }

    $after = trim(dirname(substr($script, $pos + strlen($marker))), '/.');
    if ($after === '' || $after === '.') {
        return 0;
    }

    return substr_count($after, '/') + 1;
}

function nufinds_repo_relative(string $path): string
{
    if (nufinds_is_pages_context()) {
        $depth = nufinds_script_depth();
        $prefix = $depth > 0 ? str_repeat('../', $depth + 1) : '../';

        return $prefix . ltrim($path, '/');
    }

    $levels = nufinds_script_depth() + 2;

    return str_repeat('../', $levels) . ltrim($path, '/');
}

function nufinds_asset(string $path): string
{
    return nufinds_repo_relative($path);
}

function nufinds_pages_url(string $page): string
{
    if (nufinds_is_pages_context()) {
        $depth = nufinds_script_depth();
        $prefix = $depth > 0 ? str_repeat('../', $depth) : '';

        return $prefix . ltrim($page, '/');
    }

    return nufinds_repo_relative('pages/' . ltrim($page, '/'));
}

function nufinds_student_page(string $file): string
{
    return nufinds_pages_url('student/' . ltrim($file, '/'));
}

function nufinds_admin_page(string $file): string
{
    return nufinds_pages_url('admin/' . ltrim($file, '/'));
}

function nufinds_php_url(string $relativeToPhpRoot): string
{
    if (nufinds_is_pages_context()) {
        $depth = nufinds_script_depth();
        $prefix = $depth > 0 ? str_repeat('../', $depth + 1) : '../';

        return $prefix . 'database/php/' . ltrim($relativeToPhpRoot, '/');
    }

    $depth = nufinds_script_depth();
    $prefix = $depth > 0 ? str_repeat('../', $depth) : '';

    return $prefix . ltrim($relativeToPhpRoot, '/');
}

function nufinds_require(string $relativeFromPhpRoot): void
{
    require_once NUFINDS_PHP_ROOT . '/' . ltrim($relativeFromPhpRoot, '/');
}

function nu_asset(string $path): string
{
    return nufinds_asset($path);
}

/** Earliest selectable date on student lost/found report forms. */
function nufinds_report_date_min(): string
{
    return '2022-01-01';
}

function nufinds_report_date_max(): string
{
    return date('Y-m-d');
}

/** Max image upload size for lost/found report submissions. */
function nufinds_report_upload_max_bytes(): int
{
    return 25 * 1024 * 1024;
}

function nufinds_report_upload_max_label(): string
{
    return '25 MB';
}

function nufinds_csrf_token(): string
{
    nufinds_require('lib/SessionHelper.php');
    return SessionHelper::generateCsrfToken();
}

function nufinds_csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="'
        . htmlspecialchars(nufinds_csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

/** Build a browser URL for a stored upload path (supports legacy DB values). */
function nufinds_upload_url(?string $path): ?string
{
    if ($path === null || trim($path) === '') {
        return null;
    }

    $path = str_replace('\\', '/', trim($path));
    $path = ltrim($path, '/');

    if (str_starts_with($path, 'uploads/')) {
        if (is_file(NUFINDS_APP_ROOT . '/' . $path)) {
            return nufinds_asset($path);
        }
        $legacy = 'database/' . $path;
        if (is_file(NUFINDS_APP_ROOT . '/' . $legacy)) {
            return nufinds_asset($legacy);
        }
        return nufinds_asset($path);
    }

    if (str_starts_with($path, 'database/uploads/')) {
        return nufinds_asset($path);
    }

    return nufinds_asset($path);
}
