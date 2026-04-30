<?php

namespace Axn\ModelsScanner\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionType;
use Throwable;

final class ModelsScanner
{
    public function scan(): array
    {
        $classmap = require base_path('vendor/composer/autoload_classmap.php');

        $result = [];

        foreach (array_keys($classmap) as $class) {
            if (! preg_match(config('models-scanner.models_namespace_regex'), (string) $class)) {
                continue;
            }

            if (! class_exists($class)) {
                continue;
            }

            if (! is_subclass_of($class, Model::class)) {
                continue;
            }

            $reflection = new ReflectionClass($class);

            if ($reflection->isAbstract()) {
                continue;
            }

            $model = new $class();

            $result[$model->getTable()] ??= [];
            $result[$model->getTable()][$class] = [
                'deleted_at_column' => $this->usesSoftDeletes($model) ? $model->getDeletedAtColumn() : null,
                'relations' => [],
            ];

            foreach ($reflection->getMethods() as $method) {
                if (! $this->methodIsRelation($method)) {
                    continue;
                }

                $result[$model->getTable()][$class]['relations'][] = $this->getModelRelationInfo($model, $method);
            }
        }

        return $result;
    }

    /**
     * @return mixed[]
     */
    private function getModelRelationInfo(Model $model, ReflectionMethod $method): array
    {
        try {
            $relation = $model->{$method->getName()}();

        } catch (Throwable $throwable) {
            return [
                'method_name' => $method->getName(),
                'error' => $throwable->getMessage(),
            ];
        }

        $info = [
            'method_name' => $method->getName(),
            'miss_return_type' => ! $method->getReturnType() instanceof ReflectionNamedType,
            'declaration_source' => $this->getDeclaringTraitNameWithAliases($method) ?? $method->getDeclaringClass()->getName(),
            'type' => class_basename($relation::class),
            'with_trashed' => $this->usesSoftDeletes($relation->getRelated()) ? \in_array(
                SoftDeletingScope::class,
                $relation->getQuery()->removedScopes(),
                true
            ) : null,
        ];

        if ($relation instanceof MorphTo) {
            $info['morph_type'] = $relation->getMorphType();
            $info['local_column'] = $relation->getForeignKeyName();
            $info['foreign_column'] = $relation->getOwnerKeyName() ?? 'id';

            return $info;
        }

        $info['related_model_class'] = $relation->getRelated()::class;
        $info['foreign_table'] = $relation->getRelated()->getTable();

        if ($relation instanceof HasOneOrMany) {
            $info['local_column'] = $relation->getLocalKeyName();
            $info['foreign_column'] = $relation->getForeignKeyName();

        } elseif ($relation instanceof BelongsTo) {
            $info['local_column'] = $relation->getForeignKeyName();
            $info['foreign_column'] = $relation->getOwnerKeyName();

        } elseif ($relation instanceof BelongsToMany) {
            $info['local_column'] = $relation->getParentKeyName();
            $info['foreign_column'] = $relation->getRelatedKeyName();
            $info['pivot_table'] = $relation->getTable();
            $info['pivot_column_1'] = $relation->getForeignPivotKeyName();
            $info['pivot_column_2'] = $relation->getRelatedPivotKeyName();

        } elseif ($relation instanceof HasManyThrough) {
            $info['local_column'] = $relation->getLocalKeyName();
            $info['foreign_column'] = $relation->getForeignKeyName();
            $info['through_table'] = $relation->getParent()->getTable();
            $info['through_column_1'] = $relation->getFirstKeyName();
            $info['through_column_2'] = $relation->getSecondLocalKeyName();

        } else {
            return [
                'method_name' => $method->getName(),
                'error' => 'Unrecognized relation: '.$relation::class,
            ];
        }

        return $info;
    }

    private function usesSoftDeletes(Model $model): bool
    {
        return \in_array(SoftDeletes::class, class_uses_recursive($model));
    }

    private function methodIsRelation(ReflectionMethod $method): bool
    {
        if (! $method->isPublic()) {
            return false;
        }

        if ($method->getReturnType() instanceof ReflectionNamedType) {
            return str_starts_with($method->getReturnType()->getName(), 'Illuminate\Database\Eloquent\Relations\\');
        }

        // Le type de retour est précisé mais est composite donc on ne gère pas
        if ($method->getReturnType() instanceof ReflectionType) {
            return false;
        }

        if ($method->getDeclaringClass()->getName() === Model::class) {
            return false;
        }

        return preg_match(
            '/(\{|;)\s*return\s*\$this\s*->\s*(belongsTo|belongsToMany|HasMany|hasManyThrough|hasOne|hasOneThrough|morphTo|morphToMany|morphMany|morphOne|morphedByMany)\s*\(/',
            (string) $this->getMethodContentAsString($method)
        );
    }

