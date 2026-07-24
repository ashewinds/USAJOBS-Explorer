<?php
require_once __DIR__ . '/../../config/bootstrap.php';

$desiredLocations = $locationList;
$remoteCityString1 = "Anywhere in the U.S.";
$remoteCityString2 = "remote job";

$totalRecords = 0;
$totalPages = 0;

$stmt = $pdo->prepare("
    INSERT INTO jobs
    (
        control_number,
        position_id,
        title,
        organization,
        department,
        start_date,
        end_date,
        actual_close_date,
        closing_type,
        status,
        series,
        pay_plan,
        grade_low,
        grade_high,
        is_remote,
        schedule_name,
        clearance_name,
        clearance_required,
        available_locations_json,
        matched_search_locations,
        hiring_paths_json,
        hiring_public,
        hiring_veterans,
        hiring_competitive_service,
        hiring_internal_agency,
        hiring_military_spouse,
        hiring_disability,
        who_may_apply_text,
        position_opening_status,
        telework_eligible,
        historic_raw_json,
        first_seen,
        last_seen,
        first_api_to_update,
        last_api_to_update,
        last_historic_update
    )
    VALUES
    (
        :control_number,
        :position_id,
        :title,
        :organization,
        :department,
        :start_date,
        :end_date,
        :actual_close_date,
        :closing_type,
        :status,
        :series,
        :pay_plan,
        :grade_low,
        :grade_high,
        :is_remote,
        :schedule_name,
        :clearance_name,
        :clearance_required,
        :available_locations_json,
        :matched_search_locations,
        :hiring_paths_json,
        :hiring_public,
        :hiring_veterans,
        :hiring_competitive_service,
        :hiring_internal_agency,
        :hiring_military_spouse,
        :hiring_disability,
        :who_may_apply_text,
        :position_opening_status,
        :telework_eligible,
        :historic_raw_json,
        NOW(),
        NOW(),
        'historic',
        'historic',
        NOW()
    )
    ON DUPLICATE KEY UPDATE
        position_id = COALESCE(VALUES(position_id), position_id),
        title = COALESCE(VALUES(title), title),
        organization = COALESCE(VALUES(organization), organization),
        department = COALESCE(VALUES(department), department),
        start_date = COALESCE(VALUES(start_date), start_date),
        end_date = COALESCE(VALUES(end_date), end_date),
        actual_close_date = COALESCE(VALUES(actual_close_date), actual_close_date),
        closing_type = COALESCE(VALUES(closing_type), closing_type),
        status = VALUES(status),
        series = COALESCE(VALUES(series), series),
        pay_plan = COALESCE(VALUES(pay_plan), pay_plan),
        grade_low = COALESCE(VALUES(grade_low), grade_low),
        grade_high = COALESCE(VALUES(grade_high), grade_high),
        is_remote = COALESCE(VALUES(is_remote), is_remote),
        schedule_name = COALESCE(VALUES(schedule_name), schedule_name),
        clearance_name = COALESCE(VALUES(clearance_name), clearance_name),
        clearance_required = COALESCE(VALUES(clearance_required), clearance_required),
        available_locations_json = COALESCE(VALUES(available_locations_json), available_locations_json),
        matched_search_locations = COALESCE(VALUES(matched_search_locations), matched_search_locations),
        hiring_paths_json = COALESCE(VALUES(hiring_paths_json), hiring_paths_json),
        hiring_public = COALESCE(VALUES(hiring_public), hiring_public),
        hiring_veterans = COALESCE(VALUES(hiring_veterans), hiring_veterans),
        hiring_competitive_service = COALESCE(VALUES(hiring_competitive_service), hiring_competitive_service),
        hiring_internal_agency = COALESCE(VALUES(hiring_internal_agency), hiring_internal_agency),
        hiring_military_spouse = COALESCE(VALUES(hiring_military_spouse), hiring_military_spouse),
        hiring_disability = COALESCE(VALUES(hiring_disability), hiring_disability),
        who_may_apply_text = COALESCE(VALUES(who_may_apply_text), who_may_apply_text),
        position_opening_status = COALESCE(VALUES(position_opening_status), position_opening_status),
        telework_eligible = COALESCE(VALUES(telework_eligible), telework_eligible),
        historic_raw_json = VALUES(historic_raw_json),
        last_seen = NOW(),
        first_api_to_update = COALESCE(first_api_to_update, 'historic'),
        last_api_to_update = 'historic',
        last_historic_update = NOW()
");

$startRunStmt = $pdo->prepare("
    INSERT INTO import_runs
    (
        api_type,
        run_time,
        series,
        locations,
        jobs_found,
        pages,
        notes
    )
    VALUES
    (
        'historic',
        NOW(),
        :series,
        :locations,
        0,
        0,
        'started'
    )
");

$finishRunStmt = $pdo->prepare("
    UPDATE import_runs
    SET
        jobs_found = :jobs_found,
        pages = :pages,
        notes = 'Completed'
    WHERE id = :id
");

$failRunStmt = $pdo->prepare("
    UPDATE import_runs
    SET
        jobs_found = :jobs_found,
        pages = :pages,
        notes = :notes
    WHERE id = :id
");

$existingStmt = $pdo->prepare("
    SELECT matched_search_locations
    FROM jobs
    WHERE control_number = :control_number
");

$seriesString = implode(";", $seriesList);

$locationNames = [];
foreach ($desiredLocations as $location) {
    $locationNames[] = $location["city"] . ", " . $location["stateCode"];
}
$locationsString = implode("; ", $locationNames);

$startRunStmt->execute([
    ":series" => $seriesString,
    ":locations" => $locationsString
]);

$runId = (int) $pdo->lastInsertId();

//$responseSaved = false;

try {

    foreach ($seriesList as $series) {

        $historicParams = [
            "PositionSeries" => $series,
            "StartPositionOpenDate" => date("m-d-Y", strtotime("-1 year")),
            "EndPositionOpenDate" => date("m-d-Y")
        ];

        $baseUrl = "https://data.usajobs.gov/api/historicjoa";
        $url = $baseUrl . "?" . http_build_query($historicParams);

        while ($url !== null) {
            $ch = curl_init();

            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 60
            ]);

            $response = curl_exec($ch);

            if ($response === false) {
                throw new RuntimeException(
                    "Historic API cURL error: " . curl_error($ch)
                );
            }

            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200) {
                throw new RuntimeException(
                    "Historic API HTTP $httpCode: $response"
                );
            }

            /* used once to save raw response for inspecting the json
            if (!$responseSaved) {
                file_put_contents(
                    __DIR__ . "/historic_response_json_from_script.json",
                    $response
                );
                $responseSaved = true;
            }
            */

            $decoded = json_decode($response, true);

            if (!is_array($decoded)) {
                throw new RuntimeException("Historic API returned invalid JSON.");
            }

            $records = $decoded["data"] ?? [];

            foreach ($records as $record) {
                $controlNumber = isset($record["usajobsControlNumber"])
                    ? (string) $record["usajobsControlNumber"]
                    : null;

                if (!$controlNumber) {
                    continue;
                }

                $jobCategories = $record["jobcategories"] ?? $record["jobCategories"] ?? [];
                $recSeriesValues = [];

                foreach ($jobCategories as $category) {
                    if (!empty($category["series"])) {
                        $recSeriesValues[] = $category["series"];
                    }
                }

                $recSeriesValues = array_unique($recSeriesValues);
                sort($recSeriesValues);
                $seriesValuesAsString = !empty($recSeriesValues) ? implode(",", $recSeriesValues) : null;

                $recLocations = $record["positionlocations"] ?? $record["positionLocations"] ?? [];
                
                $keepRecord = false;
                $isRemote = 0;

                $existingStmt->execute([
                    ":control_number" => $controlNumber
                ]);

                // query database to see what, if any, cities are already stored for this job announcement
                $previouslySavedLocations = $existingStmt->fetchColumn();

                $matchedLocationsJson = $previouslySavedLocations !== false ? $previouslySavedLocations : null;

                // go thru each location listed on the job announcement
                foreach ($recLocations as $recLocation) {
                    $recCity = trim($recLocation["positionLocationCity"] ?? "");
                    $recState = trim($recLocation["positionLocationState"] ?? "");

                    // If "Anywhere in the U.S." or "remote job" is listed as city or state, this job will be marked remote
                    if (stripos($recCity, $remoteCityString1) !== false || stripos($recCity, $remoteCityString2) !== false) {
                        $isRemote = 1;
                        $keepRecord = true;
                        $matchedLocationsJson = mergeMatchedLocation($matchedLocationsJson, $recCity);
                    } else {
                        // if not remote, then check to see if city/state matches one of our desired locations
                        foreach ($desiredLocations as $desiredLocation) {
                            if ($recCity === $desiredLocation["city"] && ($recState === $desiredLocation["stateFull"] || $recState === $desiredLocation["stateCode"])) {
                                $keepRecord = true;
                                $locationToAdd = $recCity . ", " . $recState;
                                $matchedLocationsJson = mergeMatchedLocation($matchedLocationsJson, $locationToAdd);
                                break;
                            }
                        }
                    }
                }

                if (!$keepRecord) {
                    continue;
                }

                $locationsJson = json_encode(
                    $recLocations,
                    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
                );

                $hiringPaths = $record["hiringpaths"] ?? $record["hiringPaths"] ?? [];

                $hiringPathsJson = json_encode(
                    $hiringPaths,
                    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
                );
                
                $hiringPathValues = array_column($hiringPaths, "hiringPath");

                $hiringPublic = in_array("The public", $hiringPathValues, true) ? 1 : null;
                $hiringVeterans = in_array("Veterans", $hiringPathValues, true) ? 1 : null;
                $hiringCompetitiveService = in_array("Federal employees - Competitive service", $hiringPathValues, true) ? 1 : null;
                $hiringInternalAgency = in_array("Internal to an agency", $hiringPathValues, true) ? 1 : null;
                $hiringMilitarySpouse = in_array("Military spouses", $hiringPathValues, true) ? 1 : null;
                $hiringDisability = in_array("Individuals with disabilities", $hiringPathValues, true) ? 1 : null;

                $clearanceName = $record["securityClearance"] ?? null;

                $clearanceRequired = match (
                    strtoupper(trim((string) (
                        $record["securityClearanceRequired"] ?? ""
                    )))
                ) {
                    "Y" => 1,
                    "N" => 0,
                    default => null
                };

                $teleworkEligible = match (
                    strtoupper(trim((string)(
                        $record["teleworkEligible"] ?? ""
                    )))
                ) {
                    "Y" => 1,
                    "N" => 0,
                    default => null
                };

                if (array_key_exists("remoteIndicator", $record)) {
                    $remoteIndicator = filter_var($record["remoteIndicator"], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                    if ($remoteIndicator !== null) {
                        $isRemote = $remoteIndicator ? 1 : 0;
                    }
                } elseif (array_key_exists("RemoteIndicator", $record)) {
                    $remoteIndicator = filter_var($record["RemoteIndicator"], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                    if ($remoteIndicator !== null) {
                        $isRemote = $remoteIndicator ? 1 : 0;
                    }
                }

                $openDate = normalizeHistoricDate($record["positionOpenDate"] ?? null);
                // closeDate is scheduled end of job announcement
                $closeDate = normalizeHistoricDate($record["positionCloseDate"] ?? null);
                // expireDate set if job announcement closes early (hit limit on received applications, but also often set illogically before open date)
                $expireDate = normalizeHistoricDate($record["positionExpireDate"] ?? null);

                /* some old records use 1900-01-01 as a placeholder.  */
                if ($expireDate !== null && 
                    (str_starts_with($expireDate, "1900-01-01") || 
                    ($openDate !== null && $expireDate < $openDate) ||
                    ($closeDate !== null && $expireDate > $closeDate))
                    ) {
                        $expireDate = null;
                }

                $effectiveCloseDate = $expireDate ?? $closeDate;

                $status = "Unknown";

                if ($effectiveCloseDate !== null) {
                    // set close status to take effect at 23:59:59
                    $closeDateTime = new DateTime($effectiveCloseDate);
                    $closeDateTime->setTime(23, 59, 59);

                    $status = $closeDateTime < new DateTime() ? "Closed" : "Open";
                }

                $stmt->execute([
                    ":control_number" => $controlNumber,
                    ":position_id" => $record["announcementNumber"] ?? null,
                    ":title" => $record["positionTitle"] ?? null,
                    ":organization" => $record["hiringAgencyName"] ?? null,
                    ":department" => $record["hiringDepartmentName"] ?? null,
                    ":start_date" => $openDate,
                    ":end_date" => $closeDate,
                    ":actual_close_date" => $effectiveCloseDate,
                    ":closing_type" => $record["announcementClosingTypeDescription"] ?? null,
                    ":status" => $status,
                    ":series" => $seriesValuesAsString,
                    ":pay_plan" => $record["payScale"] ?? $record["payPlan"] ?? null,
                    ":grade_low" => $record["minimumGrade"] ?? null,
                    ":grade_high" => $record["maximumGrade"] ?? null,
                    ":is_remote" => $isRemote,
                    ":schedule_name" => $record["workSchedule"] ?? null,
                    ":clearance_name" => $clearanceName,
                    ":clearance_required" => $clearanceRequired,
                    ":available_locations_json" => $locationsJson,
                    ":matched_search_locations" => $matchedLocationsJson,
                    ":hiring_paths_json" => $hiringPathsJson,
                    ":hiring_public" => $hiringPublic,
                    ":hiring_veterans" => $hiringVeterans,
                    ":hiring_competitive_service" => $hiringCompetitiveService,
                    ":hiring_internal_agency" => $hiringInternalAgency,
                    ":hiring_military_spouse" => $hiringMilitarySpouse,
                    ":hiring_disability" => $hiringDisability,
                    ":who_may_apply_text" => $record["whoMayApply"] ?? null,
                    ":position_opening_status" => $record["positionOpeningStatus"] ?? null,
                    ":telework_eligible" => $teleworkEligible,
                    ":historic_raw_json" => json_encode($record, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                ]);

                $totalRecords++;
            }

            $totalPages++;

            /* Current Historic API responses provide paging.next. 
            *. It is often a relative URL containing a continuation token. */
            $next = $decoded["paging"]["next"] ?? null;

            if (!$next) {
                $url = null;
            } elseif (str_starts_with($next, "http")) {
                $url = $next;
            } else {
                $url = "https://data.usajobs.gov" . $next;
            }
        }
    }

    $finishRunStmt->execute([
        ":jobs_found" => $totalRecords,
        ":pages" => $totalPages,
        ":id" => $runId
    ]);

    echo "<h1>Historic import complete.</h1>";
    echo "<p>Records processed: " . $totalRecords . "</p>";
    echo "<p>Pages processed: " . $totalPages . "</p>";
    echo "<p>Time of run: " . date("Y-m-d H:i:s") . "</p>";

} catch (Throwable $error) {
    $failRunStmt->execute([
        ":jobs_found" => $totalRecords,
        ":pages" => $totalPages,
        ":notes" => "Failed: " . $error->getMessage(),
        ":id" => $runId
    ]);

    throw $error;
}


/*
 -------------- LOG IMPORT RUN -------------- 

$runStmt->execute([
    ":series" => $series,
    ":locations" => implode(
        "; ", 
        array_map(
            fn($loc) => $loc["city"] . ", " . $loc["stateFull"],
            $locations
        )
    ),
    ":jobs_found" => $totalRecords,
    ":pages" => $totalPages,
    ":notes" => "Current Search API import"
]);
*/

?>