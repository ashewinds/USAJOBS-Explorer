import type {Job} from "./types/Job";

type JobCardProps = {
    job: Job;
};

function JobCard({job}: JobCardProps) {
    return (
    <div>
        <p>Job title: {job.title}</p>
        <p>Grade: GS-{job.grade}</p>

        {job.remote && <p>Remote</p>}
    </div>
    );
}

export default JobCard;