    private function getMethodContentAsString(ReflectionMethod $method): ?string
    {
        $file = $method->getFileName();

        if ($file === false) {
            return null;
        }

        $start = $method->getStartLine();
        $end = $method->getEndLine();

        if ($start === false || $end === false) {
            return null;
        }

        $lines = file($file, FILE_IGNORE_NEW_LINES);

        if ($lines === false) {
            return null;
        }

        $lines = array_map(trim(...), \array_slice($lines, $start - 1, $end - $start + 1));
        $lines = array_filter($lines, fn (string $line): bool => ! preg_match('`^(//|/\*|\*|\*/)`', $line));

        return implode(' ', $lines);
    }

    /**
     * Retourne le FQCN du trait qui déclare réellement la méthode,
     * en gérant les alias (as) + traits imbriqués + héritage.
     *
     * Si la méthode est déclarée directement dans la classe (ou parent), retourne null.
     */
    private function getDeclaringTraitNameWithAliases(ReflectionMethod $method): ?string
    {
        $class = $method->getDeclaringClass();
        $methodName = $method->getName();

        // 1) Cas "alias": on remonte la hiérarchie des classes,
        // car les aliases peuvent être déclarés dans un parent.
        for ($c = $class; $c; $c = $c->getParentClass()) {
            $aliases = $c->getTraitAliases(); // [aliasName => "TraitFQCN::originalMethod"]

            if (isset($aliases[$methodName])) {
                $target = $aliases[$methodName]; // ex: "My\\Trait\\FooTrait::bar"
                $pos = strrpos($target, '::');

                if ($pos !== false) {
                    return substr($target, 0, $pos);
                }
            }
        }

        // 2) Cas non-aliasé: on cherche dans tous les traits (classe + parents),
        // y compris traits de traits.
        $traits = $this->getAllTraitsRecursiveIncludingParents($class);

        // D'abord on tente une correspondance par "même fichier + mêmes lignes"
        // (très utile en cas de conflits / resolution insteadof).
        $mFile = $method->getFileName();
        $mStart = $method->getStartLine();
        $mEnd = $method->getEndLine();

        if ($mFile !== false && $mStart !== false && $mEnd !== false) {
            foreach ($traits as $trait) {
                if (! $trait->hasMethod($methodName)) {
                    continue;
                }

                $tm = $trait->getMethod($methodName);

                // La méthode est bien déclarée dans ce trait (pas héritée dans un sous-trait)
                if ($tm->getDeclaringClass()->getName() !== $trait->getName()) {
                    continue;
                }

                if ($tm->getFileName() === $mFile
                    && $tm->getStartLine() === $mStart
                    && $tm->getEndLine() === $mEnd
                ) {
                    return $trait->getName();
                }
            }
        }

        // 3) Fallback: si pas de métadonnées fichier/lignes (ou pas de match),
        // on retourne le trait qui "déclare" la méthode.
        // Attention: en cas de conflits, plusieurs traits peuvent matcher,
        // mais généralement la résolution insteadof fait que celui réellement importé
        // est celui dont le code correspond (d'où l'étape 2).
        foreach ($traits as $trait) {
            if ($trait->hasMethod($methodName)) {
                $tm = $trait->getMethod($methodName);

                if ($tm->getDeclaringClass()->getName() === $trait->getName()) {
                    return $trait->getName();
                }
            }
        }

        return null;
    }

    /**
     * @return array<string,ReflectionClass> [traitFqcn => ReflectionClass]
     */
    private function getAllTraitsRecursiveIncludingParents(ReflectionClass $class): array
    {
        $out = [];

        for ($c = $class; $c; $c = $c->getParentClass()) {
            $stack = array_values($c->getTraits());

            while ($stack) {
                /** @var ReflectionClass $trait */
                $trait = array_pop($stack);
                $name = $trait->getName();

                if (isset($out[$name])) {
                    continue;
                }

                $out[$name] = $trait;

                foreach ($trait->getTraits() as $subTrait) {
                    $stack[] = $subTrait;
                }
            }
        }

        return $out;
    }
}
