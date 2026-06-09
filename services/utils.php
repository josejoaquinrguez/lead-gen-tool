<?php

const LEADGEN_CACHE_TTL = 21600;
const LEADGEN_RADIUS_METERS = 12000;
const LEADGEN_CACHE_VERSION = 'v2-contact-quality';

function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function normalizeText(string $text): string
{
    $text = strtolower(trim($text));
    $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;
    $text = preg_replace('/[^a-z0-9\s]/', ' ', $text);
    $text = preg_replace('/\s+/', ' ', $text);

    return trim($text);
}

function normalizeKeyword(string $keyword): string
{
    $keyword = normalizeText($keyword);

    $synonyms = [
        'restaurante' => 'restaurantes',
        'bar' => 'restaurantes',
        'bares' => 'restaurantes',
        'cafeteria' => 'restaurantes',
        'cafeterias' => 'restaurantes',
        'inmobiliaria' => 'inmobiliarias',
        'clinica' => 'clinicas',
        'clinicas esteticas' => 'estetica',
        'clinica estetica' => 'estetica',
        'esteticas' => 'estetica',
        'peluqueria' => 'estetica',
        'peluquerias' => 'estetica',
        'hotel' => 'hoteles',
        'hostal' => 'hoteles',
        'dentista' => 'dentistas',
        'dentistas' => 'dentistas',
        'supermercado' => 'supermercados',
    ];

    return $synonyms[$keyword] ?? $keyword;
}

function getPostalCodeCoordinates(string $postalCode): ?array
{
    $postalCode = trim($postalCode);

    if (!preg_match('/^\d{5}$/', $postalCode)) {
        return null;
    }

    $postalCodes = [
        '29600' => ['lat' => 36.510071, 'lon' => -4.882447, 'city' => 'marbella'],
        '29601' => ['lat' => 36.5128, 'lon' => -4.8851, 'city' => 'marbella'],
        '29602' => ['lat' => 36.5064, 'lon' => -4.9107, 'city' => 'marbella'],
        '29603' => ['lat' => 36.5155, 'lon' => -4.8524, 'city' => 'marbella'],
        '29604' => ['lat' => 36.4929, 'lon' => -4.7795, 'city' => 'marbella'],
        '29630' => ['lat' => 36.5988, 'lon' => -4.5168, 'city' => 'benalmadena'],
    ];

    return $postalCodes[$postalCode] ?? null;
}

function getCategoryDefinitions(): array
{
    return [
        'restaurantes' => [
            'label' => 'Restaurantes',
            'filters' => ['["amenity"="restaurant"]', '["amenity"="cafe"]', '["amenity"="bar"]', '["amenity"="fast_food"]'],
        ],
        'inmobiliarias' => [
            'label' => 'Inmobiliarias',
            'filters' => ['["office"="estate_agent"]'],
        ],
        'clinicas' => [
            'label' => 'Clinicas',
            'filters' => ['["healthcare"="clinic"]', '["healthcare"="doctor"]', '["healthcare"="dentist"]', '["amenity"="clinic"]', '["amenity"="doctors"]', '["amenity"="dentist"]'],
        ],
        'dentistas' => [
            'label' => 'Dentistas',
            'filters' => ['["healthcare"="dentist"]', '["amenity"="dentist"]'],
        ],
        'estetica' => [
            'label' => 'Estetica',
            'filters' => ['["shop"="beauty"]', '["shop"="hairdresser"]', '["amenity"="beauty_salon"]'],
        ],
        'hoteles' => [
            'label' => 'Hoteles',
            'filters' => ['["tourism"="hotel"]', '["tourism"="hostel"]', '["tourism"="guest_house"]'],
        ],
        'supermercados' => [
            'label' => 'Supermercados',
            'filters' => ['["shop"="supermarket"]'],
        ],
    ];
}

function getCategoryForKeyword(string $keyword): array
{
    $normalized = normalizeKeyword($keyword);
    $categories = getCategoryDefinitions();

    if (isset($categories[$normalized])) {
        return [
            'key' => $normalized,
            'label' => $categories[$normalized]['label'],
            'filters' => $categories[$normalized]['filters'],
        ];
    }

    return [
        'key' => 'custom',
        'label' => $keyword,
        'filters' => ['["name"~"' . preg_quote($normalized, '/') . '", i]'],
    ];
}

function isBlockedLargeBrand(string $name): bool
{
    $name = normalizeText($name);
    $blockedBrands = [
        'telepizza',
        'dominos',
        'domino s',
        'mcdonald',
        'burger king',
        'kfc',
        'subway',
        'starbucks',
        'pizza hut',
        'taco bell',
        'vips',
        'ginos',
        'foster hollywood',
        'goiko',
        '100 montaditos',
        'popeyes',
        'five guys',
        'carrefour',
        'mercadona',
        'lidl',
        'aldi',
        'dia',
        'primor',
        'mango',
        'zara',
        'bershka',
        'stradivarius',
        'pull bear',
        'hm',
        'h m',
        'bbva',
        'santander',
        'caixabank',
        'mapfre',
    ];

    foreach ($blockedBrands as $brand) {
        if (str_contains($name, $brand)) {
            return true;
        }
    }

    return false;
}

