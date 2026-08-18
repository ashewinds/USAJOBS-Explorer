<?php
// Extract and normalize USAJOBS data into the structure used as AI model input.
function buildJobAIInput(PDO $pdo, int $id): array
{
    $stmt = $pdo->prepare("
        SELECT
            id,
            title,
            organization,
            series,
            pay_plan,
            grade_low,
            grade_high,
            is_remote,
            applicant_limit,
            raw_json,
            hiring_paths_json
        FROM jobs
        WHERE id = :id
        LIMIT 1
    ");

    $stmt->execute([
        "id" => $id
    ]);

    $job = $stmt->fetch();

    if (!$job) {
        throw new RuntimeException("Job not found.", 404);
    }

    if (empty($job["raw_json"])) {
        throw new RuntimeException(
            "This job does not contain enough current USAJOBS data for AI analysis.",
            422
        );
    }

    $rawJob = json_decode($job["raw_json"], true);

    if (!is_array($rawJob)) {
        throw new RuntimeException(
            "Stored USAJOBS data could not be decoded.",
            500
        );
    }

    $descriptor = $rawJob["MatchedObjectDescriptor"] ?? [];
    $details = $descriptor["UserArea"]["Details"] ?? [];

    $hiringPathsData = json_decode(
        $job["hiring_paths_json"] ?? [],
        true
    );

    $hiringPaths = [];

    foreach ($hiringPathsData as $path) {
        if (!empty($path["hiringPath"])) {
            $hiringPaths[] = $path["hiringPath"];
        }
    }

    $jobSummary = $details["JobSummary"] ?? "";
    $qualificationSummary = $descriptor["QualificationSummary"] ?? "";
    $requirements = $details["Requirements"] ?? "";
    $education = $details["Education"] ?? "";
    $keyRequirements = $details["KeyRequirements"] ?? [];
    $securityClearance = $details["SecurityClearance"] ?? null;

    $majorDuties = $details["MajorDuties"] ?? [];
    $duties = is_array($majorDuties) ? implode("\n\n", $majorDuties) : (string) $majorDuties;

    return [
        "title" => $job["title"],
        "organization" => $job["organization"],
        "series" => $job["series"],
        "payPlan" => $job["pay_plan"],
        "gradeLow" => $job["grade_low"] !== null ? (int) $job["grade_low"] : null,
        "gradeHigh" => $job["grade_high"] !== null ? (int) $job["grade_high"] : null,
        "isRemote" => (bool) $job["is_remote"],
        "applicantLimit" => $job["applicant_limit"] !== null ? (int) $job["applicant_limit"] : null,
        "hiringPaths" => $hiringPaths,

        "jobSummary" => $jobSummary,
        "duties" => $duties,
        "qualificationSummary" => $qualificationSummary,
        "requirements" => $requirements,
        "education" => $education,
        "clearance" => $securityClearance,
        "keyRequirements" => $keyRequirements
    ];
}