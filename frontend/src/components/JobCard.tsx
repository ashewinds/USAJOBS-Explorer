import type { Job } from "../types/Job";
import { useState } from "react";
import type { JobAnalysis } from "../types/JobAnalysis";
import JobAnalysisPanel from "./JobAnalysisPanel";

type JobCardProps = {
    job: Job;
};

function JobCard({job}: JobCardProps) {

    const [isExpanded, setIsExpanded] = useState(false);

    const payPlan = job.pay_plan;

    const gradeRange = job.grade_low === job.grade_high
        ? `${job.grade_low}`
        : `${job.grade_low}-${job.grade_high}`;

    const displayEndDate = job.actual_close_date ?? job.end_date;

    const [analysis, setAnalysis] = useState<JobAnalysis | null>(null);
    const [isAnalyzing, setIsAnalyzing] = useState(false);

    const handleAnalyze = async () => {
        try {
            setIsAnalyzing(true);

            const response = await fetch(`https://usajobs.cc/api/analyze-job.php?id=${job.id}`);

            if (!response.ok) {
                throw new Error("Failed to analyze job.");
            }
            
            const data: JobAnalysis = await response.json();

            setAnalysis(data);
        } catch (error) {
            console.error("AI analysis error:", error);
        } finally {
            setIsAnalyzing(false);
        }
    };

    return (
        <div className="job-card"
            onKeyDown={(event) => {
                if (event.key === "Enter" || event.key === " ") {
                    setIsExpanded((previous) => !previous);
                }
            }}
            role="button"
            tabIndex={0}
            aria-expanded={isExpanded}>

            <div className="job-meta">

                <div className="job-meta-left">

                    <p>{payPlan}: {gradeRange}</p>
                    <p>Remote: {job.is_remote ? "Yes" : "No"}</p>
                </div>
                
                <div className="job-meta-center">
                    <p><strong>{job.series} - {job.title}</strong></p>
                    <p>{job.department} <br /> {job.organization}</p>
                </div>
                
                <div className="job-meta-right">

                    <p><strong>{job.status.toUpperCase()}</strong></p>
                    <p>{job.status === "Open"
                                        ? "Accepting Applications"
                                        : job.position_opening_status
                                            ? job.position_opening_status.replace(/\b\w/g, (char) => char.toUpperCase())
                                            : ""}</p>
                    <p> {new Date(job.start_date).toLocaleDateString()} to{" "} {new Date(displayEndDate).toLocaleDateString()}</p>
                    {job.applicant_limit !== null && (
                    <p>Applicant Limit: {job.applicant_limit}</p>
                )}
                </div>
            
            </div>
                
            <button
                className="expand-label"
                onClick={() => setIsExpanded((previous) => !previous)}
            >
                {isExpanded ? "Hide Details" : "Show Details"}
            </button>


            {isExpanded && (
                <>
                    <div className="job-details">
                        <div className="job-details-left">
                            <p><strong>Matched locations:</strong></p>

                                {job.is_remote ? (
                                    <ul>
                                        <li>Anywhere in the U.S. (Remote Job)</li>
                                    </ul>
                                ) : (
                                    <ul>
                                        {job.matched_search_locations.map((location) => (
                                            <li key={location}>{location}</li>
                                        ))}
                                    </ul>
                                )}
                        </div>
                        <div className="job-details-right">
                            <p>Announcement #: {job.position_id} <br /> Control #: {job.control_number}</p>
                            <p><a href={job.position_uri} target="_blank" rel="noopener noreferrer">View on USAJOBS.gov</a></p>
                        </div>
                    </div>
                    <div className="job-analysis">
                        <button
                            onClick={handleAnalyze}
                            disabled={!job.has_analyzable_data || isAnalyzing}
                            title={
                                job.has_analyzable_data
                                    ? "Analyze this job with AI"
                                    : "AI analysis unavailable because historical USAJOBS data does not include enough detail."
                            }
                        >
                            {isAnalyzing ? "Analyzing" : "Analyze with AI"}
                        </button>

                        {analysis && (
                            <JobAnalysisPanel analysis={analysis} />
                        )}
                    </div>
               </> 
            )} 
        </div>
       
    );
}

export default JobCard;