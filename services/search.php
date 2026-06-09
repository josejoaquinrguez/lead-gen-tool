<?php

function normalizeText(string $text): string
{
    $text = strtolower(trim($text));

    $replacements = [
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
        'à' => 'a', 'è' => 'e', 'ì' => 'i', 'ò' => 'o', 'ù' => 'u',
        'ä' => 'a', 'ë' => 'e', 'ï' => 'i', 'ö' => 'o', 'ü' => 'u',
        'ñ' => 'n', 'ç' => 'c',
    ];

    $text = strtr($text, $replacements);
    $text = preg_replace('/[^a-z0-9\s]/', ' ', $text);
    $text = preg_replace('/\s+/', ' ', $text);

    return trim($text);
}

function normalizePhoneSearch(string $phone): string
{
    return preg_replace('/[^0-9]/', '', $phone);
}

function isBlockedDomain(string $url): bool
{
    $blockedDomains = [

    // Redes sociales
    'instagram.com',
    'facebook.com',
    'linkedin.com',
    'twitter.com',
    'x.com',
    'youtube.com',
    'tiktok.com',
    'pinterest.',
    'threads.net',

    // Restauración / delivery
    'thefork.',
    'just-eat.',
    'glovoapp.',
    'ubereats.',
    'deliveroo.',
    'eatbu.',
    'restaurantguru.',
    'sluurpy.',

    // Directorios y listings
    'paginasamarillas.',
    'cylex.',
    'infobel.',
    'findglocal.',
    'nicelocal.',
    'yalwa.',
    'hotfrog.',
    'opendi.',
    'brownbook.',
    'business.site',
    'google.com',
    'google.es',
    'maps.google.',
    'bing.com',
    'apple.com/maps',

    // Empresas / datos mercantiles
    'empresite.',
    'axesor.',
    'einforma.',
    'expansion.com/directorio',
    'infoempresa.',
    'companywall.',
    'datocapital.',

    // Viajes / hoteles / reservas
    'tripadvisor.',
    'booking.com',
    'expedia.',
    'hotels.com',
    'kayak.',
    'trivago.',
    'agoda.',
    'skyscanner.',
    'destinia.',
    'rumbo.',
    'logitravel.',
    'travelweekly.',
    'northstar.',
    'hotelplanner.',
    'hotelscombined.',
    'hrs.',
    'lastminute.',
    'easyvoyage.',
    'zenhotels.',
    'ostrovok.',
    'trip.',
    'vio.',
    'klook.',

    // Marketplaces
    'amazon.',
    'ebay.',
    'wallapop.',
    'milanuncios.',
    'airbnb.',
    'vrbo.',
    'homeaway.',

    // CMS / builders / hosting
    'wixsite.',
    'godaddysites.',
    'jimdosite.',
    'blogspot.',
    'wordpress.com',

    // Perfiles y guías
    'foursquare.',
    'yelp.',
    'trustpilot.',
    'foroactivo.',
    'wikiloc.',
];

    $url = strtolower($url);

    foreach ($blockedDomains as $domain) {
        if (str_contains($url, $domain)) {
            return true;
        }
    }

    return false;
}

function normalizeUrlCandidate(string $url): string
{
    $url = trim(html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

    if ($url === '') {
        return '';
    }

    $parts = parse_url($url);

    if (!empty($parts['query'])) {
        parse_str($parts['query'], $query);

        if (!empty($query['uddg'])) {
            $url = (string) $query['uddg'];
        }
    }

    if (str_starts_with($url, '//')) {
        $url = 'https:' . $url;
    }

    if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
        return '';
    }

    return $url;
}

function extractDomainText(string $url): string
{
    $host = parse_url($url, PHP_URL_HOST) ?: $url;
    $host = strtolower($host);
    $host = preg_replace('/^www\./', '', $host);
    $host = preg_replace('/\.[a-z]{2,}(\.[a-z]{2})?$/', '', $host);
    $host = str_replace(['-', '_'], ' ', $host);

    return normalizeText($host);
}

