<?php

function analyzeWebsite(string $website): array
{
    $result = [
        'checked' => false,
        'reachable' => false,
        'http_status' => 0,
        'final_url' => '',
        'ssl' => false,
        'wordpress' => false,
        'elementor' => false,
        'responsive' => false,
        'has_instagram' => false,
        'has_whatsapp' => false,
        'has_form' => false,
        'has_cta' => false,
        'has_favicon' => false,
    ];

    if ($website === '') {
        return $result;
    }

    if (
        !str_starts_with($website, 'http://') &&
        !str_starts_with($website, 'https://')
    ) {
        $website = 'https://' . $website;
    }

    if (str_starts_with($website, 'https://')) {
        $result['ssl'] = true;
    }

    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => $website,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 8,
        CURLOPT_CONNECTTIMEOUT => 4,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 3,
        CURLOPT_USERAGENT => 'LeadGenTool/1.0',
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);

    $html = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

    $result['checked'] = true;
    $result['http_status'] = (int) $statusCode;
    $result['final_url'] = $finalUrl ?: $website;

    $result['ssl'] = str_starts_with(strtolower($result['final_url']), 'https://');

    if (!$html || $statusCode >= 400) {
        return $result;
    }

    $result['reachable'] = true;

    $htmlLower = strtolower($html);

    if (
        str_contains($htmlLower, 'wp-content') ||
        str_contains($htmlLower, 'wordpress')
    ) {
        $result['wordpress'] = true;
    }

    if (str_contains($htmlLower, 'elementor')) {
        $result['elementor'] = true;
    }

    if (
        str_contains($htmlLower, 'viewport') &&
        str_contains($htmlLower, 'width=device-width')
    ) {
        $result['responsive'] = true;
    }

    if (preg_match_all('/https?:\/\/(?:www\.)?instagram\.com\/[a-zA-Z0-9._]+\/?/i', $html, $matches)) {
        foreach ($matches[0] as $candidate) {
            if (function_exists('normalizeInstagramUrl') && normalizeInstagramUrl($candidate) !== '') {
                $result['has_instagram'] = true;
                break;
            }
        }
    }

    if (
        str_contains($htmlLower, 'wa.me') ||
        str_contains($htmlLower, 'whatsapp')
    ) {
        $result['has_whatsapp'] = true;
    }

    if (
        str_contains($htmlLower, '<form') ||
        str_contains($htmlLower, 'contact-form')
    ) {
        $result['has_form'] = true;
    }

    if (
        str_contains($htmlLower, 'contacta') ||
        str_contains($htmlLower, 'llamanos') ||
        str_contains($htmlLower, 'reservar') ||
        str_contains($htmlLower, 'presupuesto')
    ) {
        $result['has_cta'] = true;
    }

    if (
        str_contains($htmlLower, 'rel="icon"') ||
        str_contains($htmlLower, 'favicon')
    ) {
        $result['has_favicon'] = true;
    }

    return $result;
}
