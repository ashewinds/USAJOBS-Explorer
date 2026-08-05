import {useEffect, useState} from "react";
import JobCard from "./JobCard";
import type {Job} from "./types/Job";

function App() {

  const [jobs, setJobs] = useState<Job[]>([]);

  useEffect(() => {
    fetch("http://localhost:8000/api/jobs.php")
      .then((response) => response.json())
      .then((data) => {setJobs(data)}), []});

  return (
    <main>
      <h1>USAJOBS Explorer</h1>

      {jobs.map((job) => (
        <JobCard key={job.id} job={job} />
      ))}
    </main>
  );
}

export default App;