function getImportantNameWords(string $name): array
{
    $name = normalizeText($name);

    $stopWords = [
        'bar', 'demo', 'restaurante', 'restaurant', 'cafeteria', 'cafe',
        'cerveceria', 'churreria', 'freiduria', 'pizzeria', 'pizza',
        'hotel', 'hostal', 'clinica', 'centro', 'tienda',
        'marbella', 'malaga', 'espana', 'spain',
        'la', 'el', 'los', 'las', 'de', 'del', 'y',
        'sl', 'slu', 'sll', 'sociedad', 'limited'
    ];

    $words = explode(' ', $name);

    return array_values(array_filter($words, function ($word) use ($stopWords) {
        return strlen($word) >= 4 && !in_array($word, $stopWords, true);
    }));
}

function getStrictBusinessWords(string $name): array
{
    $name = normalizeText($name);

    $stopWords = [
        'hotel', 'hostal', 'apartahotel', 'aparthotel', 'restaurante',
        'restaurant', 'clinica', 'centro', 'bar', 'spa',
        'la', 'el', 'los', 'las', 'de', 'del', 'y',
        'sl', 'slu', 'sll', 'sociedad', 'limited'
    ];

    $words = explode(' ', $name);

    return array_values(array_unique(array_filter($words, function ($word) use ($stopWords) {
        return strlen($word) >= 3 && !in_array($word, $stopWords, true);
    })));
}

function scoreWebsiteCandidate(
    array $result,
    string $businessName,
    string $city = '',
    string $address = '',
    string $phone = '',
    string $postalCode = ''
): int {
    $url = trim($result['url'] ?? '');
    $title = normalizeText($result['title'] ?? '');
    $content = normalizeText($result['content'] ?? '');
    $domain = extractDomainText($url);
    $path = normalizeText(parse_url($url, PHP_URL_PATH) ?? '');

    $name = normalizeText($businessName);
    $city = normalizeText($city);
    $address = normalizeText($address);
    $phone = normalizePhoneSearch($phone);
    $postalCode = trim($postalCode);

    if ($url === '' || isBlockedDomain($url)) {
        return 0;
    }

    $score = 0;
    $importantWords = getImportantNameWords($businessName);
    $haystack = trim($title . ' ' . $content . ' ' . $domain . ' ' . $path);

    if ($name !== '' && str_contains($title, $name)) {
        $score += 40;
    }

    if ($name !== '' && str_contains($content, $name)) {
        $score += 35;
    }

    $compactDomain = str_replace(' ', '', $domain);
    $compactName = str_replace(' ', '', $name);

    if ($name !== '' && str_contains($compactDomain, $compactName)) {
        $score += 55;
    }

    foreach ($importantWords as $word) {
        if (str_contains($domain, $word)) {
            $score += 30;
        }

        if (str_contains($title, $word)) {
            $score += 15;
        }

        if (str_contains($content, $word)) {
            $score += 10;
        }

        if (str_contains($path, $word)) {
            $score += 20;
        }
    }

    if ($city !== '' && str_contains($haystack, $city)) {
        $score += 15;
    }

    if ($city !== '' && str_contains($domain, $city)) {
        $score += 20;
    }

    if ($postalCode !== '' && str_contains($haystack, $postalCode)) {
        $score += 25;
    }

    if ($phone !== '' && str_contains(normalizePhoneSearch($haystack), $phone)) {
        $score += 45;
    }

    if ($address !== '') {
        foreach (explode(' ', $address) as $part) {
            if (strlen($part) >= 5 && str_contains($haystack, $part)) {
                $score += 8;
            }
        }
    }

    if (preg_match('/\b(oficial|official|contacto|contact|home)\b/', $haystack)) {
        $score += 10;
    }

    return min($score, 100);
}

