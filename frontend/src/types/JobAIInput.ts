export type JobAIInput = {
    title: string;
    organization: string;
    series: string;
    payPlan: string;
    gradeLow: number | null;
    gradeHigh: number | null;
    isRemote: boolean;
    applicantLimit: number | null;
    hiringPaths: string[];

    jobSummary: string;
    duties: string;
    qualificationSummary: string;
    requirements: string;
    education: string;

    clearance: string | null;
    keyRequirements: string[];
};