function isClosedBusiness(array $tags): bool
{
    $closedKeys = [
        'disused', 'abandoned', 'demolished', 'destroyed', 'removed',
        'closed', 'was:amenity', 'was:shop', 'was:office', 'was:tourism',
        'disused:amenity', 'disused:shop', 'disused:office', 'disused:tourism',
        'abandoned:amenity', 'abandoned:shop', 'abandoned:office', 'abandoned:tourism',
    ];

    foreach ($closedKeys as $key) {
        if (!empty($tags[$key])) {
            return true;
        }
    }

    foreach (['shop', 'amenity', 'office', 'tourism', 'healthcare'] as $key) {
        $value = normalizeText((string) ($tags[$key] ?? ''));

        if (in_array($value, ['vacant', 'closed', 'disused', 'abandoned'], true)) {
            return true;
        }
    }

    $name = normalizeText((string) ($tags['name'] ?? ''));
    $description = normalizeText((string) (($tags['description'] ?? '') . ' ' . ($tags['note'] ?? '') . ' ' . ($tags['fixme'] ?? '')));

    return str_contains($name, 'cerrado permanentemente')
        || str_contains($name, 'permanently closed')
        || str_contains($description, 'cerrado permanentemente')
        || str_contains($description, 'permanently closed');
}

function businessMatchesCategory(string $name, array $tags, string $categoryKey): bool
{
    $name = normalizeText($name);

    $negativeWords = [
        'restaurantes' => [
            'barber', 'barberia', 'barber shop', 'hair', 'peluqueria',
            'beauty', 'salon', 'nails', 'spa', 'gym', 'fitness',
            'real estate', 'inmobiliaria', 'clinic', 'clinica',
        ],
        'inmobiliarias' => ['restaurant', 'restaurante', 'barber', 'hotel', 'clinic'],
        'clinicas' => ['restaurant', 'restaurante', 'barber', 'hotel', 'inmobiliaria'],
        'estetica' => ['restaurant', 'restaurante', 'hotel', 'real estate', 'inmobiliaria'],
        'hoteles' => ['restaurant', 'restaurante', 'barber', 'clinic', 'inmobiliaria'],
        'dentistas' => ['restaurant', 'restaurante', 'barber', 'hotel'],
        'supermercados' => ['restaurant', 'restaurante', 'barber', 'hotel'],
    ];

    foreach ($negativeWords[$categoryKey] ?? [] as $word) {
        if (str_contains($name, $word)) {
            return false;
        }
    }

    if ($categoryKey === 'restaurantes') {
        foreach (['shop', 'office', 'healthcare', 'tourism'] as $tag) {
            if (!empty($tags[$tag])) {
                return false;
            }
        }
    }

    return true;
}

function getCachePath(string $postalCode, string $keyword): string
{
    $dir = __DIR__ . '/../storage/cache';

    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    return $dir . '/' . sha1(LEADGEN_CACHE_VERSION . '|' . trim($postalCode) . '|' . normalizeKeyword($keyword)) . '.json';
}

function readCache(string $postalCode, string $keyword): ?array
{
    $path = getCachePath($postalCode, $keyword);

    if (!file_exists($path) || time() - filemtime($path) > LEADGEN_CACHE_TTL) {
        return null;
    }

    $data = json_decode(file_get_contents($path), true);

    return is_array($data) ? $data : null;
}

function writeCache(string $postalCode, string $keyword, array $data): void
{
    file_put_contents(
        getCachePath($postalCode, $keyword),
        json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );
}

function businessWords(string $name): array
{
    $stopWords = [
        'bar', 'restaurante', 'restaurant', 'hotel', 'hostal', 'aparthotel',
        'clinica', 'inmobiliaria', 'centro', 'spa', 'marbella', 'malaga',
        'espana', 'spain', 'sl', 'slu', 'sll', 'the', 'la', 'el', 'los',
        'las', 'de', 'del', 'y', 'and',
    ];

    return array_values(array_unique(array_filter(explode(' ', normalizeText($name)), function ($word) use ($stopWords) {
        return strlen($word) >= 3 && !in_array($word, $stopWords, true);
    })));
}

function domainText(string $url): string
{
    $host = parse_url($url, PHP_URL_HOST) ?: $url;
    $host = preg_replace('/^www\./', '', strtolower($host));
    $host = preg_replace('/\.[a-z]{2,}(\.[a-z]{2})?$/', '', $host);

    return normalizeText(str_replace(['-', '_'], ' ', $host));
}