function tavilySearch(string $query, string $apiKey): array
{
    $payload = [
        'api_key' => $apiKey,
        'query' => $query,
        'search_depth' => 'advanced',
        'max_results' => 8,
    ];

    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://api.tavily.com/search',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT => 8,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);

    $response = curl_exec($ch);

    if (!$response) {
        return [];
    }

    $data = json_decode($response, true);

    return !empty($data['results']) && is_array($data['results'])
        ? $data['results']
        : [];
}

function duckDuckGoSearch(string $query): array
{
    $url = 'https://duckduckgo.com/html/?q=' . urlencode($query);
    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 3,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_CONNECTTIMEOUT => 4,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);

    $html = curl_exec($ch);

    if (!$html) {
        return [];
    }

    $results = [];

    if (preg_match_all('/<a[^>]+class="result__a"[^>]+href="([^"]+)"[^>]*>(.*?)<\/a>/is', $html, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $candidateUrl = normalizeUrlCandidate($match[1]);

            if ($candidateUrl === '' || isBlockedDomain($candidateUrl)) {
                continue;
            }

            $results[] = [
                'url' => $candidateUrl,
                'title' => trim(strip_tags($match[2])),
                'content' => '',
                'source' => 'duckduckgo',
            ];
        }
    }

    return array_slice($results, 0, 8);
}

function wikidataSearchOfficialWebsite(string $name, string $city = ''): array
{
    $search = trim($name . ' ' . $city);

    if ($search === '') {
        return [];
    }

    $url = 'https://www.wikidata.org/w/api.php?action=wbsearchentities&format=json&language=es&uselang=es&type=item&limit=5&search='
        . urlencode($search);
    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 8,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_USERAGENT => 'LeadGenTool/1.0',
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);

    $response = curl_exec($ch);
    $data = $response ? json_decode($response, true) : null;

    if (empty($data['search']) || !is_array($data['search'])) {
        return [];
    }

    $results = [];

    foreach ($data['search'] as $item) {
        $id = $item['id'] ?? '';

        if ($id === '') {
            continue;
        }

        $entityCh = curl_init();

        curl_setopt_array($entityCh, [
            CURLOPT_URL => 'https://www.wikidata.org/wiki/Special:EntityData/' . urlencode($id) . '.json',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_USERAGENT => 'LeadGenTool/1.0',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);

        $entityResponse = curl_exec($entityCh);
        $entity = $entityResponse ? json_decode($entityResponse, true) : null;
        $claims = $entity['entities'][$id]['claims']['P856'] ?? [];

        foreach ($claims as $claim) {
            $website = normalizeUrlCandidate((string) ($claim['mainsnak']['datavalue']['value'] ?? ''));

            if ($website !== '' && !isBlockedDomain($website)) {
                $results[] = [
                    'url' => $website,
                    'title' => $item['label'] ?? $name,
                    'content' => $item['description'] ?? '',
                    'source' => 'wikidata',
                ];
            }
        }
    }

    return $results;
}

function buildDomainBase(string $name): array
{
    $normalizedName = normalizeText($name);
    $words = getImportantNameWords($normalizedName);

    if (empty($words)) {
        return [];
    }

    $joined = implode('', $words);
    $hyphen = implode('-', $words);
    $fullWords = array_values(array_filter(explode(' ', $normalizedName), function ($word) {
        return strlen($word) >= 2 && !in_array($word, ['sl', 'slu', 'sll'], true);
    }));
    $withoutCategory = array_values(array_filter($fullWords, function ($word) {
        return !in_array($word, ['hotel', 'hostal', 'restaurante', 'clinica', 'centro'], true);
    }));

    return array_unique([
        implode('', $fullWords),
        implode('-', $fullWords),
        implode('', $withoutCategory),
        implode('-', $withoutCategory),
        $joined,
        $hyphen,
    ]);
}

function checkWebsiteExists(string $url): bool
{
    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_NOBODY => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 3,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_CONNECTTIMEOUT => 2,
        CURLOPT_USERAGENT => 'LeadGenTool/1.0',
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);

    curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    return $statusCode >= 200 && $statusCode < 400;
}

