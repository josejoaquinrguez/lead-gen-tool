<?php

function getPlaces($keyword, $postal) {

    // Mapa básico CP → coordenadas (puedes ampliarlo luego)
    $cpMap = [
        "29600" => ["lat" => 36.510, "lon" => -4.885] // Marbella
    ];

    // Si el CP no existe, devolvemos vacío
    if (!isset($cpMap[$postal])) {
        return [];
    }

    $lat = $cpMap[$postal]["lat"];
    $lon = $cpMap[$postal]["lon"];

    // Query Overpass (negocios tipo "shop")
    $query = '
    [out:json];

    (
      node(around:3000,' . $lat . ',' . $lon . ')["shop"];
      way(around:3000,' . $lat . ',' . $lon . ')["shop"];
    );

    out center;
    ';

    $url = "https://overpass-api.de/api/interpreter?data=" . urlencode($query);

    $response = @file_get_contents($url);

    if (!$response) {
        return [];
    }

    $data = json_decode($response, true);

    $results = [];

    if (!isset($data["elements"])) {
        return [];
    }

    foreach ($data["elements"] as $el) {

        $results[] = [
            "name" => $el["tags"]["name"] ?? "Sin nombre",
            "address" => $postal,
            "website" => $el["tags"]["website"] ?? ""
        ];
    }

    return $results;
}