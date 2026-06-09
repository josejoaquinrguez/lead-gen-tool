<?php

set_time_limit(120);

require_once __DIR__ . '/utils.php';
require_once __DIR__ . '/../config/env.php';

function getPlaces(string $keyword, string $postalCode, bool $forceRefresh = false): array
{
    $postalCode = trim($postalCode);
    $keyword = trim($keyword);

    if ($postalCode === '' || $keyword === '') {
        return ['results' => [], 'error' => 'Introduce codigo postal y nicho.'];
    }

    $coords = getPostalCodeCoordinates($postalCode);

    if ($coords === null) {
        return ['results' => [], 'error' => 'Codigo postal no configurado. Anade sus coordenadas en services/utils.php.'];
    }

    if (!$forceRefresh) {
        $cached = readCache($postalCode, $keyword);

        if ($cached !== null) {
            return ['results' => $cached, 'error' => '', 'cached' => true];
        }
    }

    $category = getCategoryForKeyword($keyword);
    $query = buildOverpassQuery($coords['lat'], $coords['lon'], $category['filters']);
    $response = callOverpass($query);

    if ($response['error'] !== '') {
        return ['results' => [], 'error' => $response['error']];
    }

    $data = json_decode($response['body'], true);

    if (empty($data['elements']) || !is_array($data['elements'])) {
        writeCache($postalCode, $keyword, []);
        return ['results' => [], 'error' => 'Overpass no devolvio negocios para esta busqueda.'];
    }

    $results = parseOverpassElements($data['elements'], $postalCode, $coords['city'], $category['label'], $category['key']);
    $results = dedupeBusinesses($results);
    writeCache($postalCode, $keyword, $results);

    return ['results' => $results, 'error' => '', 'cached' => false];
}

function buildOverpassQuery(float $lat, float $lon, array $filters): string
{
    $parts = [];

    foreach ($filters as $filter) {
        foreach (['node', 'way', 'relation'] as $type) {
            $parts[] = sprintf('%s(around:%d,%F,%F)%s;', $type, LEADGEN_RADIUS_METERS, $lat, $lon, $filter);
        }
    }

    return "[out:json][timeout:35];\n(\n" . implode("\n", $parts) . "\n);\nout center tags;";
}

function callOverpass(string $query): array
{
    $debugDir = __DIR__ . '/../storage/debug';

    if (!is_dir($debugDir)) {
        mkdir($debugDir, 0777, true);
    }

    file_put_contents($debugDir . '/last_query.txt', $query);

    $primaryEndpoint = (string) envValue('OVERPASS_API_URL', 'https://overpass-api.de/api/interpreter');
    $endpoints = array_values(array_unique(array_filter([
        $primaryEndpoint,
        'https://overpass.kumi.systems/api/interpreter',
        'https://overpass.openstreetmap.ru/api/interpreter',
    ])));

    $lastStatus = 0;
    $lastBody = '';
    $lastError = '';

    foreach ($endpoints as $endpoint) {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query(['data' => $query]),
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_TIMEOUT => 45,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_USERAGENT => 'LeadGenTool/1.0',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);

        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        $lastStatus = $status;
        $lastBody = $body ?: '';
        $lastError = $error ?: '';

        if ($status === 200 && $body && str_starts_with(trim($body), '{')) {
            file_put_contents($debugDir . '/raw_response.txt', $body);
            file_put_contents($debugDir . '/http_status.txt', (string) $status);
            return ['body' => $body, 'error' => ''];
        }
    }

    file_put_contents($debugDir . '/raw_response.txt', $lastBody);
    file_put_contents($debugDir . '/http_status.txt', (string) $lastStatus);

    return [
        'body' => '',
        'error' => 'Overpass no esta respondiendo ahora mismo. Prueba de nuevo o usa force=1 mas tarde. ' . $lastError,
    ];
}