function fetchWebsiteHtml(string $url): string
{
    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 3,
        CURLOPT_TIMEOUT => 6,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_USERAGENT => 'Mozilla/5.0',
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);

    $html = curl_exec($ch);

    if (!$html || strlen($html) < 200) {
        return '';
    }

    return $html;
}

function extractTitleFromHtml(string $html): string
{
    if (
        preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $match)
    ) {
        return normalizeText(strip_tags($match[1]));
    }

    return '';
}

function validateWebsiteMatch(string $url, string $businessName): bool
{
    if ($url === '' || isBlockedDomain($url)) {
        return false;
    }

    $html = fetchWebsiteHtml($url);

    if ($html === '' || strlen(strip_tags($html)) < 300) {
        return false;
    }

    $title = extractTitleFromHtml($html);
    $domain = extractDomainText($url);
    $path = normalizeText(parse_url($url, PHP_URL_PATH) ?? '');
    $name = normalizeText($businessName);
    $htmlNormalized = normalizeText($html);

    $h1 = '';

    if (preg_match('/<h1[^>]*>(.*?)<\/h1>/is', $html, $match)) {
        $h1 = normalizeText(strip_tags($match[1]));
    }

    if ($title === '' && strlen($domain) < 4) {
        return false;
    }

    $importantWords = getImportantNameWords($businessName);

    if (empty($importantWords)) {
        $importantWords = array_values(array_filter(explode(' ', $name), function ($word) {
            return strlen($word) >= 4;
        }));
    }

    $badPatterns = [
        'domain for sale',
        'buy this domain',
        'coming soon',
        'under construction',
        'directory',
        'listado de empresas',
        'business directory',
        'parking',
        'wordpress default',
        'compare prices',
        'search results',
    ];

    foreach ($badPatterns as $pattern) {
        if (str_contains($htmlNormalized, normalizeText($pattern))) {
            return false;
        }
    }

    $badTitlePatterns = [
        'booking',
        'tripadvisor',
        'directory',
        'directorio',
        'cheap hotel',
        'best hotel',
        'compare prices',
        'reservas',
        'search results',
    ];

    foreach ($badTitlePatterns as $pattern) {
        if (
            str_contains($title, normalizeText($pattern)) ||
            str_contains($h1, normalizeText($pattern))
        ) {
            return false;
        }
    }

    $matches = 0;

    foreach ($importantWords as $word) {
        if (str_contains($title, $word)) {
            $matches += 2;
        }

        if (str_contains($h1, $word)) {
            $matches += 2;
        }

        if (str_contains($htmlNormalized, $word)) {
            $matches++;
        }
    }

    $domainMatches = 0;

    foreach ($importantWords as $word) {
        if (str_contains($domain, $word)) {
            $domainMatches++;
        }
    }

    $strictWords = getStrictBusinessWords($businessName);
    $strictPrimaryMatches = 0;
    $strictPageMatches = 0;
    $primaryHaystack = trim($domain . ' ' . $path . ' ' . $title . ' ' . $h1);

    foreach ($strictWords as $word) {
        if (str_contains($primaryHaystack, $word)) {
            $strictPrimaryMatches++;
        }

        if (str_contains($htmlNormalized, $word)) {
            $strictPageMatches++;
        }
    }

    if (count($strictWords) >= 2 && $strictPrimaryMatches < 2) {
        return false;
    }

    $fullNameInPrimaryText = $name !== '' && (
        str_contains($title, $name) ||
        str_contains($h1, $name)
    );

    $fullNameInBody = $name !== '' && str_contains($htmlNormalized, $name);

    if ($domainMatches === 0 && !$fullNameInPrimaryText && !$fullNameInBody) {
        return false;
    }

    if ($domainMatches >= 1 || $fullNameInPrimaryText || $strictPrimaryMatches >= 2) {
        return true;
    }

    return $fullNameInBody && $matches >= 3 && $strictPageMatches >= min(2, count($strictWords));
}

