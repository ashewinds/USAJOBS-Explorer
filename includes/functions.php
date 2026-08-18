<?php
/* ------------ HELPER FUNCTION ----------- */

function mergeMatchedLocation(?string $existingJson, string $cityAndState): string 
{
    $locations = [];

    if ($existingJson) {
        $decoded = json_decode($existingJson, true);

        if (is_array($decoded)) {
            $locations = $decoded;
        }
    }

    if (!in_array($cityAndState, $locations, true)) {
        $locations[] = $cityAndState;
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