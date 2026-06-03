<?php
require_once dirname(__DIR__) . '/lib/bootstrap.php';
nufinds_require('lib/SessionHelper.php');
nufinds_require('lib/MatchVerifier.php');
nufinds_require('lib/View.php');

SessionHelper::requireAdmin();

$verifier     = new MatchVerifier();
$searchQuery  = trim($_GET['q'] ?? '');
$matches      = $searchQuery !== ''
    ? $verifier->searchPendingMatches($searchQuery)
    : $verifier->getPendingMatches();
$adminName    = htmlspecialchars(SessionHelper::get('AdminName', 'Admin'), ENT_QUOTES, 'UTF-8');
$adminEmail   = htmlspecialchars(SessionHelper::get('AdminEmail', ''), ENT_QUOTES, 'UTF-8');
$verifyUrl    = nufinds_php_url('verify/verify_matches.php');

nufinds_render('verify/verify-matches.php', compact(
    'matches',
    'adminName',
    'adminEmail',
    'searchQuery',
    'verifyUrl'
));