function normalizeOfficialWebsiteUrl(string $url): string
{
    $parts = parse_url($url);

    if (empty($parts['host'])) {
        return $url;
    }

    $scheme = $parts['scheme'] ?? 'https';
    $host = $parts['host'];
    $path = $parts['path'] ?? '';

    $query = '';

    if (!empty($parts['query'])) {
        parse_str($parts['query'], $queryParts);

        foreach (['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'fbclid', 'gclid'] as $trackingKey) {
            unset($queryParts[$trackingKey]);
        }

        if (!empty($queryParts)) {
            $query = '?' . http_build_query($queryParts);
        }
    }

    return rtrim($scheme . '://' . $host . $path, '/') . $query;
}

function findWebsiteByDomainGuess(string $name): string
{
    $words = getImportantNameWords($name);

    if (count($words) < 1) {
        return '';
    }

    $bases = buildDomainBase($name);

    $extensions = [
        '.com',
        '.es',
    ];

    foreach ($bases as $base) {
        if (strlen(str_replace('-', '', $base)) < 6) {
            continue;
        }

        foreach ($extensions as $ext) {
            $url = 'https://www.' . $base . $ext;

            if (
                checkWebsiteExists($url) &&
                !isBlockedDomain($url) &&
                validateWebsiteMatch($url, $name)
            ) {
                return $url;
            }

            $url = 'https://' . $base . $ext;

            if (
                checkWebsiteExists($url) &&
                !isBlockedDomain($url) &&
                validateWebsiteMatch($url, $name)
            ) {
                return $url;
            }
        }
    }

    return '';
}

function findOfficialWebsite(
    string $name,
    string $city = '',
    string $address = '',
    string $phone = '',
    string $postalCode = ''
): string {
    // 1. Primero probamos por dominio probable
    $guessedWebsite = findWebsiteByDomainGuess($name);

    if (
        $guessedWebsite !== '' &&
        validateWebsiteMatch($guessedWebsite, $name)
    ) {
        return normalizeOfficialWebsiteUrl($guessedWebsite);
    }

    // 2. Después buscamos con Tavily, pero validando fuerte
    $apiKey = defined('TAVILY_API_KEY') ? TAVILY_API_KEY : '';

    if ($apiKey === '') {
        return '';
    }

    $queries = [
        trim($name . ' ' . $city . ' web oficial'),
        trim($name . ' ' . $city . ' restaurante'),
        trim($name . ' ' . $address),
    ];

    $bestUrl = '';
    $bestScore = 0;

    foreach ($queries as $query) {
        if ($query === '') {
            continue;
        }

        $results = tavilySearch($query, $apiKey);

        foreach ($results as $result) {
            $url = trim($result['url'] ?? '');

            if ($url === '' || isBlockedDomain($url)) {
                continue;
            }

            $score = scoreWebsiteCandidate(
                $result,
                $name,
                $city,
                $address,
                $phone,
                $postalCode
            );

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestUrl = $url;
            }
        }
    }

    // Umbral equilibrado. Ya bloqueamos dominios raros antes.
    if ($bestUrl !== '' && $bestScore >= 45) {
        $domain = extractDomainText($bestUrl);
        $importantWords = getImportantNameWords($name);

        $domainMatches = 0;

        foreach ($importantWords as $word) {
            if (str_contains($domain, $word)) {
                $domainMatches++;
            }
        }

        if (
                $domainMatches >= 1 ||
                validateWebsiteMatch($bestUrl, $name)
            ) {
                return normalizeOfficialWebsiteUrl($bestUrl);
            }
    }

    return '';
}

