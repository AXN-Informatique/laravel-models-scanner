---
title: Architecture
order: 4
---

Architecture
============

Le package se compose de quatre éléments principaux : un service provider, un controller, et trois services métier.

ServiceProvider
---------------

`Axn\ModelsScanner\ServiceProvider` enregistre la configuration et, en environnement local uniquement :

- Charge la route `/_models` depuis `routes/web.php`
- Charge les vues du namespace `models-scanner`
- Publie le fichier de configuration via `vendor:publish --tag=models-scanner-config`

Controller
----------

`Axn\ModelsScanner\Controllers\ScanController` est un controller invokable unique qui :

1. Appelle `ScanMerger::execute()` pour récupérer les données fusionnées
2. Applique les filtres de recherche depuis la query string
3. Retourne la vue `models-scanner::scan`

Services
--------

| Service | Responsabilité |
|---------|----------------|
| `DatabaseScanner` | Introspection du schéma DB via doctrine/dbal |
| `ModelsScanner` | Introspection des modèles Eloquent via Reflection |
| `ScanMerger` | Fusion des deux résultats par nom de table |

### `DatabaseScanner`

Utilise `doctrine/dbal` pour introspecter les tables et clés étrangères de la connexion par défaut. Pour chaque FK détectée, deux entrées sont créées :

- Une `BelongsTo` sur la table locale
- Une `HasMany` inverse sur la table étrangère

Les identifiants SQL sont normalisés (déquotage des backticks MySQL, double quotes PostgreSQL et brackets SQL Server).

### `ModelsScanner`

Parcourt `vendor/composer/autoload_classmap.php` et conserve les classes :

1. Dont le FQCN matche `models-scanner.models_namespace_regex`
2. Qui étendent `Illuminate\Database\Eloquent\Model`
3. Qui sont concrètes (non abstraites)

Pour chaque modèle, les méthodes publiques sont inspectées pour détecter les relations Eloquent :

- Soit par le **type de retour** (matching `Illuminate\Database\Eloquent\Relations\*`)
- Soit, à défaut, par une **regex sur le corps** de la méthode (`return $this->belongsTo(...)`, `hasMany(...)`, etc.)

Pour chaque relation détectée, l'instance `Relation` est construite et inspectée pour récupérer les clés, modèle lié, table pivot, table through et le statut du `SoftDeletingScope`. Le FQCN du trait déclarant la relation est aussi résolu (avec gestion des alias `as` et des traits imbriqués).

### `ScanMerger`

Joint les résultats des deux scanners par nom de table. Pour chaque modèle :

1. Chaque FK détectée en base est matchée contre les relations déclarées (sur le tuple `type`, `local_column`, `foreign_table`, `foreign_column`)
2. Les FK non matchées deviennent des **relations proposées** avec un nom de méthode deviné (par `Str::camel` sur la colonne ou la table)
3. Les relations déclarées non matchées (par exemple morphs, relations sans FK en base) sont conservées
4. Le statut ternaire `soft_deletes` est calculé : `true` (utilisé et colonne présente), `false` (colonne `deleted_at` en base mais trait absent), `null` (aucune colonne `deleted_at`)

Vues
----

- `resources/views/scan.blade.php` — Page principale (table, filtres, JS clipboard)
- `resources/views/partials/` — Templates pour chaque type de relation
