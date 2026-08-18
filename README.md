# USAJOBS Explorer

USAJOBS Explorer is a personal full-stack project for exploring and analyzing federal job announcements from USAJOBS.

It was built primarily to support a focused federal job search while also serving as a hands-on learning project involving React, TypeScript, PHP, MySQL/MariaDB, REST APIs, scheduled data imports, and structured AI analysis.

## Scope

USAJOBS Explorer is currently focused on two federal occupational series:

- **0343** — Management and Program Analysis
- **2210** — Information Technology Management

The current data pipeline, filters, search locations, and interface reflect those use cases rather than the full USAJOBS catalog.

## Features

- Imports current and historical USAJOBS announcement data
- Stores normalized job data in MySQL/MariaDB
- Tracks current vs. closed job status
- Supports filtering by:
  - occupational series
  - open/closed status
  - remote availability
- Supports sorting by:
  - newest
  - closing soonest
  - pay plan / grade ascending
  - pay plan / grade descending
- Displays matched search locations and remote status
- Links directly to the original USAJOBS announcement
- Uses expandable job cards for additional details
- Determines whether enough source data exists for AI analysis
- Provides structured AI analysis for supported announcements

## AI Job Analysis

The AI analysis feature is designed specifically for federal job announcements rather than as a generic summarizer.

For jobs with sufficient source data, the application sends a normalized subset of the announcement to the OpenAI API and requests a strict structured response.

The analysis includes:

- Summary
- Key duties
- Specialized experience
- Hiring eligibility
- Education requirements
- Security clearance
- Important announcement-specific notes

The prompt is designed to preserve distinctions between:

- qualifications
- hiring eligibility
- duties
- education substitution
- conditions of employment
- security requirements

Structured Outputs are used so the model response follows a defined JSON schema before being rendered in the React interface.

Historical records that do not contain enough detailed announcement data are not sent for AI analysis.

## Data Pipeline

The project uses scheduled PHP import scripts to retrieve and normalize USAJOBS data.

The pipeline includes:

- current-job imports
- historical-job imports
- long-text/detail imports
- import run tracking
- retry handling
- historical import completion verification

Maintenance scripts are kept separately from scheduled production import scripts.

## Data Freshness

USAJOBS Explorer reflects the most recent successful import from USAJOBS data sources.

USAJOBS current listings, historical records, and public-facing job pages may update at different times. Because of this, an announcement's status in USAJOBS Explorer may temporarily differ from the live USAJOBS page between scheduled imports.

For example, a job may disappear from the current USAJOBS feed before the historical API reflects its updated closing or cancellation status.

The application can therefore only be as current as the structured data available from USAJOBS at the time of the most recent import.

## Tech Stack

### Frontend

- React
- TypeScript
- Vite
- CSS

### Backend

- PHP
- PDO
- MySQL / MariaDB

### APIs

- USAJOBS current API
- USAJOBS historical JOA API
- OpenAI Responses API

### Other

- Scheduled cron jobs
- JSON normalization
- Structured prompting
- Strict JSON-schema AI output
- Git / GitHub

## Project Structure

```text
USAJOBS Explorer/
├── api/
│   ├── analyze-job.php
│   ├── job-ai-input.php
│   ├── jobs.php
│   ├── helpers/
│   └── prompts/
├── config/
├── cron/
├── frontend/
│   ├── src/
│   │   ├── components/
│   │   └── types/
│   └── ...
├── includes/
├── maintenance/
└── ...
```

Private configuration and credentials are excluded from Git.

## Frontend Deployment

The React frontend is built with Vite:

```bash
npm run build
```

Vite creates the production files in:

```text
frontend/dist/
```

The contents of `dist/` are deployed to the site's public web root.

## Development Goals

This project is both a working personal tool and a learning environment.

Areas explored through the project include:

- React component architecture
- TypeScript typing
- REST API integration
- PHP backend endpoints
- database normalization
- scheduled data pipelines
- structured LLM prompting
- JSON schema enforcement
- production deployment
- operational reliability and monitoring

## Future Ideas

Possible future improvements include:

- private resume-to-job comparison
- authenticated candidate profiles
- additional job-series support
- more advanced filters
- improved import monitoring
- richer AI-assisted qualification analysis

These are intentionally kept separate from the current working version.

## Disclaimer

This project is not affiliated with or endorsed by USAJOBS, the U.S. Office of Personnel Management, or any federal agency.

Job seekers should always verify announcement details directly on the official USAJOBS listing before applying.
