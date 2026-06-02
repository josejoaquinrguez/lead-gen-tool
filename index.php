<?php

require_once __DIR__ . '/services/places.php';
require_once __DIR__ . '/services/analyzer.php';

$postalCode = $_GET['postal_code'] ?? '';
$keyword = $_GET['keyword'] ?? '';
$filter = $_GET['filter'] ?? 'all';

$results = [];
$error = '';

if ($postalCode !== '' && $keyword !== '') {

    try {

        $results = getPlaces($keyword, $postalCode);

        foreach ($results as &$business) {

            $website = trim($business['website'] ?? '');
            $phone = trim($business['phone'] ?? '');
            $instagram = trim($business['instagram'] ?? '');
            $facebook = trim($business['facebook'] ?? '');

            $score = 0;
            $issues = [];

            if ($website === '') {

                $score += 40;

                if ($instagram !== '' || $facebook !== '') {

                    $issues[] = 'Solo tiene presencia en redes sociales';

                } else {

                    $issues[] = 'No tiene página web ni redes sociales visibles';
                }
            }

            if ($phone === '') {
                $score += 10;
                $issues[] = 'No tiene teléfono visible';
            }

            if ($score >= 40) {

                $level = 'Lead interesante';

            } elseif ($score >= 10) {

                $level = 'Mejorable';

            } else {

                $level = 'Correcto';
            }

            $business['analysis'] = [
                'score' => $score,
                'issues' => $issues,
                'level' => $level,
            ];
        }

        unset($business);

        usort($results, function ($a, $b) {
            return $b['analysis']['score'] <=> $a['analysis']['score'];
        });

        if ($filter !== 'all') {

            $results = array_filter($results, function ($business) use ($filter) {

                return match ($filter) {

                    'lead' =>
                        $business['analysis']['level'] === 'Lead interesante',

                    'improvable' =>
                        $business['analysis']['level'] === 'Mejorable',

                    'correct' =>
                        $business['analysis']['level'] === 'Correcto',

                    default => true,
                };
            });
        }

    } catch (Throwable $e) {

        $error = 'No se ha podido realizar la búsqueda.';
    }
}

require __DIR__ . '/views/results.php';