<?php


/* ------------ HELPER FUNCTION ----------- */

function mergeMatchedLocation(?string $existingJson, string $city): string 
{
    $locations = [];

    if ($existingJson) {
        $decoded = json_decode($existingJson, true);

        if (is_array($decoded)) {
            $locations = $decoded;
        }
    }

    if (!in_array($city, $locations, true)) {
        $locations[] = $city;
    }

    return json_encode(
        array_values($locations),
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    );
}

function normalizeHistoricDate(?string $value): ?string 
{
    if ($value === null || trim($value) === "") {
        return null;
    }

    $timestamp = strtotime($value);

    if ($timestamp === false) {
        return null;
    }

    return date("Y-m-d H:i:s", $timestamp);
}

?>