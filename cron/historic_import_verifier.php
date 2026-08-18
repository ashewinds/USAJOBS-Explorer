<?php

require_once __DIR__ . '/../config/bootstrap.php';

$pdo = $connectDatabase();

$logFile = __DIR__ . "/../logs/historic_import_verifier.log";
$logDir = dirname($logFile);

if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

$timestamp = date("Y-m-d H:i:s");

$stmt = $pdo->query("
    SELECT notes
    FROM import_runs
    WHERE api_type = 'historic'
        AND DATE(run_time) = CURDATE()
    ORDER BY id DESC
    LIMIT 1
");

$lastRunNote = $stmt->fetchColumn();

if ($lastRunNote === "Completed") {
    file_put_contents(
        $logFile,
        "[$timestamp] Historic import already completed today. No action needed.\n",
        FILE_APPEND
    );
} else {
    $statusMessage = $lastRunNote !== false ? $lastRunNote : "No run found";
    file_put_contents(
        $logFile,
        "[$timestamp] Historic import not completed today. [Status: $statusMessage] Starting recovery run.\n",
        FILE_APPEND
    );  
    require_once __DIR__ . "/historic_import.php";
}