<?php

/**
 * Load database/php/entries/{name}.php and return its view data array.
 */
function nufinds_page_entry(string $entry): array
{
    if (!defined('NUFINDS_PHP_ROOT')) {
        require_once dirname(__DIR__) . '/database/php/lib/bootstrap.php';
    }

    $entryFile = NUFINDS_PHP_ROOT . '/entries/' . $entry . '.php';
    if (!is_file($entryFile)) {
        http_response_code(500);
        echo 'Missing page entry: database/php/entries/' . htmlspecialchars($entry, ENT_QUOTES, 'UTF-8') . '.php';
        exit;
    }

    $data = require $entryFile;
    if (!is_array($data)) {
        http_response_code(500);
        echo 'Page entry must return an array: ' . htmlspecialchars($entry, ENT_QUOTES, 'UTF-8');
        exit;
    }

    if (!defined('NUFINDS_VIEW')) {
        define('NUFINDS_VIEW', true);
    }

    return $data;
}
