<?php

require_once __DIR__ . '/utils.php';

function analyzeWebsite(string $website): array
{
    $website = normalizeWebsiteUrl($website);

    if ($website !== '') {
        $cached = readWebsiteAuditCache($website);

        if ($cached !== null) {
            return $cached;
        }
    }

    $result = [
        'checked' => false,
        'reachable' => false,
        'http_status' => 0,
        'final_url' => '',
        'load_time' => null,
        'ssl' => false,
        'redirect_count' => 0,
        'wordpress' => false,
        'elementor' => false,
        'woocommerce' => false,
        'responsive' => false,
        'has_viewport' => false,
        'has_instagram' => false,
        'has_facebook' => false,
        'has_whatsapp' => false,
        'emails' => [],
        'phones' => [],
        'instagram_profiles' => [],
        'facebook_profiles' => [],
        'has_form' => false,
        'has_cta' => false,
        'has_favicon' => false,
        'has_title' => false,
        'title' => '',
        'has_meta_description' => false,
        'has_h1' => false,
        'h1' => '',
        'has_open_graph' => false,
        'has_trust_signals' => false,
        'suspicious_domain' => false,
        'error' => '',
    ];

    if ($website === '') {
        return $result;
    }

    $result['suspicious_domain'] = isSuspiciousExternalDomain($website);
    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => $website,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 4,
        CURLOPT_TIMEOUT => 4,
        CURLOPT_CONNECTTIMEOUT => 2,
        CURLOPT_USERAGENT => 'LeadGenTool/1.0',
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);

    $html = curl_exec($ch);
    $result['checked'] = true;
    $result['http_status'] = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $result['final_url'] = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL) ?: $website;
    $result['load_time'] = round((float) curl_getinfo($ch, CURLINFO_TOTAL_TIME), 2);
    $result['redirect_count'] = (int) curl_getinfo($ch, CURLINFO_REDIRECT_COUNT);
    $result['ssl'] = str_starts_with(strtolower($result['final_url']), 'https://');
    $result['error'] = curl_error($ch) ?: '';

    if (!$html || $result['http_status'] >= 400) {
        writeWebsiteAuditCache($website, $result);
        return $result;
    }

    $result['reachable'] = true;
    $lower = strtolower($html);

    $result['wordpress'] = str_contains($lower, 'wp-content') || str_contains($lower, 'wordpress');
    $result['elementor'] = str_contains($lower, 'elementor');
    $result['woocommerce'] = str_contains($lower, 'woocommerce');
    $result['has_viewport'] = str_contains($lower, 'name="viewport"') || str_contains($lower, "name='viewport'");
    $result['responsive'] = $result['has_viewport'] && str_contains($lower, 'width=device-width');
    $result['emails'] = extractEmailsFromHtml($html);
    $result['phones'] = extractPhonesFromHtml($html);
    $result['instagram_profiles'] = extractInstagramProfiles($html);
    $result['facebook_profiles'] = extractFacebookProfiles($html);
    $result['has_instagram'] = !empty($result['instagram_profiles']);
    $result['has_facebook'] = str_contains($lower, 'facebook.com/');
    $result['has_whatsapp'] = str_contains($lower, 'wa.me/') || str_contains($lower, 'api.whatsapp.com') || str_contains($lower, 'whatsapp');
    $result['has_form'] = str_contains($lower, '<form') || str_contains($lower, 'contact-form') || str_contains($lower, 'wpcf7');
    $result['has_cta'] = preg_match('/(contacta|llamanos|llámanos|reserva|reservar|presupuesto|solicita|consulta|book now|contact us)/iu', $html) === 1;
    $result['has_favicon'] = str_contains($lower, 'rel="icon"') || str_contains($lower, "rel='icon") || str_contains($lower, 'favicon');
    $result['has_open_graph'] = str_contains($lower, 'property="og:') || str_contains($lower, "property='og:");
    $result['has_trust_signals'] = preg_match('/(aviso legal|politica de privacidad|política de privacidad|cookies|reseñas|opiniones|certificado|legal notice)/iu', $html) === 1;

    if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $match)) {
        $result['title'] = trim(html_entity_decode(strip_tags($match[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $result['has_title'] = $result['title'] !== '';
    }

    if (preg_match('/<meta[^>]+name=["\']description["\'][^>]+content=["\']([^"\']+)["\']/is', $html, $match)) {
        $result['has_meta_description'] = trim($match[1]) !== '';
    }

    if (preg_match('/<h1[^>]*>(.*?)<\/h1>/is', $html, $match)) {
        $result['h1'] = trim(html_entity_decode(strip_tags($match[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $result['has_h1'] = $result['h1'] !== '';
    }

    writeWebsiteAuditCache($website, $result);
    return $result;
}

function readWebsiteAuditCache(string $website): ?array
{
    $path = websiteAuditCachePath($website);

    if (!file_exists($path) || time() - filemtime($path) > 86400) {
        return null;
    }

    $data = json_decode(file_get_contents($path), true);

    return is_array($data) ? $data : null;
}

function writeWebsiteAuditCache(string $website, array $data): void
{
    file_put_contents(
        websiteAuditCachePath($website),
        json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );
}

function websiteAuditCachePath(string $website): string
{
    $dir = __DIR__ . '/../storage/cache/websites';

    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    return $dir . '/' . sha1($website) . '.json';
}

function extractEmailsFromHtml(string $html): array
{
    $emails = [];
    $decoded = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    if (preg_match_all('/[a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,}/i', $decoded, $matches)) {
        foreach ($matches[0] as $email) {
            $email = normalizeEmailCandidate($email);

            if ($email !== '' && !str_contains($email, 'example.') && !str_contains($email, 'sentry.')) {
                $emails[$email] = $email;
            }
        }
    }

    return array_values($emails);
}

function extractPhonesFromHtml(string $html): array
{
    $phones = [];
    $decoded = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

    if (preg_match_all('/(?:\+34|0034|34)?[\s.-]*[6789](?:[\s.-]*\d){8}/', $decoded, $matches)) {
        foreach ($matches[0] as $phone) {
            $phone = normalizePhone($phone);

            if ($phone !== '') {
                $phones[$phone] = $phone;
            }
        }
    }

    return array_values($phones);
}

function extractInstagramProfiles(string $html): array
{
    if (!preg_match_all('/https?:\/\/(?:www\.)?instagram\.com\/[a-zA-Z0-9._]+\/?/i', $html, $matches)) {
        return [];
    }

    $profiles = [];

    foreach ($matches[0] as $candidate) {
        $profile = normalizeInstagramUrl($candidate);

        if ($profile !== '') {
            $profiles[$profile] = $profile;
        }
    }

    return array_values($profiles);
}

function extractFacebookProfiles(string $html): array
{
    if (!preg_match_all('/https?:\/\/(?:www\.)?facebook\.com\/[a-zA-Z0-9._-]+\/?/i', $html, $matches)) {
        return [];
    }

    $profiles = [];

    foreach ($matches[0] as $candidate) {
        $path = trim(parse_url($candidate, PHP_URL_PATH) ?? '', '/');
        $handle = explode('/', $path)[0] ?? '';

        if (!in_array(strtolower($handle), ['', 'share', 'sharer', 'plugins', 'dialog'], true)) {
            $profiles[$candidate] = $candidate;
        }
    }

    return array_values($profiles);
}
