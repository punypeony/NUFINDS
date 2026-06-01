<?php

function nufinds_render(string $template, array $vars = []): void
{
    if (!defined('NUFINDS_PHP_ROOT')) {
        require_once __DIR__ . '/bootstrap.php';
    }

    define('NUFINDS_VIEW', true);
    extract($vars, EXTR_SKIP);

    $path = NUFINDS_APP_ROOT . '/pages/' . ltrim($template, '/');
    if (!is_file($path)) {
        http_response_code(500);
        echo 'View not found.';
        exit;
    }

    include $path;
}
