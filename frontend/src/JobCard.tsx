import type {Job} from "./types/Job";
import {useState} from "react";

type JobCardProps = {
    job: Job;
};

function JobCard({job}: JobCardProps) {

    const [isExpanded, setIsExpanded] = useState(false);

    return (
    <div className="job-card" 
        onClick={() => setIsExpanded((previous) => !previous)}
        onKeyDown={(event) => {
            if (event.key === "Enter" || event.key === " ") {
                setIsExpanded((previous) => !previous);
            }
        }}
        role="button"
        tabIndex={0}
        aria-expanded={isExpanded}>
        <p>Job title: {job.title}</p>
        <p>Grade: GS-{job.grade}</p>
        {job.remote && <p>Remote</p>}
        {isExpanded && (
            <div className="job-details">
                <p>More job details will go here.</p>
            </div>
        )}
        <p className="expand-label">
            {isExpanded ? "▲ Hide details" : "▼ Show details"}
        </p>
    </div>
    );
}

export default JobCard;