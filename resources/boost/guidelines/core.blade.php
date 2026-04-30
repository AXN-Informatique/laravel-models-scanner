# Models Scanner

- Provides a development-only debug page at `/_models` that compares Eloquent model relationships with the database schema and lists undefined relationships as proposals.
- The route is registered only when `app()->isLocal()` returns `true`. It is unreachable in staging or production.
- Models are discovered through Composer's classmap, filtered by the regex `models-scanner.models_namespace_regex` (default matches any class containing `\Models\`).
- Database schema is introspected via `doctrine/dbal` on the **default** connection only. There is no UI option to switch connections; use the `DatabaseScanner` service directly if needed.
- The page detects relationships via the method's return type (`Illuminate\Database\Eloquent\Relations\*`) or, as a fallback, by regex-matching the method body for `belongsTo`, `hasMany`, etc.
- Search and filter controls in the UI narrow the view by table, model, relation, schema columns or status (defined / undefined / untyped / errors / tables without model).
