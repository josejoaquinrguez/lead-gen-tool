<?php

require_once __DIR__ . '/../services/utils.php';
require_once __DIR__ . '/../services/website_analyzer.php';
require_once __DIR__ . '/../services/analyzer.php';

header('Content-Type: application/json; charset=utf-8');

$name = trim($_GET['name'] ?? '');
$website = normalizeWebsiteUrl($_GET['website'] ?? '');
$phone = normalizePhone($_GET['phone'] ?? '');
$instagram = normalizeInstagramUrl($_GET['instagram'] ?? '');
$email = normalizeEmailCandidate($_GET['email'] ?? '');

if ($name === '') {
    echo json_encode(['success' => false, 'error' => 'Nombre no valido.']);
    exit;
}

if ($website !== '' && (!websiteMatchesBusiness($website, $name) || isSuspiciousExternalDomain($website))) {
    $website = '';
}

$audit = analyzeWebsite($website);
$business = [
    'name' => $name,
    'website' => $website,
    'phone' => $phone,
    'email' => $email,
    'instagram' => $instagram,
    'facebook' => '',
    'address' => trim($_GET['address'] ?? ''),
    'website_is_doubtful' => $website !== '' && !websiteMatchesBusiness($website, $name),
    'audit' => $audit,
];

$analysis = analyzeBusiness($business);

echo json_encode([
    'success' => true,
    'data' => [
        'website' => $website,
        'website_found' => $website !== '',
        'phone' => $phone,
        'email' => $email,
        'instagram' => $instagram,
        'audit' => $audit,
        'analysis' => $analysis,
        'website_issues' => $analysis['issues'],
    ],
], JSON_UNESCAPED_UNICODE);
