<?php 

header("Access-Control-Allow-Origin: http://localhost:5173");
header("Content-Type: application/json");

require_once "../../config/database_config.php";
require_once __DIR__ . "/prompts/job-analysis-prompt.php";
require_once __DIR__ . "/helpers/job-ai-input-builder.php";
require_once "../../config/openai_config.php";

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

    $prompt = buildJobAnalysisPrompt($aiInput);

    $requestData = [
        "model" => "gpt-5.6-luna",
        "input" => $prompt,
        "store" => false,

        "text" => [
            "format" => [
                "type" => "json_schema",
                "name" => "job_analysis",
                "strict" => true,
                "schema" => [
                    "type" => "object",
                    "properties" => [
                        "summary" => [
                            "type" => "string"
                        ],
                        "keyDuties" => [
                            "type" => "array",
                            "items" => [
                                "type" => "string"
                            ]
                        ],
                        "specializedExperience" => [
                            "type" => "array",
                            "items" => [
                                "type" => "string"
                            ]
                        ],
                        "hiringEligibility" => [
                            "type" => "string"
                        ],
                        "education" => [
                            "type" => "string"
                        ],
                        "clearance" => [
                            "type" => ["string", "null"]
                        ],
                        "importantNotes" => [
                            "type" => "array",
                            "items" => [
                                "type" => "string"
                            ]
                        ]
                    ],
                    "required" => [
                        "summary",
                        "keyDuties",
                        "specializedExperience",
                        "hiringEligibility",
                        "education",
                        "clearance",
                        "importantNotes"
                    ],
                    "additionalProperties" => false
                ]
            ]
        ]
    ];

    $ch = curl_init("https://api.openai.com/v1/responses");

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer " . $openaiApiKey,
            "Content-Type: application/json"
        ],
        CURLOPT_POSTFIELDS => json_encode($requestData)
    ]);

    $response = curl_exec($ch);

    if ($response === false) {
        throw new RuntimeException(
            "OpenAI request failed: " . curl_error($ch),
            500
        );
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    if ($httpCode < 200 || $httpCode >= 300) {
        http_response_code($httpCode);

        echo $response;
        exit;
    }

   $responseData = json_decode($response, true);

   $outputText = null;

   foreach ($responseData["output"] ?? [] as $output) {
        if ($output["type"] !== "message") {
            continue;
        }

        foreach ($output["content"] ?? [] as $content) {
            if ($content["type"] === "output_text") {
                $outputText = $content["text"];
                break 2;
            }
        }
   }

    if ($outputText === null) {
        throw new RuntimeException(
            "OpenAI response did not contain output text.",
            500
        );
    }

   $analysis = json_decode($outputText, true);

    if (!is_array($analysis)) {
        throw new RuntimeException(
            "OpenAI analysis could not be decoded.",
            500
        );
    }

    echo json_encode($analysis);

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