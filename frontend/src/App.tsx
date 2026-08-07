import {useEffect, useState} from "react";
import "./App.css";
import JobCard from "./JobCard";
import type {Job} from "./types/Job";

function App() {

  const [jobs, setJobs] = useState<Job[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState("");

  useEffect(() => {
    fetch("http://localhost:8000/api/jobs.php")
      .then((response) => {
        if (!response.ok) {
          throw new Error("Request failed");
        }
        return  response.json();
      })
      .then((data) => {
        setJobs(data);
      })
      .catch(() => {
        setError("Unable to load jobs");
      })
      .finally(() => {
        setIsLoading(false);
      });
    }, []);

  return (
    <main className="app-container">
      <h1>USAJOBS Explorer</h1>

      {/* Show this while the jobs are loading */}
      {isLoading && <p>Loading jobs...</p>}

      {/* Show this if loading the jobs fails */}
      {error && <p>{error}</p>}

      {/* Show this if loading succeeded but no jobs were returned */}
      {!isLoading && !error && jobs.length === 0 && (
        <p>No jobs found.</p>
      )}

      {/* Show the number of jobs returned */}
      {!isLoading && !error && jobs.length > 0 && (
        <p className="results-count">
          {jobs.length} {jobs.length === 1 ? "job" : "jobs"} found
        </p>
      )}

      <section className="job-list">
        {/* Create one JobCard for every job */}
        {jobs.map((job) => (
          <JobCard key={job.id} job={job} />
        ))}
      </section>
    </main>
  );
}

export default App;