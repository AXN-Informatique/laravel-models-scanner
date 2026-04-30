---
title: Utilisation
order: 2
---

Utilisation
===========

Accès à la page
---------------

Une fois le package installé, accéder à la page de debug via :

```
https://{votre-domaine}/_models
```

La route est uniquement enregistrée lorsque l'application tourne en environnement local (`app()->isLocal()` retourne `true`). Elle est inaccessible en staging ou en production.

Lecture des résultats
---------------------

Pour chaque table détectée en base, la page affiche :

- Le **nom de la table** et la **classe du modèle** Eloquent associée (s'il y en a une)
- L'utilisation ou non de **`SoftDeletes`** sur le modèle, comparée à la présence de la colonne `deleted_at` en base
- Les **relations déclarées** sur le modèle, avec :
    - Nom de la méthode
    - Type de relation (`BelongsTo`, `HasMany`, `MorphTo`, etc.)
    - Classe du modèle lié
    - Information `withTrashed` / `withoutTrashed` si le modèle lié utilise `SoftDeletes`
    - Schéma DB de la relation (par exemple : `users.id ← posts.user_id`)
    - Nom de la contrainte FK si elle existe en base
- Les **propositions de relations** détectées en base mais non déclarées sur le modèle (alignées à droite)

Code couleur
------------

| Couleur | Signification |
|---------|---------------|
| Bleu | Relation `BelongsTo` |
| Orange | Relation `HasMany` |
| Noir | Autres types de relations |
| Rouge | Erreur (par exemple : modèle lié inexistant) |

Copie du code généré
--------------------

Sur une relation proposée :

- **Clic gauche** : copie le code de la méthode de relation correspondante dans le presse-papiers
- **Clic droit** : copie le code de toutes les relations proposées de la table dans le presse-papiers

Recherche et filtres
--------------------

La barre de recherche permet de filtrer par :

- Nom de table
- Classe de modèle
- Nom de méthode de relation
- Modèle lié dans une relation
- Schéma DB d'une relation (`table.colonne`)

Le filtre permet de n'afficher que :

- Les relations définies sur les modèles
- Les relations détectées en base mais non définies sur les modèles
- Les relations sans type de retour
- Les tables sans modèle associé
- Les erreurs détectées
