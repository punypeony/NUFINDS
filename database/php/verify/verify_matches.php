<?php
require_once dirname(__DIR__) . '/lib/bootstrap.php';
nufinds_require('lib/SessionHelper.php');
nufinds_require('lib/MatchVerifier.php');
nufinds_require('lib/View.php');

SessionHelper::requireAdmin();

$verifier  = new MatchVerifier();
$matches   = $verifier->getPendingMatches();
$adminName = htmlspecialchars(SessionHelper::get('AdminName', 'Admin'), ENT_QUOTES, 'UTF-8');
$adminEmail = htmlspecialchars(SessionHelper::get('AdminEmail', ''), ENT_QUOTES, 'UTF-8');

nufinds_render('verify/verify-matches.php', compact('matches', 'adminName', 'adminEmail'));
