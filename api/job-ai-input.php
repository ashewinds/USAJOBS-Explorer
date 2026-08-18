<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Content-Type: application/json");

require_once "../../config/database_config.php";
require_once __DIR__ . "/helpers/job-ai-input-builder.php";

$pdo = $connectDatabase();

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id) {
    http_response_code(400);

    echo json_encode([
        "error" => "Valid job id is required."
    ]);

    exit;
}

try {
    $aiInput = buildJobAIInput($pdo, $id);
    echo json_encode($aiInput);
} catch (RuntimeException $e) {

    $statusCode = $e->getCode();

    if ($statusCode < 400 || $statusCode > 599) {
        $statusCode = 500;
    }

    http_response_code($statusCode);

    echo json_encode([
        "error" => $e->getMessage()
    ]);
}