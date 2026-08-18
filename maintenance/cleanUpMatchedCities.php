<?php
require_once __DIR__ . '/../../../config/bootstrap.php';

$desiredLocations = $locationList;
$remoteCityString1 = "Anywhere in the U.S.";
$remoteCityString2 = "remote job";

$pdo = $connectDatabase();

$allJobsStmt = $pdo->query("
    SELECT control_number, available_locations_json
    FROM jobs
    WHERE matched_search_locations IS NULL
");

$updateMatchedCitiesStmt = $pdo->prepare("
    UPDATE jobs
    SET matched_search_locations = :matched_search_locations
    WHERE control_number = :control_number
");

$allJobs = $allJobsStmt->fetchAll(PDO::FETCH_ASSOC);

$updatedJobs = 0;

foreach ($allJobs as $job) {
    $exactCityMatches = [];
    $jobLocArray = json_decode($job["available_locations_json"], true);
    
    if (!is_array($jobLocArray)) {
        continue;
    }
    $matchFound = false;
    foreach ($desiredLocations as $desiredLoc) {
        $fullDesiredLoc = $desiredLoc["city"] . ", " . $desiredLoc["stateFull"];
        $abbrDesiredLoc = $desiredLoc["city"] . ", " . $desiredLoc["stateCode"];
        foreach ($jobLocArray as $loc) {
            $fullJobLoc = "";
            // get position location if it came from current api
            if (array_key_exists("LocationName", $loc)) {
                $fullJobLoc = $loc["LocationName"];
            } elseif (array_key_exists("positionLocationCity", $loc)
                && array_key_exists("positionLocationState", $loc)) {
                    $fullJobLoc= $loc["positionLocationCity"] . ", " . $loc["positionLocationState"];
            }
            if ($fullDesiredLoc === $fullJobLoc || $abbrDesiredLoc === $fullJobLoc) {
                $exactCityMatches[] = $fullDesiredLoc;
                $matchFound = true;
                break;
            }
        }    
    }
    if ($matchFound === true) {
        $exactCityMatchesJson = json_encode(array_values($exactCityMatches), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $updateMatchedCitiesStmt->execute([
            ":control_number" => $job["control_number"],
            ":matched_search_locations" => $exactCityMatchesJson
        ]);
        $updatedJobs++;
    }
}

echo "Number of Updated Jobs: " . $updatedJobs;