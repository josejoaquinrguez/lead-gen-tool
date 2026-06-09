<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lead Gen Tool - Auditoria de negocios locales</title>
    <script>
        (function () {
            var savedTheme = localStorage.getItem('leadgen-theme');
            var prefersLight = window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches;
            document.documentElement.dataset.theme = savedTheme || (prefersLight ? 'light' : 'dark');
        })();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<?php
    $totalAnalyzed = max(1, (int) ($stats['total'] ?? 0));
    $insights = [
        'with_web' => 0,
        'without_ssl' => 0,
        'wordpress' => 0,
        'responsive' => 0,
        'with_email' => 0,
        'with_phone' => 0,
        'with_instagram' => 0,
    ];

    foreach ($allResults as $item) {
        $auditItem = $item['audit'] ?? [];

        if (!empty($item['website'])) {
            $insights['with_web']++;
        }

        if (!empty($item['website']) && empty($auditItem['ssl'])) {
            $insights['without_ssl']++;
        }

        if (!empty($auditItem['wordpress'])) {
            $insights['wordpress']++;
        }

        if (!empty($auditItem['responsive'])) {
            $insights['responsive']++;
        }

        if (!empty($item['email'])) {
            $insights['with_email']++;
        }

        if (!empty($item['phone'])) {
            $insights['with_phone']++;
        }

        if (!empty($item['instagram']) || !empty($auditItem['has_instagram'])) {
            $insights['with_instagram']++;
        }
    }

    $percent = fn (int $value): int => (int) round(($value / $totalAnalyzed) * 100);
