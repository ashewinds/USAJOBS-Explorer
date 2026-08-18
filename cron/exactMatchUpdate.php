<?php
require_once __DIR__ . '/../config/bootstrap.php';

$desiredLocations = $locationList;
$remoteCityString1 = "Anywhere in the U.S.";
$remoteCityString2 = "remote job";

$pdo = $connectDatabase();

$allJobsStmt = $pdo->query("
    SELECT control_number, available_locations_json
    FROM jobs
    WHERE exact_city_match = 0
");

$setExactMatchStmt = $pdo->prepare("
    UPDATE jobs
    SET exact_city_match = 1
    WHERE control_number = :control_number
");

$allJobs = $allJobsStmt->fetchAll(PDO::FETCH_ASSOC);

$exactCityMatchJobs = 0;

foreach ($allJobs as $job) {
    $exactCityMatch = 0;
    $jobLocArray = json_decode($job["available_locations_json"], true);
    
    if (!is_array($jobLocArray)) {
        continue;
    }

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
                $exactCityMatch = 1;
                $exactCityMatchJobs++;
                break 2;
            }
        }    
    }
    if ($exactCityMatch === 1) {
        $setExactMatchStmt->execute([
            ":control_number" => $job["control_number"]
        ]);
    }
}

echo "Number of Exact City Match Jobs: " . $exactCityMatchJobs;