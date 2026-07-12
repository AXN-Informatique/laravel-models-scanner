# Models Scanner

- Development-only debug page at `/_models` comparing Eloquent model relationships with the database schema (default connection only); the route is registered only when `app()->isLocal()` — unreachable in staging/production.
- See the package's `docs/` directory for discovery, detection and filtering details.