function isSuspiciousExternalDomain(string $url): bool
{
    $blocked = [
        'facebook.', 'instagram.', 'linkedin.', 'x.com', 'twitter.', 'youtube.',
        'booking.', 'tripadvisor.', 'expedia.', 'hotels.', 'trivago.', 'kayak.',
        'airbnb.', 'vrbo.', 'thefork.', 'glovo.', 'ubereats.', 'just-eat.',
        'google.', 'maps.google.', 'business.site', 'linktr.ee', 'wixsite.',
        'blogspot.', 'wordpress.com', 'paginasamarillas.', 'cylex.', 'yelp.',
        'foursquare.', 'restaurantguru.', 'nicelocal.', 'eureka.com', 'cortijo.net',
    ];

    $lower = strtolower($url);

    foreach ($blocked as $domain) {
        if (str_contains($lower, $domain)) {
            return true;
        }
    }

    return false;
}

function websiteMatchesBusiness(string $website, string $businessName): bool
{
    if ($website === '' || isSuspiciousExternalDomain($website)) {
        return false;
    }

    $domain = domainText($website);
    $words = businessWords($businessName);

    if (empty($words)) {
        return true;
    }

    $matches = 0;

    foreach ($words as $word) {
        if (str_contains($domain, $word)) {
            $matches++;
        }
    }

    return count($words) === 1 ? $matches >= 1 : $matches >= 1;
}

function chooseOfficialWebsite(array $candidates, string $businessName): array
{
    $cleanCandidates = [];
    $discarded = [];

    foreach ($candidates as $candidate) {
        $website = normalizeWebsiteUrl((string) $candidate);

        if ($website === '') {
            continue;
        }

        $key = strtolower(rtrim($website, '/'));

        if (isset($cleanCandidates[$key])) {
            continue;
        }

        $cleanCandidates[$key] = $website;
    }

    foreach ($cleanCandidates as $website) {
        if (isSuspiciousExternalDomain($website)) {
            $discarded[] = $website;
            continue;
        }

        if (websiteMatchesBusiness($website, $businessName)) {
            return [
                'website' => $website,
                'is_doubtful' => false,
                'discarded_website' => '',
            ];
        }

        $discarded[] = $website;
    }

    return [
        'website' => '',
        'is_doubtful' => !empty($discarded),
        'discarded_website' => $discarded[0] ?? '',
    ];
}

function normalizeWebsiteUrl(string $url): string
{
    $url = trim($url);

    if ($url === '') {
        return '';
    }

    if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
        $url = 'https://' . $url;
    }

    return $url;
}

function normalizePhone(string $phone): string
{
    $digits = preg_replace('/[^0-9]/', '', $phone);

    if (str_starts_with($digits, '0034')) {
        $digits = substr($digits, 4);
    } elseif (str_starts_with($digits, '34') && strlen($digits) === 11) {
        $digits = substr($digits, 2);
    }

    if (!preg_match('/^[6789][0-9]{8}$/', $digits) || preg_match('/(\d)\1{5,}/', $digits)) {
        return '';
    }

    return '+34 ' . substr($digits, 0, 3) . ' ' . substr($digits, 3, 3) . ' ' . substr($digits, 6, 3);
}

function normalizeSpanishPhone(string $phone): string
{
    return normalizePhone($phone);
}

function normalizeEmailCandidate(string $email): string
{
    $email = trim($email);

    if ($email === '') {
        return '';
    }

    if (preg_match('/^\d{3,}([a-z][a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,})$/i', $email, $match)) {
        $email = $match[1];
    }

    if (preg_match('/^(.+?\.(?:com|es|net|org|info|hotel|travel|com\.es))[a-z]{2,}$/i', $email, $match)) {
        $email = $match[1];
    }

    $decoded = base64_decode($email, true);

    if ($decoded !== false && filter_var($decoded, FILTER_VALIDATE_EMAIL)) {
        return strtolower($decoded);
    }

    return filter_var($email, FILTER_VALIDATE_EMAIL) ? strtolower($email) : '';
}

function normalizeInstagramUrl(string $url): string
{
    $url = trim($url);

    if ($url === '') {
        return '';
    }

    if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
        $url = 'https://www.instagram.com/' . ltrim($url, '@');
    }

    $host = strtolower(parse_url($url, PHP_URL_HOST) ?? '');
    $path = trim(parse_url($url, PHP_URL_PATH) ?? '', '/');
    $handle = explode('/', $path)[0] ?? '';
    $blocked = ['', 'p', 'reel', 'reels', 'stories', 'explore', 'accounts'];

    if (!str_contains($host, 'instagram.com') || in_array(strtolower($handle), $blocked, true)) {
        return '';
    }

    return preg_match('/^[a-zA-Z0-9._]{3,30}$/', $handle)
        ? 'https://www.instagram.com/' . $handle . '/'
        : '';
}
