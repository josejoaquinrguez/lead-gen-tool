<?php

function analyzeWebsite(string $url): array
{
    $issues = [];
    $score = 0;

    if ($url === '') {

        return [
            'score' => 40,
            'issues' => ['No tiene página web'],
            'status' => 'missing'
        ];
    }

    if (!str_starts_with(strtolower($url), 'https://')) {
        $score += 15;
        $issues[] = 'La web no usa HTTPS';
    }

    $suspiciousDomains = [
        'wixsite',
        'blogspot',
        'facebook.com',
        'instagram.com'
    ];

    foreach ($suspiciousDomains as $domain) {

        if (str_contains(strtolower($url), $domain)) {
            $score += 10;
            $issues[] = 'Usa una plataforma poco profesional';
            break;
        }
    }

    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 3,
        CURLOPT_CONNECTTIMEOUT => 2,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_USERAGENT => 'LeadGenTool/1.0',
    ]);

    $html = curl_exec($ch);

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    $loadTime = curl_getinfo($ch, CURLINFO_TOTAL_TIME);

    

    if (!$html || $httpCode >= 400) {

        $score += 30;
        $issues[] = 'La web no carga correctamente';

    } else {

        if ($loadTime > 3) {
            $score += 10;
            $issues[] = 'La web es lenta';
        }

        if (!preg_match('/<title>(.*?)<\/title>/i', $html)) {
            $score += 10;
            $issues[] = 'La web no tiene título SEO';
        }
    }

    return [
        'score' => $score,
        'issues' => $issues,
        'status' => 'checked'
    ];
}

function analyzeBusiness(array $business): array
{
    $score = 0;
    $issues = [];

    $website = trim($business['website'] ?? '');
    $phone = trim($business['phone'] ?? '');

    $websiteAnalysis = analyzeWebsite($website);

    $score += $websiteAnalysis['score'];

    $issues = array_merge($issues, $websiteAnalysis['issues']);

    if ($phone === '') {
        $score += 10;
        $issues[] = 'No tiene teléfono visible';
    }

    if ($score >= 40) {
        $level = 'Lead interesante';
    } elseif ($score >= 15) {
        $level = 'Mejorable';
    } else {
        $level = 'Correcto';
    }

    return [
        'score' => $score,
        'issues' => $issues,
        'level' => $level,
    ];
}