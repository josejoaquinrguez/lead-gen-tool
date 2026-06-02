<?php

function normalizeKeyword(string $keyword): string
{
    $keyword = strtolower(trim($keyword));

    $replacements = [
        'á' => 'a',
        'é' => 'e',
        'í' => 'i',
        'ó' => 'o',
        'ú' => 'u',
        'ñ' => 'n'
    ];

    return strtr($keyword, $replacements);
}

function getCategoryFilters(string $keyword): array
{
    $keyword = normalizeKeyword($keyword);

    $categoryMap = [

        'restaurantes' => [
            '["amenity"="restaurant"]',
            '["amenity"="fast_food"]',
            '["amenity"="cafe"]',
            '["amenity"="bar"]'
        ],

        'restaurante' => [
            '["amenity"="restaurant"]',
            '["amenity"="fast_food"]',
            '["amenity"="cafe"]',
            '["amenity"="bar"]'
        ],

        'inmobiliarias' => [
            '["office"="estate_agent"]',
            '["shop"]',
            '["office"]',
            '["name"~"inmobiliaria", i]',
            '["name"~"real estate", i]'
        ],

        'inmobiliaria' => [
            '["office"="estate_agent"]',
            '["shop"]',
            '["office"]',
            '["name"~"inmobiliaria", i]',
            '["name"~"real estate", i]'
        ],

        'clinicas esteticas' => [
            '["shop"="beauty"]',
            '["healthcare"="clinic"]',
            '["amenity"="clinic"]'
        ],

        'clinica estetica' => [
            '["shop"="beauty"]',
            '["healthcare"="clinic"]',
            '["amenity"="clinic"]'
        ],

        'peluquerias' => [
            '["shop"="hairdresser"]'
        ],

        'peluqueria' => [
            '["shop"="hairdresser"]'
        ],

        'dentistas' => [
            '["amenity"="dentist"]',
            '["healthcare"="dentist"]'
        ],

        'dentista' => [
            '["amenity"="dentist"]',
            '["healthcare"="dentist"]'
        ],

        'hoteles' => [
            '["tourism"="hotel"]',
            '["tourism"="guest_house"]'
        ],

        'hotel' => [
            '["tourism"="hotel"]',
            '["tourism"="guest_house"]'
        ],
    ];

    return $categoryMap[$keyword] ?? [
        '["name"~"' . preg_quote($keyword, '/') . '", i]'
    ];
}

function buildAddress(array $tags): string
{
    $parts = [];

    if (!empty($tags['addr:street'])) {
        $parts[] = $tags['addr:street'];
    }

    if (!empty($tags['addr:housenumber'])) {
        $parts[] = $tags['addr:housenumber'];
    }

    if (!empty($tags['addr:city'])) {
        $parts[] = $tags['addr:city'];
    }

    if (!empty($tags['addr:postcode'])) {
        $parts[] = $tags['addr:postcode'];
    }

    return !empty($parts)
        ? implode(', ', $parts)
        : 'Dirección no disponible';
}

function getPlaces($keyword, $postal): array
{
    $cpMap = [
        "29600" => ["lat" => 36.510, "lon" => -4.885], // Marbella
        "41630" => ["lat" => 37.353, "lon" => -5.222], // La Lantejuela
    ];

    if (!isset($cpMap[$postal])) {
        return [];
    }

    $lat = $cpMap[$postal]["lat"];
    $lon = $cpMap[$postal]["lon"];

    $radius = 5000;

    $filters = getCategoryFilters($keyword);

    $queryParts = [];

    foreach ($filters as $filter) {

        $queryParts[] =
            'node(around:' . $radius . ',' . $lat . ',' . $lon . ')' . $filter . ';';

        $queryParts[] =
            'way(around:' . $radius . ',' . $lat . ',' . $lon . ')' . $filter . ';';

        $queryParts[] =
            'relation(around:' . $radius . ',' . $lat . ',' . $lon . ')' . $filter . ';';
    }

    $query = '
    [out:json][timeout:25];

    (
        ' . implode("\n", $queryParts) . '
    );

    out center tags;
    ';

    $url = "https://overpass-api.de/api/interpreter?data=" . urlencode($query);

    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_USERAGENT => 'LeadGenTool/1.0',
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);

    $response = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (!$response || $statusCode !== 200) {
        return [];
    }

    $data = json_decode($response, true);

    if (!isset($data["elements"])) {
        return [];
    }

    $results = [];

    foreach ($data["elements"] as $el) {

        $tags = $el["tags"] ?? [];

        $latResult =
            $el["lat"]
            ?? $el["center"]["lat"]
            ?? null;

        $lonResult =
            $el["lon"]
            ?? $el["center"]["lon"]
            ?? null;

        $website =
            $tags["website"]
            ?? $tags["contact:website"]
            ?? $tags["url"]
            ?? "";

        $phone =
            $tags["phone"]
            ?? $tags["contact:phone"]
            ?? "";

        $instagram =
            $tags["contact:instagram"]
            ?? $tags["instagram"]
            ?? "";

        $facebook =
            $tags["contact:facebook"]
            ?? $tags["facebook"]
            ?? "";

        $results[] = [
            "name" => $tags["name"] ?? "Sin nombre",
            "address" => buildAddress($tags),
            "phone" => $phone,
            "website" => $website,
            "instagram" => $instagram,
            "facebook" => $facebook,
            "latitude" => $latResult,
            "longitude" => $lonResult,
        ];
    }

    return $results;
}