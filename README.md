# USAJOBS Explorer

A PHP and MariaDB application that collects, normalizes, stores, and analyzes current and historical federal job-announcement data from USAJOBS.

## Overview

USAJOBS Explorer began as a way to build a historical dataset for analyzing federal employment trends. The original goal was to answer questions such as whether opportunities in particular occupational series or geographic areas were increasing or decreasing over time.

The application imports current adn historical USAJOBS announcements, maps data from differing API structures into a consistent database schema, preserves historical records, and prepares the information for searching, filtering, and trend analysis.

The project currently tracks:

-   0343 - Management and Program Analysis
-   2210 - Information Technology Management

## Project Goals

-   Build a historical record of federal job announcments
-   Compare job availability across locations and time periods
-   Analyze trends within selected occupational series
-   Track remote, telework-eligible, full-time, and part-time opportunities
-   Preserve announcements after they are no longer available through the current-jobs API
-   Provide searchable data for both individual job research and broader employment analysis

## Current Features

-   Imports current and historical USAJOBS announcements
-   Processes the 0343 and 2210 occupational series
-   Integrates multiple USAJOBS API structures
-   Maps API data into a consistent database schema
-   Stores announcement data in MariaDB
-   Avoids duplicate records using USAJOBS control numbers
-   Tracks when announcements were first and last observed
-   Identifies open and closed announcements
-   Preserves existing values when an API response does not contain updated information
-   Detects remote positions and selected geographic matches
-   Stores both normalized fields and original JSON data
-   Records import-run status and results
-   Supports scheduled automated imports

## Tech Stack

**Backend** - PHP - MariaDB / MySQL - SQL - cURL

**Frontend** - HTML - CSS - JavaScript

**Tools** - Git - GitHub - VS Code - phpMyAdmin

## What I Learned

This project strengthened my experience with:

-   REST API integration
-   JSON processing
-   Database schema design
-   SQL query optimization
-   Backend application architecture
-   Debugging complex data import workflows
-   Version control with Git

## Future Improvements

-   Responsive search interface
-   Advanced filtering
-   Email notifications
-   Analytics dashboard
-   Improved import monitoring

## Status

Active development

This project is under active development. Current work focuses on completing the data-import and historical-tracking systems before expanding the analytical interface, filters, reports, and visualizations.

## Author

Ashley Vance
