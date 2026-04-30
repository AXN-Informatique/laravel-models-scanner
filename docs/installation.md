---
title: Installation
order: 1
---

Installation
============

Prérequis
---------

- PHP 8.4+
- Laravel 12+ ou 13+
- doctrine/dbal 4.4+

Installation via Composer
-------------------------

Le package est destiné à un usage en développement uniquement :

```bash
composer require axn/laravel-models-scanner --dev
```

Le `ServiceProvider` est auto-découvert grâce à la configuration `extra.laravel.providers` du `composer.json`.

Publication de la configuration (optionnel)
-------------------------------------------

```bash
php artisan vendor:publish --tag=models-scanner-config
```

Cela crée le fichier `config/models-scanner.php` avec la valeur par défaut de `models_namespace_regex`.
