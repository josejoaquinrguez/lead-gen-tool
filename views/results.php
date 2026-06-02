<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Extractor y Analizador de Negocios</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<main class="container">

    <section class="hero">
        <h1>Extractor y Analizador de Negocios</h1>
        <p>Busca negocios locales por código postal y nicho para detectar oportunidades de auditoría digital.</p>

        <form method="GET" action="index.php" class="search-form">
            <div>
                <label for="postal_code">Código postal</label>
                <input
                    type="text"
                    id="postal_code"
                    name="postal_code"
                    placeholder="29600"
                    value="<?= htmlspecialchars($postalCode) ?>"
                    required
                >
            </div>

            <div>
                <label for="keyword">Nicho</label>
                <input
                    type="text"
                    id="keyword"
                    name="keyword"
                    placeholder="Inmobiliarias, restaurantes..."
                    value="<?= htmlspecialchars($keyword) ?>"
                    required
                >
            </div>

            <button type="submit">Buscar negocios</button>
        </form>
    </section>

    <?php if ($error): ?>
        <div class="alert error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($postalCode !== '' && $keyword !== ''): ?>
        <section class="results-header">
            <h2>Resultados encontrados</h2>
            <p>
                Código postal:
                <strong><?= htmlspecialchars($postalCode) ?></strong>
                · Nicho:
                <strong><?= htmlspecialchars($keyword) ?></strong>
                · Total:
                <strong><?= count($results) ?></strong>
            </p>
        </section>

        <div class="filters">
            <a class="<?= $filter === 'all' ? 'active' : '' ?>"
               href="index.php?postal_code=<?= urlencode($postalCode) ?>&keyword=<?= urlencode($keyword) ?>&filter=all">
                Todos
            </a>

            <a class="<?= $filter === 'lead' ? 'active' : '' ?>"
               href="index.php?postal_code=<?= urlencode($postalCode) ?>&keyword=<?= urlencode($keyword) ?>&filter=lead">
                Leads interesantes
            </a>

            <a class="<?= $filter === 'improvable' ? 'active' : '' ?>"
               href="index.php?postal_code=<?= urlencode($postalCode) ?>&keyword=<?= urlencode($keyword) ?>&filter=improvable">
                Mejorables
            </a>

            <a class="<?= $filter === 'correct' ? 'active' : '' ?>"
               href="index.php?postal_code=<?= urlencode($postalCode) ?>&keyword=<?= urlencode($keyword) ?>&filter=correct">
                Correctos
            </a>
        </div>

        <?php if (empty($results)): ?>
            <div class="alert">
                No se han encontrado negocios para esta búsqueda.
            </div>
        <?php else: ?>
            <div class="cards">
                <?php foreach ($results as $item): ?>
                    <?php $analysis = $item['analysis']; ?>

                    <article class="business-card">
                        <div class="card-top">
                            <div>
                                <h3><?= htmlspecialchars($item['name']) ?></h3>
                                <p><?= htmlspecialchars($item['address']) ?></p>
                            </div>

                            <span class="score">
                                <?= htmlspecialchars((string) $analysis['score']) ?> pts
                            </span>
                        </div>

                        <div class="info-grid">
                            <p>
                                <strong>Teléfono:</strong>
                                <?= htmlspecialchars($item['phone'] ?: 'Sin teléfono') ?>
                            </p>

                            <p>
                                <strong>Web:</strong>
                                <?php if (!empty($item['website'])): ?>
                                    <a href="<?= htmlspecialchars($item['website']) ?>" target="_blank">
                                        Visitar web
                                    </a>
                                <?php else: ?>
                                    Sin web
                                <?php endif; ?>
                            </p>

                            <p>
                                <strong>Instagram:</strong>
                                <?php if (!empty($item['instagram'])): ?>
                                    <a href="<?= htmlspecialchars($item['instagram']) ?>" target="_blank">
                                        Ver Instagram
                                    </a>
                                <?php else: ?>
                                    Sin Instagram
                                <?php endif; ?>
                            </p>

                            <p>
                                <strong>Facebook:</strong>
                                <?php if (!empty($item['facebook'])): ?>
                                    <a href="<?= htmlspecialchars($item['facebook']) ?>" target="_blank">
                                        Ver Facebook
                                    </a>
                                <?php else: ?>
                                    Sin Facebook
                                <?php endif; ?>
                            </p>

                            <p>
                                <strong>Coordenadas:</strong>
                                <?= htmlspecialchars((string) $item['latitude']) ?>,
                                <?= htmlspecialchars((string) $item['longitude']) ?>
                            </p>

                            <p>
                                <strong>Estado:</strong>
                                <?= htmlspecialchars($analysis['level']) ?>
                            </p>
                        </div>

                        <?php if (!empty($analysis['issues'])): ?>
                            <ul class="issues">
                                <?php foreach ($analysis['issues'] as $issue): ?>
                                    <li><?= htmlspecialchars($issue) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p class="ok">No se han detectado carencias básicas.</p>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>

</main>

</body>
</html>