function parseOverpassElements(array $elements, string $postalCode, string $expectedCity, string $category, string $categoryKey): array
{
    $results = [];

    foreach ($elements as $element) {
        $tags = $element['tags'] ?? [];
        $name = firstTag($tags, ['name', 'official_name', 'brand']);

        if ($name === '') {
            continue;
        }

        if (isClosedBusiness($tags) || isBlockedLargeBrand($name) || !businessMatchesCategory($name, $tags, $categoryKey)) {
            continue;
        }

        $lat = $element['lat'] ?? $element['center']['lat'] ?? null;
        $lon = $element['lon'] ?? $element['center']['lon'] ?? null;
        $city = firstTag($tags, ['addr:city', 'addr:town', 'addr:village', 'is_in:city']);
        $cityNormalized = normalizeText($city);

        if ($cityNormalized !== '' && $expectedCity !== '' && !str_contains($cityNormalized, normalizeText($expectedCity))) {
            continue;
        }

        $websiteSelection = chooseOfficialWebsite(collectTags($tags, [
            'contact:website',
            'website',
            'official_website',
            'brand:website',
            'operator:website',
            'contact:url',
            'url',
        ]), $name);

        $results[] = [
            'name' => $name,
            'category' => $category,
            'address' => buildAddress($tags),
            'city' => $city !== '' ? $city : $expectedCity,
            'postal_code' => firstTag($tags, ['addr:postcode']) ?: $postalCode,
            'phone' => normalizePhone(firstTag($tags, ['phone', 'contact:phone', 'mobile', 'contact:mobile'])),
            'email' => normalizeEmailCandidate(firstTag($tags, ['email', 'contact:email'])),
            'website' => $websiteSelection['website'],
            'website_is_doubtful' => $websiteSelection['is_doubtful'],
            'discarded_website' => $websiteSelection['discarded_website'],
            'instagram' => normalizeSocialProfile(firstTag($tags, ['contact:instagram', 'instagram', 'social:instagram']), 'instagram'),
            'facebook' => normalizeSocialProfile(firstTag($tags, ['contact:facebook', 'facebook', 'social:facebook']), 'facebook'),
            'latitude' => $lat,
            'longitude' => $lon,
            'source' => 'OpenStreetMap',
        ];
    }

    return array_values(array_filter($results, fn ($item) => $item['name'] !== ''));
}

function firstTag(array $tags, array $keys): string
{
    foreach ($keys as $key) {
        if (!empty($tags[$key])) {
            return trim((string) $tags[$key]);
        }
    }

    return '';
}

function collectTags(array $tags, array $keys): array
{
    $values = [];

    foreach ($keys as $key) {
        if (!empty($tags[$key])) {
            $values[] = trim((string) $tags[$key]);
        }
    }

    return $values;
}

function buildAddress(array $tags): string
{
    if (!empty($tags['addr:full'])) {
        return trim($tags['addr:full']);
    }

    $street = trim(($tags['addr:street'] ?? '') . ' ' . ($tags['addr:housenumber'] ?? ''));
    $parts = array_filter([
        $street,
        $tags['addr:postcode'] ?? '',
        $tags['addr:city'] ?? '',
    ]);

    return implode(', ', $parts);
}

function normalizeSocialProfile(string $value, string $network): string
{
    $value = trim($value);

    if ($value === '') {
        return '';
    }

    if ($network === 'instagram') {
        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            $path = trim(parse_url($value, PHP_URL_PATH) ?? '', '/');
            $handle = explode('/', $path)[0] ?? '';
        } else {
            $handle = ltrim($value, '@');
        }

        $blocked = ['', 'p', 'reel', 'reels', 'stories', 'explore', 'accounts'];

        if (in_array(strtolower($handle), $blocked, true) || !preg_match('/^[a-zA-Z0-9._]{3,30}$/', $handle)) {
            return '';
        }

        return 'https://www.instagram.com/' . $handle . '/';
    }

    if ($network === 'facebook') {
        return str_starts_with($value, 'http') ? $value : 'https://www.facebook.com/' . ltrim($value, '@');
    }

    return '';
}

function dedupeBusinesses(array $businesses): array
{
    $unique = [];

    foreach ($businesses as $business) {
        $nameKey = normalizeText($business['name']);
        $addressKey = normalizeText($business['address'] ?? '');
        $lat = $business['latitude'] !== null ? round((float) $business['latitude'], 4) : '';
        $lon = $business['longitude'] !== null ? round((float) $business['longitude'], 4) : '';
        $key = $nameKey . '|' . ($addressKey ?: $lat . ',' . $lon);

        if (!isset($unique[$key]) || dataQuality($business) > dataQuality($unique[$key])) {
            $unique[$key] = $business;
        }
    }

    return array_values($unique);
}

function dataQuality(array $business): int
{
    $score = 0;

    foreach (['website', 'phone', 'email', 'address', 'instagram', 'facebook'] as $field) {
        if (!empty($business[$field])) {
            $score++;
        }
    }

    return $score;
}
