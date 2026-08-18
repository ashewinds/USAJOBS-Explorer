<?php

require_once __DIR__ . "/../config/bootstrap.php";

$pdo = $connectDatabase();

$openJobsStmt = $pdo->query("
    SELECT control_number
    FROM jobs
    WHERE status = 'Open'
");

$openControlNumbers = $openJobsStmt->fetchAll(PDO::FETCH_COLUMN);

$updateStatusStmt = $pdo->prepare("
    UPDATE jobs
    SET
        status = COALESCE(:status, status),
        position_opening_status = COALESCE(:position_opening_status, position_opening_status),
        actual_close_date = COALESCE(:actual_close_date, actual_close_date)
    WHERE control_number = :control_number
");

try {
    foreach ($openControlNumbers as $openControlNumber) {

        $historicParams = [
            "USAJOBSControlNumbers" => $openControlNumber
        ];

        $baseUrl = "https://data.usajobs.gov/api/historicjoa";
        $url = $baseUrl . "?" . http_build_query($historicParams);

        $maxAttempts = 3;
            
            $attempt = 0;
            $response = false;
            $curlError = "";

            while ($attempt < $maxAttempts && $response === false) {
                $attempt++;

                $ch = curl_init();

                curl_setopt_array($ch, [
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 30
                ]);

                $response = curl_exec($ch);

                if ($response === false) {
                    $curlError = curl_error($ch);
                    curl_close($ch);

                    if ($attempt < $maxAttempts){
                        sleep(2);
                    }
                }
            }

            if ($response === false) {
                throw new RuntimeException(
                    "Historic API cURL error: " . $curlError
                );
            }

            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200) {
                throw new RuntimeException(
                    "Historic API HTTP $httpCode: $response"
                );
            }

            $decoded = json_decode($response, true);

            if (!is_array($decoded)) {
                throw new RuntimeException("Historic API returned invalid JSON.");
            }

            $record = $decoded["data"] ?? [];
            $record = $record[0] ?? [];

            $positionOpeningStatus = trim($record["positionOpeningStatus"] ?? "");

            if ($positionOpeningStatus !== "Accepting applications"
                && $positionOpeningStatus !== "") {
                    $openDate = normalizeHistoricDate($record["positionOpenDate"] ?? null);
                    $closeDate = normalizeHistoricDate($record["positionCloseDate"] ?? null);
                    $expireDate = normalizeHistoricDate($record["positionExpireDate"] ?? null);

                    $actualCloseDate = null;
                    $status = "Closed";

                    if ($expireDate !== null
                        && ($openDate !== null && $expireDate >= $openDate)
                        && ($closeDate !== null && $expireDate <= $closeDate)) {
                            $actualCloseDate = $expireDate;
                    }

                    $updateStatusStmt->execute([
                        ":control_number" => $openControlNumber,
                        ":position_opening_status" => $positionOpeningStatus,
                        ":status" => $status,
                        ":actual_close_date" => $actualCloseDate
                    ]);
            }
    }
} catch (Throwable $error) {
    throw $error;
}