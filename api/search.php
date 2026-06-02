<?php

require_once __DIR__ . "/../services/places.php";

$postal = $_POST['postal_code'];
$keyword = $_POST['keyword'];

$results = getPlaces($keyword, $postal);

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Resultados</title>
</head>
<body>

<h1>Negocios encontrados</h1>

<p>CP: <?= $postal ?></p>
<p>Nicho: <?= $keyword ?></p>

<table border="1" cellpadding="10">
    <tr>
        <th>Nombre</th>
        <th>Dirección</th>
        <th>Web</th>
    </tr>

    <?php foreach ($results as $item): ?>
        <tr>
            <td><?= $item['name'] ?></td>
            <td><?= $item['address'] ?></td>
            <td>
                <?php if ($item['website']): ?>
                    <a href="<?= $item['website'] ?>" target="_blank">Visitar</a>
                <?php else: ?>
                    Sin web
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>

</table>

<br>
<a href="../index.php">← Volver</a>

</body>
</html>