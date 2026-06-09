<?php

require_once __DIR__ . '/../services/utils.php';
require_once __DIR__ . '/../services/places.php';
require_once __DIR__ . '/../services/website_analyzer.php';
require_once __DIR__ . '/../services/analyzer.php';

$postalCode = trim($_GET['postal_code'] ?? '');
$keyword = trim($_GET['keyword'] ?? '');
$filter = $_GET['filter'] ?? 'all';
$forceRefresh = ($_GET['force'] ?? '') === '1';

if (!preg_match('/^\d{5}$/', $postalCode) || $keyword === '') {
    exit('Parametros invalidos.');
}

$response = getPlaces($keyword, $postalCode, $forceRefresh);
$results = $response['results'] ?? [];

foreach ($results as &$business) {
    $business['audit'] = analyzeWebsite($business['website'] ?? '');
    enrichBusinessContactsForExport($business);
    $business['analysis'] = analyzeBusiness($business);
}

unset($business);

$results = array_values(array_filter($results, function ($business) use ($filter) {
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

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename=leads_excel.csv');

echo "\xEF\xBB\xBF";
echo "sep=;\r\n";

$output = fopen('php://output', 'w');
$delimiter = ';';

fputcsv($output, [
    'Nombre',
    'Categoria',
    'Direccion',
    'Telefono',
    'Email',
    'Web',
    'Web dudosa',
    'Latitud',
    'Longitud',
    'Score',
    'Nivel',
    'Problemas detectados',
    'Instagram',
    'Facebook',
    'WordPress',
    'Elementor',
    'WooCommerce',
    'Responsive',
    'SSL',
    'Estado web',
    'HTTP',
    'Tiempo carga',
], $delimiter, '"', '\\');

foreach ($results as $business) {
    $audit = $business['audit'] ?? [];
    $analysis = $business['analysis'] ?? [];

    fputcsv($output, [
        $business['name'] ?? '',
        $business['category'] ?? '',
        $business['address'] ?? '',
        $business['phone'] ?? '',
        $business['email'] ?? '',
        $business['website'] ?? '',
        !empty($business['website_is_doubtful']) ? 'Si' : 'No',
        $business['latitude'] ?? '',
        $business['longitude'] ?? '',
        $analysis['score'] ?? '',
        $analysis['level'] ?? '',
        implode(' | ', $analysis['issues'] ?? []),
        $business['instagram'] ?? '',
        $business['facebook'] ?? '',
        !empty($audit['wordpress']) ? 'Si' : 'No',
        !empty($audit['elementor']) ? 'Si' : 'No',
        !empty($audit['woocommerce']) ? 'Si' : 'No',
        !empty($audit['responsive']) ? 'Si' : 'No',
        !empty($audit['ssl']) ? 'Si' : 'No',
        !empty($audit['reachable']) ? 'Responde' : (!empty($business['website']) ? 'No responde' : 'Sin web'),
        $audit['http_status'] ?? '',
        $audit['load_time'] ?? '',
    ], $delimiter, '"', '\\');
}

fclose($output);

function enrichBusinessContactsForExport(array &$business): void
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
