<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Content-Type: application/json");

require_once "../../config/database_config.php";

$pdo = $connectDatabase();

$stmt = $pdo->prepare("
    SELECT
        id,
        position_id,
        control_number,
        title,
        organization,
        department,
        start_date,
        end_date,
        applicant_limit,
        status,
        series,
        is_remote,
        pay_plan,
        grade_low,
        grade_high,
        matched_search_locations,
        position_uri,
        position_opening_status,
        actual_close_date,
        CASE
            WHEN raw_json IS NOT NULL AND raw_json <> '' THEN 1
            ELSE 0
        END AS has_analyzable_data
    FROM jobs
    ORDER BY id DESC
");

$stmt->execute();

$jobs = $stmt->fetchAll();

foreach ($jobs as &$job) {
    $job["matched_search_locations"] = json_decode($job["matched_search_locations"] ?? "[]", true);
    $job["is_remote"] = (bool) $job["is_remote"];
    $job["has_analyzable_data"] = (bool) $job["has_analyzable_data"];
}

unset($job);

echo json_encode($jobs);