<?php

require_once __DIR__ . '/../config/bootstrap.php';

$series = implode(';', $seriesList);

$locations = $locationList;

/* ------------ PREPARE INSERT ------------ */

$jobStmt = $pdo->prepare("
    INSERT INTO jobs
    (
        control_number,
        position_id,
        title,
        organization,
        department,
        start_date,
        end_date,
        position_uri,
        raw_json,
        status,
        available_locations_json,
        matched_search_locations,
        first_seen,
        last_seen,
        series,
        grade,
        hiring_paths_json,
        hiring_public,
        hiring_veterans,
        hiring_internal_agency,
        hiring_competitive_service,
        hiring_military_spouse,
        hiring_disability,
        pay_plan,
        grade_low,
        grade_high,
        schedule_code,
        schedule_name,
        clearance_name,
        clearance_required,
        is_remote,
        telework_eligible,
        first_api_to_update,
        last_api_to_update,
        last_current_update
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
        :position_uri,
        :raw_json,
        'Open',
        :available_locations_json,
        :matched_search_locations,
        NOW(),
        NOW(),
        :series,
        :grade,
        :hiring_paths_json,
        :hiring_public,
        :hiring_veterans,
        :hiring_internal_agency,
        :hiring_competitive_service,
        :hiring_military_spouse,
        :hiring_disability,
        :pay_plan,
        :grade_low,
        :grade_high,
        :schedule_code,
        :schedule_name,
        :clearance_name,
        :clearance_required,
        :is_remote,
        :telework_eligible,
        'current',
        'current',
        NOW()
    )
    ON DUPLICATE KEY UPDATE
        position_id = VALUES(position_id),
        title = VALUES(title),
        organization = VALUES(organization),
        department = VALUES(department),
        start_date = VALUES(start_date),
        end_date = VALUES(end_date),
        position_uri = VALUES(position_uri),
        raw_json = VALUES(raw_json),
        status = 'Open',
        available_locations_json = VALUES(available_locations_json),
        matched_search_locations = VALUES(matched_search_locations),
        last_seen = NOW(),
        fetched_at = CURRENT_TIMESTAMP,
        series = VALUES(series),
        grade = VALUES(grade),
        hiring_paths_json = VALUES(hiring_paths_json),
        hiring_public = VALUES(hiring_public),
        hiring_veterans = VALUES(hiring_veterans),
        hiring_internal_agency = VALUES(hiring_internal_agency),
        hiring_competitive_service = VALUES(hiring_competitive_service),
        hiring_military_spouse = VALUES(hiring_military_spouse),
        hiring_disability = VALUES(hiring_disability),
        pay_plan = VALUES(pay_plan),
        grade_low = VALUES(grade_low),
        grade_high = VALUES(grade_high),
        schedule_code = VALUES(schedule_code),
        schedule_name = VALUES(schedule_name),
        clearance_name = VALUES(clearance_name),
        clearance_required = VALUES(clearance_required),
        is_remote = VALUES(is_remote),
        telework_eligible = VALUES(telework_eligible),
        first_api_to_update = COALESCE(first_api_to_update, 'current'),
        last_api_to_update = 'current',
        last_current_update = NOW()
");

$runStmt = $pdo->prepare("
    INSERT INTO import_runs
    (
        api_type,
        series,
        locations,
        radius,
        jobs_found,
        pages,
        notes
    )
    VALUES
    (
        'current',
        :series,
        :locations,
        :radius,
        :jobs_found,
        :pages,
        :notes
    )
");

$existingStmt = $pdo->prepare("
    SELECT matched_search_locations
    FROM jobs
    WHERE control_number = :control_number
");

/* ------------- IMPORT ---------------- */

echo "<h1>USAJOBS Current Import</h1>";
echo "<p><strong>Run Time:</strong> " . date("Y-m-d H:i:s") . "</p>";

$totalPages = 0;
$uniqueJobs = [];

$withinRadiusOf = [];

// for each location in list of desired locations
foreach ($locations as $location) {

    $city = $location["city"];
    $stateCode = $location["stateCode"];
    $stateFull = $location["stateFull"];
    $radius = $location["radius"];

    echo "<h2>Searching {$city}, {$stateCode} within {$radius} miles</h2>";

    $page = 1;
    $numberOfPages = 1;

    do {
        $params = [
            "JobCategoryCode" => $series,
            "LocationName" => $city . ", " . $stateFull,
            "Radius" => $radius,
            "ResultsPerPage" => 500,
            "Page" => $page,
            "Fields" => "Full",
            "PositionScheduleTypeCode" => "1;2"
        ];

        $url = "https://data.usajobs.gov/api/search?" . http_build_query($params);

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "Host: data.usajobs.gov",
                "User-Agent: $email",
                "Authorization-Key: $apiKey"
            ]
        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            echo "<p>cURL error for {$city}: " . htmlspecialchars(curl_error($ch)) . "</p>";
            curl_close($ch);
            break;
        }

        curl_close($ch);

        $data = json_decode($response, true);

        if (!isset($data["SearchResult"])) {
            echo "<p>No SearchResult returned for {$city}, page {$page}</p>";
            echo "<pre>" . htmlspecialchars($response) . "</pre>";
            break;
        }

        $searchResult = $data["SearchResult"];
        $items = $searchResult["SearchResultItems"] ?? [];
        $numberOfPages = $searchResult["UserArea"]["NumberOfPages"] ?? 1;

        echo "<p>Page {$page} of {$numberOfPages}: " . count($items) . " jobs</p>";

        foreach ($items as $item) {
            $job = $item["MatchedObjectDescriptor"] ?? null;

            if (!$job) {
                continue;
            }

            $controlNumber = $item["MatchedObjectId"] ?? null;
            $positionId = $job["PositionID"] ?? null;

            if (!$controlNumber) {
                continue;
            }

            // Actual duty locations returned by USAJOBS
            $availableLocations = $job["PositionLocation"] ?? [];

            $availableLocationsJson = json_encode(
                $availableLocations,
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            );

            // Look up any search locations already stored for this job
            $existingStmt->execute([
                ":control_number" => $controlNumber
            ]);

            $existingMatchedJson = $existingStmt->fetchColumn();

            // Add the current search city without removing previous matches
            $matchedSearchLocationsJson = mergeMatchedLocation(
                $existingMatchedJson ?: null,
                $city
            );

            $uniqueJobs[$controlNumber] = true;

            $seriesValue = $job["JobCategory"][0]["Code"] ?? null;

            // second comparison should catch NT jobs
            //$gradeData = $job["JobGrade"] ?? $job["JobGrade"][0]["Code"] ?? [];
            $gradeData = $job["JobGrade"] ?? [];
            $gradeValue = !empty($gradeData)
                ? json_encode($gradeData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                : null;

            $hiringPaths = $job["UserArea"]["Details"]["HiringPath"] ?? [];

            if (!is_array($hiringPaths)) {
                $hiringPaths = [];
            }

            $hiringPathsJson = json_encode(
                $hiringPaths,
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            );

            $hiringPathCodes = [];

            foreach ($hiringPaths as $path) {
                if (is_array($path)) {
                    $code = $path["Code"] ?? $path["Name"] ?? null;
                } else {
                    $code = $path;
                }
                if ($code !== null) {
                    $hiringPathCodes[] = strtolower((string) $code);
                }
            }

            $hiringPublic = in_array("public", $hiringPathCodes, true) ? 1 : 0;
            $hiringVeterans = in_array("vet", $hiringPathCodes, true) ? 1 : 0;
            $hiringInternalAgency = in_array("fed-internal-search", $hiringPathCodes, true) ? 1 : 0;
            $hiringCompetitiveService = in_array("fed-competitive", $hiringPathCodes, true) ? 1 : 0;
            $hiringMilitarySpouse = in_array("mspouse", $hiringPathCodes, true) ? 1 : 0;
            $hiringDisability = in_array("disability", $hiringPathCodes, true) ? 1 : 0;

            $payPlan = $job["JobGrade"][0]["Code"] ?? null;

            $gradeLow = $job["UserArea"]["Details"]["LowGrade"] ?? null;
            $gradeHigh = $job["UserArea"]["Details"]["HighGrade"] ?? null;

            $scheduleCode = $job["PositionSchedule"][0]["Code"] ?? null;
            $scheduleName = $job["PositionSchedule"][0]["Name"] ?? null;

            $details = $job["UserArea"]["Details"] ?? [];

            $clearanceName = $details["SecurityClearance"] ?? null;

            $clearanceRequired = null;

            if ($clearanceName !== null) {
                $clearanceRequired = strtolower(trim($clearanceName)) === "not required" ? 0 : 1; 
            }

            $isRemote = ($details["RemoteIndicator"] ?? false) ? 1 : 0;
            
            $teleworkEligible = ($details["TeleworkEligible"] ?? false) ? 1 : 0;

            // Now insert or update the job
            $jobStmt->execute([
                ":control_number" => $controlNumber,
                ":position_id" => $positionId,
                ":title" => $job["PositionTitle"] ?? null,
                ":organization" => $job["OrganizationName"] ?? null,
                ":department" => $job["DepartmentName"] ?? null,
                ":start_date" => $job["PositionStartDate"] ?? null,
                ":end_date" => $job["PositionEndDate"] ?? null,
                ":position_uri" => $job["PositionURI"] ?? null,
                ":raw_json" => json_encode($item, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                ":available_locations_json" => $availableLocationsJson,
                ":matched_search_locations" => $matchedSearchLocationsJson,
                ":series" => $seriesValue,
                ":grade" => $gradeValue,
                ":hiring_paths_json" => $hiringPathsJson,
                ":hiring_public" => $hiringPublic,
                ":hiring_veterans" => $hiringVeterans,
                ":hiring_internal_agency" => $hiringInternalAgency,
                ":hiring_competitive_service" => $hiringCompetitiveService,
                ":hiring_military_spouse" => $hiringMilitarySpouse,
                ":hiring_disability" => $hiringDisability,
                ":pay_plan" => $payPlan,
                ":grade_low" => $gradeLow,
                ":grade_high" => $gradeHigh,
                ":schedule_code" => $scheduleCode,
                ":schedule_name" => $scheduleName,
                ":clearance_name" => $clearanceName,
                ":clearance_required" => $clearanceRequired,
                ":is_remote" => $isRemote,
                ":telework_eligible" => $teleworkEligible
            ]);
        }

        $totalPages++;
        $page++;

    } while ($page <= $numberOfPages);
}

/* -------------- LOG IMPORT RUN -------------- */

$runStmt->execute([
    ":series" => $series,
    ":locations" => implode(
        "; ", 
        array_map(
            fn($loc) => $loc["city"] . ", " . $loc["stateFull"],
            $locations
        )
    ),
    ":radius" => $radius,
    ":jobs_found" => count($uniqueJobs),
    ":pages" => $totalPages,
    ":notes" => "Current Search API import"
]);

echo "<h2>Import complete.</h2>";
echo "<p><strong>Unique jobs saved/updated:</strong> " . count($uniqueJobs) . "</p>";
echo "<p><strong>Total API pages read:</strong> {$totalPages}</p>";