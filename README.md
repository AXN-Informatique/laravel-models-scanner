Laravel Models Scanner
======================

Scans Eloquent models to show some information about them.
It also scans database (default connection) to compare with models.

Installation
------------

Install the package with Composer:

```sh
composer require axn/laravel-models-scanner --dev
```

Publish config if needed using these commands:

```sh
php artisan vendor:publish --tag=models-scanner-config
```

Config is published in `config/models-scanner.php`

Modify config options if needed.

Usage
-----

In your browser, go to `https://{your_project_domain}/_models`

This URL is only accessible in local environment.

This showing:

* Table name with associated model class
* Information about if model uses SoftDeletes
* Models relationships with:
  * Method name
  * Method type (BelongsTo, HasMany, etc.)
  * Related model class
  * Information about withTrashed or whithoutTrashed on the relationship, if related model uses SoftDeletes
  * DB schema of the relationship (for example: `users.id ← posts.user_id`)
  * DB constraint name, if set
* Proposals of relationships detected from DB (aligned right)

BelongsTo relationships are colored in blue.
HasMany relationships are colored in orange.
Other types of relationships are left black.
If red: there is an error (for example: the related model does not exist).

On a proposed relationship, you can left click to copy the corresponding code to clipboard.
You can also right click to copy all proposed relationships code to clipboard.