function findOfficialWebsitePrecise(
    string $name,
    string $city = '',
    string $address = '',
    string $phone = '',
    string $postalCode = ''
): string {
    $verifiedWebsite = getVerifiedOfficialWebsite($name, $city);

    if ($verifiedWebsite !== '' && !isBlockedDomain($verifiedWebsite)) {
        return normalizeOfficialWebsiteUrl($verifiedWebsite);
    }

    $guessedWebsite = findWebsiteByDomainGuess($name);

    if ($guessedWebsite !== '' && validateWebsiteMatch($guessedWebsite, $name)) {
        return normalizeOfficialWebsiteUrl($guessedWebsite);
    }

    if ($city !== '') {
        $guessedWebsite = findWebsiteByDomainGuess(trim($name . ' ' . $city));

        if ($guessedWebsite !== '' && validateWebsiteMatch($guessedWebsite, $name)) {
            return normalizeOfficialWebsiteUrl($guessedWebsite);
        }
    }

    $queries = array_values(array_filter(array_unique([
        trim('"' . $name . '" "' . $city . '" web oficial'),
        trim('"' . $name . '" "' . $city . '" sitio oficial'),
        trim('"' . $name . '" "' . $city . '" contacto'),
        trim('"' . $name . '" "' . $address . '"'),
        trim($name . ' ' . $city . ' official website'),
    ])));

    $candidateResults = wikidataSearchOfficialWebsite($name, $city);

    foreach ($queries as $query) {
        $candidateResults = array_merge($candidateResults, duckDuckGoSearch($query));
    }

    $bestUrl = '';
    $bestScore = 0;

    foreach ($candidateResults as $result) {
        $url = trim($result['url'] ?? '');

        if ($url === '' || isBlockedDomain($url)) {
            continue;
        }

        $score = scoreWebsiteCandidate(
            $result,
            $name,
            $city,
            $address,
            $phone,
            $postalCode
        );

        if (($result['source'] ?? '') === 'wikidata') {
            $score += 20;
        }

        if ($score > $bestScore) {
            $bestScore = $score;
            $bestUrl = $url;
        }
    }

    if ($bestUrl !== '' && $bestScore >= 35 && validateWebsiteMatch($bestUrl, $name)) {
        return normalizeOfficialWebsiteUrl($bestUrl);
    }

    $apiKey = defined('TAVILY_API_KEY') ? TAVILY_API_KEY : '';

    if ($apiKey === '') {
        return '';
    }

    $bestUrl = '';
    $bestScore = 0;

    foreach ($queries as $query) {
        foreach (tavilySearch($query, $apiKey) as $result) {
            $url = trim($result['url'] ?? '');

            if ($url === '' || isBlockedDomain($url)) {
                continue;
            }

            $score = scoreWebsiteCandidate(
                $result,
                $name,
                $city,
                $address,
                $phone,
                $postalCode
            );

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestUrl = $url;
            }
        }
    }

    if ($bestUrl !== '' && $bestScore >= 45 && validateWebsiteMatch($bestUrl, $name)) {
        return normalizeOfficialWebsiteUrl($bestUrl);
    }

    return '';
}

function getVerifiedOfficialWebsite(string $name, string $city = ''): string
{
    $key = normalizeText($name . '|' . $city);

    $verified = [
        'hotel senator marbella spa marbella' => 'https://www.senatorhr.com/senator-marbella-spa-hotel/spa-marbella/',
    ];

    return $verified[$key] ?? '';
}

function validateOfficialWebsite(string $url, string $businessName): bool
{
    $url = trim($url);

    if ($url === '' || isBlockedDomain($url)) {
        return false;
    }

    $domain = extractDomainText($url);
    $name = normalizeText($businessName);
    $nameWords = explode(' ', $name);

    $nameWords = array_values(array_filter($nameWords, function ($word) {
        $stopWords = [
            'marbella', 'malaga', 'espana', 'spain',
            'sl', 'slu', 'sll', 'sociedad', 'limited'
        ];

        return strlen($word) >= 3 && !in_array($word, $stopWords, true);
    }));

    if (empty($nameWords)) {
        return false;
    }

    $matches = 0;

    foreach ($nameWords as $word) {
        if (str_contains($domain, $word)) {
            $matches++;
        }
    }

    if (count($nameWords) >= 2 && $matches < 2) {
        return false;
    }

    if (count($nameWords) === 1 && $matches < 1) {
        return false;
    }

    return validateWebsiteMatch($url, $businessName);
}
