---
title: Configuration
order: 3
---

Configuration
=============

Fichier `config/models-scanner.php`
------------------------------------

Une seule clé de configuration est exposée :

```php
return [

    'models_namespace_regex' => '/\\\Models\\\/i',

];
```

`models_namespace_regex`
------------------------

Expression régulière appliquée au FQCN (Fully Qualified Class Name) de chaque classe trouvée dans `vendor/composer/autoload_classmap.php` pour décider si elle doit être considérée comme un modèle candidat.

La valeur par défaut `/\\\Models\\\/i` correspond aux classes contenant `\Models\` dans leur namespace, ce qui couvre la convention Laravel `App\Models\*` ainsi que les modèles d'autres packages comme `Vendor\Package\Models\*`.

Adapter cette regex si vos modèles sont organisés différemment, par exemple :

```php
// N'inclure que les modèles de l'application (pas ceux des packages)
'models_namespace_regex' => '/^App\\\Models\\\/',

// Inclure plusieurs namespaces
'models_namespace_regex' => '/\\\(Models|Entities)\\\/i',
```

Connexion à la base de données
------------------------------

Le scan utilise la **connexion par défaut** (`config('database.default')`). Aucune option de configuration n'est exposée pour changer cela ; le service `DatabaseScanner` accepte un paramètre `connectionName` pour un usage programmatique si nécessaire.
