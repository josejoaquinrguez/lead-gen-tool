<?php

require_once __DIR__ . '/utils.php';

function analyzeBusiness(array $business): array
{
    $score = 0;
    $issues = [];
    $audit = $business['audit'] ?? [];
    $website = trim($business['website'] ?? '');

    if ($website === '') {
        $score += 40;
        $issues[] = 'Sin web';
    }

    if (!empty($business['website_is_doubtful'])) {
        $score += 30;
        $issues[] = 'Web dudosa';
    }

    if (empty($business['phone'])) {
        $score += 15;
        $issues[] = 'Sin telefono';
    }

    if (empty($business['email'])) {
        $score += 7;
        $issues[] = 'Sin email visible';
    }

    if (empty($business['address'])) {
        $score += 10;
        $issues[] = 'Direccion incompleta';
    }

    if (empty($business['instagram']) && empty($business['facebook']) && empty($audit['has_instagram']) && empty($audit['has_facebook'])) {
        $score += 10;
        $issues[] = 'Sin redes visibles';
    }

    if ($website !== '' && !empty($audit['checked'])) {
        if (empty($audit['reachable'])) {
            $score += 35;
            $issues[] = 'Web no responde';
        } else {
            if (empty($audit['ssl'])) {
                $score += 15;
                $issues[] = 'Sin HTTPS';
            }

            if (($audit['load_time'] ?? 0) > 3) {
                $score += 10;
                $issues[] = 'Web lenta';
            }

            if (empty($audit['responsive'])) {
                $score += 15;
                $issues[] = 'No responsive';
            }

            if (empty($audit['has_viewport'])) {
                $score += 10;
                $issues[] = 'Sin meta viewport';
            }

            if (empty($audit['has_meta_description'])) {
                $score += 8;
                $issues[] = 'Sin meta description';
            }

            if (empty($audit['has_open_graph'])) {
                $score += 6;
                $issues[] = 'Sin Open Graph';
            }

            if (empty($audit['has_favicon'])) {
                $score += 6;
                $issues[] = 'Sin favicon';
            }

            if (empty($audit['has_form'])) {
                $score += 10;
                $issues[] = 'Sin formulario';
            }

            if (empty($audit['has_cta'])) {
                $score += 10;
                $issues[] = 'Sin CTA clara';
            }

            if (empty($audit['has_whatsapp'])) {
                $score += 8;
                $issues[] = 'Sin WhatsApp';
            }

            if (empty($audit['has_trust_signals'])) {
                $score += 5;
                $issues[] = 'Pocas senales de confianza';
            }

            if (!empty($audit['wordpress'])) {
                $issues[] = 'WordPress detectado';
            }

            if (!empty($audit['elementor'])) {
                $issues[] = 'Elementor detectado';
            }

            if (!empty($audit['suspicious_domain'])) {
                $score += 30;
                $issues[] = 'Dominio sospechoso';
            }
        }
    }

    $score = min(100, $score);
    $level = match (true) {
        $score >= 65 => 'Lead Alto',
        $score >= 35 => 'Lead Medio',
        default => 'Lead Bajo',
    };

    return [
        'score' => $score,
        'level' => $level,
        'issues' => array_values(array_unique($issues)),
        'has_bad_website' => hasBadWebsite($business),
        'website_down' => $website !== '' && !empty($audit['checked']) && empty($audit['reachable']),
    ];
}

function hasBadWebsite(array $business): bool
{
    $website = trim($business['website'] ?? '');
    $audit = $business['audit'] ?? [];

    if ($website === '' || empty($audit['checked']) || empty($audit['reachable'])) {
        return false;
    }

    $badChecks = [
        empty($audit['ssl']),
        empty($audit['responsive']),
        empty($audit['has_form']),
        empty($audit['has_cta']),
        empty($audit['has_favicon']),
        empty($audit['has_meta_description']),
        empty($audit['has_open_graph']),
        empty($audit['has_whatsapp']),
        ($audit['load_time'] ?? 0) > 3,
    ];

    return count(array_filter($badChecks)) >= 2;
}
