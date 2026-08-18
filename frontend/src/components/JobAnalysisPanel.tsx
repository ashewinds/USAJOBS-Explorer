import type { JobAnalysis } from "../types/JobAnalysis";

type JobAnalysisPanelProps = {
    analysis: JobAnalysis;
};

export default function JobAnalysisPanel({
    analysis
}: JobAnalysisPanelProps) {
    return (
        <div className="job-analysis-panel">
            <h3>AI Job Analysis</h3>

            <section>
                <h4>Summary</h4>
                <p>{analysis.summary}</p>
            </section>

            <section>
                <h4>Key Duties</h4>
                <ul>
                    {analysis.keyDuties.map((duty, index) => (
                        <li key={index}>{duty}</li>
                    ))}
                </ul>
            </section>

            <section>
                <h4>Specialized Experience</h4>
                <ul>
                    {analysis.specializedExperience.map((item, index) => (
                        <li key={index}>{item}</li>
                    ))}
                </ul>
            </section>

            <section>
                <h4>Hiring Eligibility</h4>
                <p>{analysis.hiringEligibility}</p>
            </section>

            <section>
                <h4>Education</h4>
                <p>{analysis.education}</p>
            </section>

            <section>
                <h4>Security Clearance</h4>
                <p>{analysis.clearance ?? "None listed"}</p>
            </section>

            {analysis.importantNotes.length > 0 && (
                <section>
                    <h4>Important Notes</h4>
                    <ul>
                        {analysis.importantNotes.map((note, index) => (
                            <li key={index}>{note}</li>
                        ))}
                    </ul>
                </section>
            )}
                
        </div>
    );
}