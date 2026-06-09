<?php

require_once __DIR__ . '/services/utils.php';
require_once __DIR__ . '/services/places.php';
require_once __DIR__ . '/services/website_analyzer.php';
require_once __DIR__ . '/services/analyzer.php';
require_once __DIR__ . '/services/database.php';

$postalCode = trim($_GET['postal_code'] ?? '');
$keyword = trim($_GET['keyword'] ?? '');
$filter = $_GET['filter'] ?? 'all';
$forceRefresh = ($_GET['force'] ?? '') === '1';

$results = [];
$error = '';
$cached = false;
$allResults = [];
$dbStatus = [
    'enabled' => leadDbEnabled(),
    'saved' => false,
    'message' => leadDbEnabled() ? 'MySQL pendiente' : 'MySQL desactivado',
];
$stats = [
    'total' => 0,
    'high' => 0,
    'medium' => 0,
    'low' => 0,
    'no_web' => 0,
    'improvable_web' => 0,
    'doubtful_web' => 0,
    'down_web' => 0,
];

if ($postalCode !== '' || $keyword !== '') {
    if (!preg_match('/^\d{5}$/', $postalCode)) {
        $error = 'Introduce un codigo postal valido de 5 digitos.';
    } elseif ($keyword === '') {
        $error = 'Introduce un nicho o palabra clave.';
    } else {
        $placesResponse = getPlaces($keyword, $postalCode, $forceRefresh);
        $results = $placesResponse['results'] ?? [];
        $error = $placesResponse['error'] ?? '';
        $cached = !empty($placesResponse['cached']);

        foreach ($results as &$business) {
            $business['audit'] = analyzeWebsite($business['website'] ?? '');
            enrichBusinessContacts($business);
            $business['analysis'] = analyzeBusiness($business);
        }

        unset($business);

        $allResults = $results;
        $stats = buildStats($allResults);
        $dbStatus = saveSearchResultsToDatabase($postalCode, $keyword, $filter, $allResults, $stats, $cached);
        $results = filterResults($results, $filter);

        usort($results, function ($a, $b) {
            return ($b['analysis']['score'] ?? 0) <=> ($a['analysis']['score'] ?? 0);
        });
    }
}

function enrichBusinessContacts(array &$business): void
{
    $audit = $business['audit'] ?? [];

    if (empty($business['email']) && !empty($audit['emails'][0])) {
        $business['email'] = $audit['emails'][0];
    }

    if (empty($business['phone']) && !empty($audit['phones'][0])) {
        $business['phone'] = $audit['phones'][0];
    }

    if (empty($business['instagram']) && !empty($audit['instagram_profiles'][0])) {
        $business['instagram'] = $audit['instagram_profiles'][0];
    }

    if (empty($business['facebook']) && !empty($audit['facebook_profiles'][0])) {
        $business['facebook'] = $audit['facebook_profiles'][0];
    }
}

function buildStats(array $results): array
{
    $stats = [
        'total' => count($results),
        'high' => 0,
        'medium' => 0,
        'low' => 0,
        'no_web' => 0,
        'improvable_web' => 0,
        'doubtful_web' => 0,
        'down_web' => 0,
    ];

    foreach ($results as $business) {
        $analysis = $business['analysis'] ?? [];

        match ($analysis['level'] ?? '') {
            'Lead Alto' => $stats['high']++,
            'Lead Medio' => $stats['medium']++,
            default => $stats['low']++,
        };

        if (empty($business['website'])) {
            $stats['no_web']++;
        }

        if (!empty($analysis['has_bad_website'])) {
            $stats['improvable_web']++;
        }

        if (!empty($business['website_is_doubtful'])) {
            $stats['doubtful_web']++;
        }

        if (!empty($analysis['website_down'])) {
            $stats['down_web']++;
        }
    }

    return $stats;
}

function filterResults(array $results, string $filter): array
{
    return array_values(array_filter($results, function ($business) use ($filter) {
        $analysis = $business['analysis'] ?? [];

        return match ($filter) {
            'high' => ($analysis['level'] ?? '') === 'Lead Alto',
            'medium' => ($analysis['level'] ?? '') === 'Lead Medio',
            'low' => ($analysis['level'] ?? '') === 'Lead Bajo',
            'no_web' => empty($business['website']),
            'improvable_web' => !empty($analysis['has_bad_website']),
            'doubtful_web' => !empty($business['website_is_doubtful']),
            'down_web' => !empty($analysis['website_down']),
            default => true,
        };
    }));
}

require __DIR__ . '/views/results.php';
