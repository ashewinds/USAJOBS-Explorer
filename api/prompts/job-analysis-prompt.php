<?php

function buildJobAnalysisPrompt(array $job): string{

    $keyRequirements = !empty($job["keyRequirements"])
        ? implode("\n- ", $job["keyRequirements"])
        : "None listed";
    
    if ($keyRequirements !== "None listed") {
        $keyRequirements = "- " . $keyRequirements;
    }

    $hiringPaths = !empty($job["hiringPaths"])
        ? implode(", ", $job["hiringPaths"])
        : "None listed";

    $isRemote = $job["isRemote"] ? "Yes" : "No";

    $applicantLimit = $job["applicantLimit"] ?? "None listed";

    $clearance = $job["clearance"] ?? "None listed";

    $gradeLow = $job["gradeLow"] ?? "Not listed";
    $gradeHigh = $job["gradeHigh"] ?? "Not listed";

    return <<<PROMPT
ROLE
You are a federal job announcement analyst.

OBJECTIVE
Analyze the provided USAJOBS announcement data and explain what the job
actually is, what an applicant must demonstrate to qualify, and which
details are most important to understand before applying.

Prioritize useful interpretation over simply restating the announcement.

ANALYSIS RULES
- Use only the information provided in JOB DATA.
- Do not invent or infer requirements that are not stated.
- Preserve important distinctions between eligibility, qualifications,
  conditions of employment, and job duties.
- Preserve explicit AND/OR relationships in qualification requirements.
- Do not treat general application instructions as job duties.
- Ignore reasonable accommodation instructions, EEO language,
  assessment instructions, and other administrative boilerplate unless
  they directly affect eligibility or qualifications.
- Treat Qualification Summary as the primary source for specialized
  experience requirements.
- Clearly identify when education may substitute for experience and when
  it may not.
- Do not interpret a required security clearance as meaning that an
  applicant must already possess that clearance unless the announcement
  explicitly says so.
- Keep the analysis concise and practical.
- For Key Duties, preserve the substantive duties and technical specificity
  of the announcement. Remove boilerplate and redundant wording, but do not
  generalize away important systems, technologies, responsibilities, or
  operational context.
- Key Duties should closely reflect the actual listed duties. Prefer concise
  paraphrases of each substantive duty over broad thematic summaries.
- Distinguish mandatory qualification criteria from examples,
  preferences, and general descriptive language.
- When qualification criteria contain AND or OR logic, explain that logic
  clearly and preserve which requirements must be satisfied together.
- If multiple grade levels are present, keep grade-specific qualification
  requirements separate.
- Do not list ordinary job metadata, such as location or remote status,
  as a requirement unless it is actually a condition of employment.
- In Important Notes, prioritize details that could materially affect an
  applicant's decision, qualification, or application strategy.
- Avoid repeating the same information in multiple output sections.

- In Requirements, include only conditions that could materially affect
  whether an applicant may apply, qualify, accept, or perform the job.
- Omit routine federal employment boilerplate such as standard identity
  verification, direct deposit, generic probationary-period language,
  and Selective Service language unless it is unusually relevant to
  this announcement.
- Do not repeat qualification requirements in Requirements if they are
  already explained under Specialized Experience.
- Requirements may be empty if the announcement contains no unusual or
  decision-relevant conditions.
- Treat Hiring Paths as eligibility information, not qualification evidence.
- In Hiring Eligibility, state plainly whether the job is open to the public,
  internal to the agency, open to veterans, or limited to specific federal
  hiring groups. Keep this to one concise sentence.
- Do not surface standard federal application rules as Important Notes unless
  this announcement differs from the usual rule or the detail is unusually
  consequential.
- For resume-length rules, mention them only when the announcement explicitly
  provides an exception to the standard two-page limit.
- Education must be concise. If no degree, coursework, or education
  substitution is required, return exactly "None required."
- If education may substitute for experience, briefly state how.
- If a specific degree or coursework is mandatory, state only that requirement.
- For education, return only the practical qualification requirement.
- If no education is required and education does not substitute for experience,
  return exactly: None required.

- Hiring Eligibility must be one concise sentence based only on the supplied
  Hiring Paths. Do not infer additional eligibility rules.
- If "The public" is listed, explicitly say the job is open to the public.
- Mention other important listed groups such as Veterans or Internal to agency.

- Important Notes is reserved for unusual announcement-specific facts only.

- Include an Important Note only if it fits one of these categories:
  1. An applicant limit or unusual closing rule.
  2. An explicit exception to the standard federal resume-length rule.
  3. Unusual travel, schedule, relocation, physical, testing, certification,
     or other conditions specific to this position.
  4. Another genuinely unusual announcement-specific fact that could
     materially change an applicant's decision to apply.

- Do not include resume-writing instructions, required resume contents,
  employment-date or hours-per-week instructions, documentation rules,
  SF-50/SF-52 requirements, proof-of-experience instructions, or explanations
  of how experience will be credited.
- Do not include information about obtaiing and using a Government-issue charge 
  card for business-related travel.

- Do not include standard federal employment conditions, eligibility rules,
  duties, specialized experience, education, hiring paths, clearance, remote
  status, or other information already represented elsewhere in the analysis.

- If nothing meets the permitted categories above, return an empty array.

JOB DATA
Title: {$job["title"]}
Organization: {$job["organization"]}
Series: {$job["series"]}
Pay Plan: {$job["payPlan"]}
Grade Low: {$gradeLow}
Grade High: {$gradeHigh}
Remote: {$isRemote}
Applicant Limit: {$applicantLimit}
Hiring Paths: {$hiringPaths}

Job Summary:
{$job["jobSummary"]}

Major Duties:
{$job["duties"]}

Qualification Summary:
{$job["qualificationSummary"]}

Requirements:
{$job["requirements"]}

Education:
{$job["education"]}

Security Clearance:
{$clearance}

Key Requirements:
{$keyRequirements}

OUTPUT REQUIREMENTS
Return an analysis containing:
- summary
- key duties
- specialized experience
- hiring eligibility
- education
- clearance
- important notes

Do not include commentary outside the requested structured output.
PROMPT;
}