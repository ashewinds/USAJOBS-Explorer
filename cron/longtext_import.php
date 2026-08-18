<?php

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . "/../config/bootstrap.php";

echo '<h1>USAJOBS Longtext Import';

$pdo = $connectDatabase();

$getLongtextStmt = $pdo->query("
    SELECT id, position_id, control_number
    FROM jobs
    WHERE longtext_checked_at IS NULL
");

$jobs = $getLongtextStmt->fetchALL();

$startedImportStmt = $pdo->prepare("
    INSERT INTO import_runs
    (
        api_type,
        notes
    )
    VALUES
    (
        'longtext',
        'Started'
    )
");

$startedImportStmt->execute();

$runId = $pdo->lastInsertId();

$pdo = null;
$jobsWithAppLimit = 0;

foreach ($jobs as $job) {
    // make api call w the announcement number
    $params = [
        "AnnouncementNumbers" => $job["position_id"]
    ];
    $baseUrl = "https://data.usajobs.gov/api/historicjoa/announcementtext";
    $url = $baseUrl . "?" . http_build_query($params);

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_USERAGENT => "Mozilla/5.0"
    ]);

    $response = curl_exec($ch);

    if ($response === false) {
        $curlError = curl_error($ch);
        echo "<p>Failed: {$job['position_id']} - $curlError</p>";
        curl_close($ch);
        continue;
    }

    $decoded = json_decode($response, true);

    // need to get otherinformation
    $otherInformation = $decoded["data"][0]["otherInformation"] ?? "";
    $plainText = strip_tags($otherInformation);
    $appLimit = null;

    // search otherinformation for limit text
    if (preg_match(
        '/close when we have received\s+(\d+)\s+applications/i',
        $plainText,
        $matches
    )) {
        $appLimit = (int) $matches[1];
    } else {
        $jobPageUrl = "https://www.usajobs.gov/job/" . $job["control_number"];

        curl_setopt($ch, CURLOPT_URL, $jobPageUrl);

        $html = curl_exec($ch);

        if ($html === false) {
            $curlError = curl_error($ch);
            echo "<p>HTML failed: {$job['position_id']} - $curlError</p>";
            curl_close($ch);
            continue;
        }

        $plainText = preg_replace('/\s+/', ' ', strip_tags($html));

        if (preg_match(
            '/job will close when we have received\s+(\d+)\s+applications/i',
            $plainText,
            $matches
        )) {
            $appLimit = (int) $matches[1];
        }
    } 

    if ($appLimit !== null) {
        $jobsWithAppLimit++;
    }

    $pdo = $connectDatabase();

    $setLongtextStmt = $pdo->prepare("
        UPDATE jobs
        SET applicant_limit = :applicant_limit,
            longtext_checked_at = NOW()
        WHERE id = :id
    ");

    // run stmt to save new info to job in database
    $setLongtextStmt->execute([
        ":id" => $job["id"],
        ":applicant_limit" => $appLimit
        ]);

    $pdo = null;
    
    curl_close($ch);
}

$pdo = $connectDatabase();

// update import_runs table
$updateImportStmt = $pdo->prepare("
    UPDATE import_runs
    SET
        jobs_found = :jobs_found,
        notes = :notes
    WHERE id = :runId
");

$updateImportStmt->execute([
    ":jobs_found" => count($jobs),
    ":notes" => "Number of new jobs with applicant limits: " . $jobsWithAppLimit,
    ":runId" => $runId
]);

$pdo = null;