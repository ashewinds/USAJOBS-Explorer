import { useEffect, useState } from "react";
import "./App.css";
import JobCard from "./components/JobCard";
import type { Job } from "./types/Job";
import FilterPanel from "./components/FilterPanel";

function App() {

  const [jobs, setJobs] = useState<Job[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState("");
  const [selectedSeries, setSelectedSeries] = useState("2210");
  const [selectedStatus, setSelectedStatus] = useState("Open");
  const [selectedSort, setSelectedSort] = useState("closing-soon");
  const [selectedRemote, setSelectedRemote] = useState("all");

  const filteredJobs = jobs.filter(
    (job) =>
      job.series === selectedSeries &&
      job.status === selectedStatus &&
      (
        selectedRemote === "all" ||
        (selectedRemote === "remote" && job.is_remote) ||
        (selectedRemote === "not-remote" && !job.is_remote)
      )
  )
  .sort((a, b) => {
    if (selectedSort === "closing-soonest") {
      return new Date(a.end_date).getTime() - new Date(b.end_date).getTime();
    }
    if (selectedSort === "newest") {
      return new Date(b.start_date).getTime() - new Date(a.start_date).getTime();
    }

    if (selectedSort === "grade-asc" || selectedSort === "grade-desc") {
      const getPayPlanGroup = (job: Job) => {
        if (job.pay_plan === "GS" || job.pay_plan === "GG") {
          return "GS/GG";
        }
        return job.pay_plan;
      };

      const aPayPlan = getPayPlanGroup(a);
      const bPayPlan = getPayPlanGroup(b);

      if (aPayPlan !== bPayPlan) {
        return selectedSort === "grade-asc"
          ? aPayPlan.localeCompare(bPayPlan)
          : bPayPlan.localeCompare(aPayPlan);
      }

      const aGrade = a.grade_low ?? Infinity;
      const bGrade = b.grade_low ?? Infinity;

      return selectedSort === "grade-asc"
        ? aGrade - bGrade
        : bGrade - aGrade;
    }

    return 0;
  });

  useEffect(() => {
    fetch("https://usajobs.cc/api/jobs.php")
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
      <h1 className="page-heading">USAJOBS Explorer</h1>

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
          Job Count: {filteredJobs.length} 
        </p>
      )}

      <FilterPanel
        selectedSeries={selectedSeries}
        onSeriesChange={setSelectedSeries}
        selectedStatus={selectedStatus}
        onStatusChange={setSelectedStatus}
        selectedSort={selectedSort}
        onSortChange={setSelectedSort}
        selectedRemote={selectedRemote}
        onRemoteChange={setSelectedRemote}
      />

      <section className="job-list">
        {/* Create one JobCard for every job */}
        {filteredJobs.map((job) => (
          <JobCard key={job.id} job={job} />
        ))}
      </section>
    </main>
  );
}

export default App;