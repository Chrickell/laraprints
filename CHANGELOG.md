# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 2026-03-26

### Added
- Middleware-based page view tracking (`TrackPageViews`) with queue dispatch
- Click tracking via POST API endpoint (`/api/clicks`)
- Vue 3 + Tailwind CSS analytics dashboard component (`AnalyticsDashboard`)
- Bar chart component (`AnalyticsBarChart`) for trend visualization
- JavaScript composable (`useAnalyticsTracking`) for frontend click capture
- Device type detection (desktop / mobile / unknown)
- Bot and crawler detection and exclusion
- Session-based visit ID tracking
- Referrer URL tracking
- Query parameter capture
- Configurable excluded paths with wildcard support
- Admin exclusion support (boolean or callable)
- Queue-based jobs (`StorePageView`, `StoreClick`) with configurable connection and queue names
- Automatic data pruning via `MassPrunable` on both models
- Dashboard API endpoints for page views and clicks with date range filtering
- Top pages, referrers, and clicked elements aggregation
- Inertia.js integration support
- Support for Laravel 10, 11, and 12
- Support for PHP 8.1+
