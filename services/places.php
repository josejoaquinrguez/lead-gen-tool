<?php

function getPlaces($keyword, $postal) {

    $query = '
    [out:json];

    area["postal_code"="' . $postal . '"]->.searchArea;

    (
      node["shop"](area.searchArea);
      way["shop"](area.searchArea);
    );

    out center;
    ';

    $url = "https://overpass-api.de/api/interpreter?data=" . urlencode($query);

    $response = file_get_contents($url);

    if (!$response) {
        return [];
    }

    $data = json_decode($response, true);

    $results = [];

    foreach ($data["elements"] as $el) {

        $results[] = [
            "name" => $el["tags"]["name"] ?? "Sin nombre",
            "address" => $postal,
            "website" => $el["tags"]["website"] ?? ""
        ];
    }

    return $results;
}