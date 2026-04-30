# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Package overview

Laravel Models Scanner — a development-only debug page (`/_models`) that introspects Eloquent models and the database schema, then displays a side-by-side comparison of:

- Tables and the model classes that map to them
- Whether each model uses `SoftDeletes` (and whether the column actually exists in the database)
- Eloquent relationships defined on models (with method name, type, related model, `withTrashed`/`withoutTrashed` info, schema columns and FK constraint name)
- Foreign-key relationships introspected from the database that are **not** declared on any model (proposals, with one-click copy of the corresponding Eloquent code)

Search and filter controls let you narrow the view by table, model, relation name/model/schema or by status (defined, undefined, untyped, errors, tables without model).

The route is registered only when `app()->isLocal()`.

- **Namespace:** `Axn\ModelsScanner`
- **Requires:** PHP 8.4+, Laravel 12+, doctrine/dbal 4.4+
- **Auto-discovered** via `ServiceProvider` (registered in `composer.json` extra.laravel.providers)

## Commands

```bash
# Install dependencies
composer install

# Code style (Laravel Pint, laravel preset)
vendor/bin/pint            # fix
vendor/bin/pint --test     # check only

# Rector (automated refactoring)
vendor/bin/rector           # apply
vendor/bin/rector --dry-run # preview changes
```

No test suite exists in this package.

## Architecture

### Source files (`src/`)

- **`ServiceProvider`** — Merges and publishes the config, then (in local environment only) registers the `/_models` route and loads the `models-scanner` view namespace.
- **`Controllers/ScanController`** — Single invokable controller backing `GET /_models`. Calls `ScanMerger`, applies the search/filter from the query string, returns the `models-scanner::scan` view.
- **`Services/DatabaseScanner`** — Uses `doctrine/dbal` to introspect tables and foreign keys for the default connection. Resolves a DBAL connection via `Connection::getDoctrineConnection()` when available, otherwise rebuilds one from the Laravel config. Each FK produces both a `BelongsTo` entry on the local table and an inverse `HasMany` entry on the foreign table. Identifiers are unquoted across MySQL (backticks), PostgreSQL (double quotes) and SQL Server (brackets).
- **`Services/ModelsScanner`** — Walks `vendor/composer/autoload_classmap.php`, keeps classes whose FQCN matches `models-scanner.models_namespace_regex` and which extend `Eloquent\Model`. For each concrete model, reflects its public methods to detect relationships (return type matching `Illuminate\Database\Eloquent\Relations\*`, or fallback to a regex on the method body). For each detected relation, the actual `Relation` instance is built and inspected (keys, related model, pivot/through tables, soft-delete scope removal). Also resolves the declaring trait FQCN for relations defined in traits (handles `as` aliases and nested traits).
- **`Services/ScanMerger`** — Joins `DatabaseScanner` and `ModelsScanner` outputs by table name. For each model, matches each DB FK against a declared relation (same type/local/foreign columns); unmatched DB FKs become "proposed relations" with a guessed method name, unmatched model relations are appended. Computes a `soft_deletes` ternary status (true = uses SoftDeletes and column exists, false = column exists but trait missing, null = no column).

### Views (`resources/views/`)

- **`scan.blade.php`** — Main page: search box, filter dropdown, table listing one row per model with its relations and the proposed relations. Uses inline JS to handle clipboard copy of single/all proposed relations.
- **`partials/`** — Sub-templates for each relation type rendering.

### Config (`config/models-scanner.php`)

Single key:

- `models_namespace_regex` (default `/\\Models\\/i`) — regex applied to the FQCN to decide whether a class should be considered a candidate model. Override via `config/models-scanner.php` after `php artisan vendor:publish --tag=models-scanner-config`.

## Code style notes

- Pint config (`pint.json`): laravel preset with `native_function_invocation` (compiler_optimized, namespaced, strict) and custom `blank_line_before_statement` rules.
- Rector targets PHP 8.4 with deadCode, codeQuality, codingStyle, typeDeclarations, instanceOf, earlyReturn, carbon sets. `AddOverrideAttributeToOverriddenMethodsRector` is skipped.

## Laravel Boost Assets

The package provides Laravel Boost integration assets in `resources/boost/`:
- **Guidelines** (`guidelines/core.blade.php`): Package overview for AI assistants

**Important:** These files must be kept up to date when components, configuration keys, or usage patterns change. When adding, renaming, or removing components or config options, update the corresponding Boost assets accordingly.