?>
<div class="app-shell">
    <aside class="sidebar">
        <div class="brand">
            <div class="brand-mark">LG</div>
            <div>
                <strong>Lead Gen Tool</strong>
                <span>Local audit engine</span>
            </div>
        </div>

        <nav class="side-nav">
            <a class="active" data-nav-icon="dashboard" href="#">Dashboard</a>
            <a data-nav-icon="leads" href="#results">Leads</a>
            <a data-nav-icon="filters" href="#filters">Filtros</a>
            <a data-nav-icon="export" href="exports/export.php?postal_code=<?= urlencode($postalCode) ?>&keyword=<?= urlencode($keyword) ?>&filter=<?= urlencode($filter) ?>">Exportar</a>
        </nav>

        <button class="theme-toggle" type="button" data-theme-toggle data-button-icon="moon">
            <span data-theme-label>Modo oscuro</span>
        </button>

        <div class="sidebar-note">
            <span>Prioridad</span>
            <strong>Webs malas, sin web y datos verificables.</strong>
        </div>

        <div class="db-note <?= !empty($dbStatus['saved']) ? 'ok' : 'idle' ?>">
            <span>Base de datos</span>
            <strong><?= h($dbStatus['message'] ?? 'MySQL desactivado') ?></strong>
        </div>
    </aside>

    <main class="main">
        <section class="hero-panel">
            <div class="hero-copy">
                <div class="eyebrow">Lead generation intelligence</div>
                <h1>Lead Gen Tool</h1>
                <p>Busca negocios locales, valida sus datos publicos y prioriza oportunidades reales para auditorias digitales.</p>
                <div class="hero-status">
                    <span></span>
                    <strong><?= h((string) $stats['high']) ?></strong> leads calientes detectados
                </div>
            </div>

            <form method="GET" action="index.php" class="search-card" data-search-form>
                <label>
                    <span>Codigo postal</span>
                    <input type="text" name="postal_code" placeholder="29600" value="<?= h($postalCode) ?>" required>
                </label>

                <label>
                    <span>Nicho</span>
                    <input type="text" name="keyword" placeholder="Restaurantes, inmobiliarias..." value="<?= h($keyword) ?>" required>
                </label>

                <button type="submit" data-button-icon="search">
                    <span>Buscar leads</span>
                    <span aria-hidden="true">-&gt;</span>
                </button>
            </form>
        </section>

        <?php if ($error !== ''): ?>
            <div class="alert error"><?= h($error) ?></div>
        <?php endif; ?>

        <section class="metrics-grid">
            <article class="metric-card accent-blue">
                <div class="metric-head">
                    <span>Negocios encontrados</span>
                    <i data-icon="database"></i>
                </div>
                <strong><?= h((string) $stats['total']) ?></strong>
                <small><?= $cached ? 'Datos cacheados' : 'Busqueda actualizada' ?></small>
                <div class="sparkline"><b></b><b></b><b></b><b></b><b></b></div>
            </article>
            <article class="metric-card accent-red">
                <div class="metric-head">
                    <span>Leads altos</span>
                    <i data-icon="target"></i>
                </div>
                <strong><?= h((string) $stats['high']) ?></strong>
                <small>Mayor oportunidad comercial</small>
                <div class="sparkline red"><b></b><b></b><b></b><b></b><b></b></div>
            </article>
            <article class="metric-card accent-amber">
                <div class="metric-head">
                    <span>Webs mejorables</span>
                    <i data-icon="activity"></i>
                </div>
                <strong><?= h((string) $stats['improvable_web']) ?></strong>
                <small>Con carencias auditables</small>
                <div class="sparkline amber"><b></b><b></b><b></b><b></b><b></b></div>
            </article>
            <article class="metric-card accent-green">
                <div class="metric-head">
                    <span>Sin web</span>
                    <i data-icon="globe"></i>
                </div>
                <strong><?= h((string) $stats['no_web']) ?></strong>
                <small>Prospectos de alta prioridad</small>
                <div class="sparkline green"><b></b><b></b><b></b><b></b><b></b></div>
            </article>
        </section>

        <?php if ($postalCode !== '' && $keyword !== ''): ?>
            <section class="results-header" id="filters">
                <div>
                    <div class="eyebrow">Resultados</div>
                    <h2>CP <?= h($postalCode) ?> - <?= h($keyword) ?></h2>
                    <p><?= h((string) count($results)) ?> negocios visibles con el filtro actual</p>
                </div>

                <div class="header-actions">
                    <a class="btn ghost" data-button-icon="refresh" href="index.php?postal_code=<?= urlencode($postalCode) ?>&keyword=<?= urlencode($keyword) ?>&filter=<?= urlencode($filter) ?>&force=1">Refrescar</a>
                    <a class="btn success" data-button-icon="download" href="exports/export.php?postal_code=<?= urlencode($postalCode) ?>&keyword=<?= urlencode($keyword) ?>&filter=<?= urlencode($filter) ?>">Exportar CSV</a>
                </div>
            </section>

            <?php
                $filters = [
                    'all' => ['Todos', $stats['total']],
                    'high' => ['Leads altos', $stats['high']],
                    'medium' => ['Leads medios', $stats['medium']],
                    'low' => ['Leads bajos', $stats['low']],
                    'no_web' => ['Sin web', $stats['no_web']],
                    'improvable_web' => ['Web mejorable', $stats['improvable_web']],
                    'doubtful_web' => ['Web dudosa', $stats['doubtful_web']],
                    'down_web' => ['Web caida', $stats['down_web']],
                ];
            ?>

            <nav class="filter-pills">
                <?php foreach ($filters as $key => [$label, $count]): ?>
                    <a class="filter-pill <?= $filter === $key ? 'active' : '' ?>" href="index.php?postal_code=<?= urlencode($postalCode) ?>&keyword=<?= urlencode($keyword) ?>&filter=<?= urlencode($key) ?>">
                        <?= h($label) ?>
                        <span><?= h((string) $count) ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>

            <section class="dashboard-grid">
                <div class="results-column">
            <?php if (empty($results)): ?>
                <section class="empty-state">
                    <div>Sin resultados</div>
                    <h3>No hay negocios para este filtro</h3>
                    <p>Prueba otro nicho, amplia el mapa de codigos postales o refresca la consulta.</p>
                </section>
            <?php else: ?>
                <section class="lead-grid" id="results">
                    <?php foreach ($results as $business): ?>
                        <?php
                            $analysis = $business['analysis'];
                            $audit = $business['audit'];
                            $score = (int) $analysis['score'];
                            $levelClass = match ($analysis['level']) {
                                'Lead Alto' => 'danger',
                                'Lead Medio' => 'warning',
                                default => 'success',
                            };
                            $mapsUrl = !empty($business['latitude']) && !empty($business['longitude'])
                                ? 'https://www.google.com/maps/search/?api=1&query=' . urlencode((string) $business['latitude'] . ',' . (string) $business['longitude'])
                                : 'https://www.google.com/maps/search/?api=1&query=' . urlencode(($business['name'] ?? '') . ' ' . ($business['address'] ?? '') . ' ' . ($business['city'] ?? ''));
                            $host = !empty($business['website']) ? (parse_url($business['website'], PHP_URL_HOST) ?: $business['website']) : '';
                        ?>
                        <article class="lead-card">
                            <div class="card-top">
                                <div>
                                    <span class="category-chip"><?= h($business['category'] ?? 'Negocio') ?></span>
                                    <h3><?= h($business['name']) ?></h3>
                                    <p><?= h($business['address'] ?: 'Direccion no disponible') ?></p>
                                </div>

                                <div class="score-ring <?= h($levelClass) ?>" style="--score: <?= h((string) $score) ?>">
                                    <span><?= h((string) $score) ?></span>
                                </div>
                            </div>

                            <div class="lead-meta">
                                <div>
                                    <span>Telefono</span>
                                    <strong><?= $business['phone'] ? h($business['phone']) : 'No registrado' ?></strong>
                                </div>
                                <div>
                                    <span>Email</span>
                                    <strong><?= !empty($business['email']) ? h($business['email']) : 'No registrado' ?></strong>
                                </div>
                                <div>
                                    <span>Instagram</span>
                                    <strong><?= !empty($business['instagram']) ? 'Disponible' : 'No registrado' ?></strong>
                                </div>
                                <div>
                                    <span>Web</span>
                                    <strong><?= $host ? h($host) : 'Sin web' ?></strong>
                                </div>
                            </div>

                            <div class="badge-row">
                                <span class="badge <?= h($levelClass) ?>"><?= h($analysis['level']) ?></span>
                                <?php if (empty($business['website'])): ?><span class="badge danger">Sin web</span><?php endif; ?>
                                <?php if (!empty($business['website_is_doubtful'])): ?><span class="badge warning">Web dudosa</span><?php endif; ?>
                                <?php if (!empty($analysis['website_down'])): ?><span class="badge danger">Web caida</span><?php endif; ?>
                                <?php if (!empty($audit['wordpress'])): ?><span class="badge neutral">WordPress</span><?php endif; ?>
                                <?php if (!empty($audit['elementor'])): ?><span class="badge neutral">Elementor</span><?php endif; ?>
                                <?php if (!empty($audit['responsive'])): ?><span class="badge success">Responsive</span><?php endif; ?>
                                <?php if (!empty($audit['ssl'])): ?><span class="badge success">SSL</span><?php endif; ?>
                            </div>

                            <div class="issues-panel">
                                <span>Motivos</span>
                                <?php $issues = array_values($analysis['issues'] ?? []); ?>
                                <ul>
                                    <?php foreach (array_slice($issues, 0, 4) as $issue): ?>
                                        <li><?= h($issue) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                                <?php if (count($issues) > 4): ?>
                                    <details>
                                        <summary>Ver <?= h((string) (count($issues) - 4)) ?> mas</summary>
                                        <ul>
                                            <?php foreach (array_slice($issues, 4, 5) as $issue): ?>
                                                <li><?= h($issue) ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </details>
                                <?php endif; ?>
                            </div>

                            <div class="tech-strip">
                                <span class="<?= !empty($audit['has_form']) ? 'ok' : 'bad' ?>">Formulario</span>
                                <span class="<?= !empty($audit['has_cta']) ? 'ok' : 'bad' ?>">CTA</span>
                                <span class="<?= !empty($audit['has_whatsapp']) ? 'ok' : 'bad' ?>">WhatsApp</span>
                                <span class="<?= !empty($audit['has_instagram']) || !empty($business['instagram']) ? 'ok' : 'bad' ?>">Redes</span>
                            </div>

                            <div class="card-actions">
                                <?php if (!empty($business['website'])): ?>
                                    <a class="btn primary" data-button-icon="external" href="<?= h($business['website']) ?>" target="_blank">Ver web</a>
                                <?php endif; ?>
                                <?php if (!empty($business['instagram'])): ?>
                                    <a class="btn ghost" data-button-icon="instagram" href="<?= h($business['instagram']) ?>" target="_blank">Instagram</a>
                                <?php endif; ?>
                                <a class="btn ghost" data-button-icon="map" href="<?= h($mapsUrl) ?>" target="_blank">Mapa</a>
                                <a class="btn ghost" data-button-icon="search" href="https://www.google.com/search?q=<?= urlencode(($business['name'] ?? '') . ' ' . ($business['city'] ?? '') . ' ' . ($business['address'] ?? '') . ' web oficial contacto') ?>" target="_blank">Google</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </section>
            <?php endif; ?>
                </div>

                <aside class="insight-rail">
                    <section class="insight-card hero-insight">
                        <span class="eyebrow">Distribucion</span>
                        <h3>Oportunidad comercial</h3>
                        <div class="donut" style="--high: <?= h((string) $percent((int) $stats['high'])) ?>; --medium: <?= h((string) $percent((int) $stats['medium'])) ?>">
                            <strong><?= h((string) $percent((int) $stats['high'])) ?>%</strong>
                            <span>lead alto</span>
                        </div>
                        <div class="legend-list">
                            <span><b class="dot red"></b>Altos <?= h((string) $stats['high']) ?></span>
                            <span><b class="dot amber"></b>Medios <?= h((string) $stats['medium']) ?></span>
                            <span><b class="dot green"></b>Bajos <?= h((string) $stats['low']) ?></span>
                        </div>
                    </section>

                    <section class="insight-card">
                        <div class="insight-title">
                            <span class="eyebrow">Web health</span>
                            <strong><?= h((string) $stats['down_web']) ?> caidas</strong>
                        </div>
                        <div class="bar-metric">
                            <span>Sin SSL</span>
                            <strong><?= h((string) $percent($insights['without_ssl'])) ?>%</strong>
                            <i><b style="width: <?= h((string) $percent($insights['without_ssl'])) ?>%"></b></i>
                        </div>
                        <div class="bar-metric">
                            <span>Responsive</span>
                            <strong><?= h((string) $percent($insights['responsive'])) ?>%</strong>
                            <i><b class="good" style="width: <?= h((string) $percent($insights['responsive'])) ?>%"></b></i>
                        </div>
                        <div class="bar-metric">
                            <span>WordPress</span>
                            <strong><?= h((string) $percent($insights['wordpress'])) ?>%</strong>
                            <i><b class="blue" style="width: <?= h((string) $percent($insights['wordpress'])) ?>%"></b></i>
                        </div>
                    </section>

                    <section class="insight-card">
                        <div class="insight-title">
                            <span class="eyebrow">Contacto</span>
                            <strong>Datos utiles</strong>
                        </div>
                        <div class="contact-score">
                            <span>Telefono</span><b><?= h((string) $insights['with_phone']) ?></b>
                            <span>Email</span><b><?= h((string) $insights['with_email']) ?></b>
                            <span>Instagram</span><b><?= h((string) $insights['with_instagram']) ?></b>
                        </div>
                    </section>
                </aside>
            </section>
        <?php endif; ?>
    </main>
</div>
<script src="assets/app.js"></script>
</body>
</html>
