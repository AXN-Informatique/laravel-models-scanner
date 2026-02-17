<?php

namespace Axn\ModelsScanner\Services;

use Illuminate\Support\Str;

final class ScanMerger
{
    public function __construct(
        private readonly DatabaseScanner $databaseScanner,
        private readonly ModelsScanner $modelsScanner,
    ) {}

    public function execute(): array
    {
        $databaseInfos = $this->databaseScanner->scan();
        $modelsInfos = $this->modelsScanner->scan();

        return $this->merge($databaseInfos, $modelsInfos);
    }

    private function merge(array $databaseInfos, array $modelsInfos): array
    {
        $mergedInfos = [];

        foreach ($databaseInfos as $tableName => $tableInfo) {
            if (empty($modelsInfos[$tableName])) {
                $mergedInfos[] = [
                    'table_name' => $tableName,
                ];

                continue;
            }

            foreach ($modelsInfos[$tableName] as $modelClass => $modelInfo) {
                $relations = $this->buildRelationsForModel($tableInfo, $modelInfo, $modelsInfos);

                $mergedInfos[] = [
                    'table_name' => $tableName,
                    'model_class' => $modelClass,
                    'relations' => $relations,
                    'soft_deletes' => $this->resolveSoftDeletes($tableInfo, $modelInfo),
                ];
            }
        }

        return $mergedInfos;
    }

    private function buildRelationsForModel(array $tableInfo, array $modelInfo, array $modelsInfos): array
    {
        $relations = [];
        $matchedModelRelationsIndexes = [];

        foreach ($tableInfo['relations'] as $tableRelation) {
            $matchedModelRelation = collect($modelInfo['relations'])
                ->where('type', $tableRelation['type'])
                ->where('local_column', $tableRelation['local_column'])
                ->where('foreign_table', $tableRelation['foreign_table'])
                ->where('foreign_column', $tableRelation['foreign_column']);

            if ($matchedModelRelation->count()) {
                $relations[] = [
                    'from_model' => true,
                    ...$tableRelation,
                    ...$matchedModelRelation->first(),
                ];

                $matchedModelRelationsIndexes[] = $matchedModelRelation->keys()->first();
                continue;
            }

            $relations[] = [
                'from_model' => false,
                'method_name' => $this->guessMethodName($tableRelation),
                'related_models' => $modelsInfos[$tableRelation['foreign_table']] ?? [],
                ...$tableRelation,
            ];
        }

        foreach ($modelInfo['relations'] as $modelRelationIndex => $modelRelation) {
            if (in_array($modelRelationIndex, $matchedModelRelationsIndexes, true)) {
                continue;
            }

            $relations[] = [
                'from_model' => true,
                ...$modelRelation,
            ];
        }

        return collect($relations)
            ->sortBy([
                ['from_model', 'desc'],
                ['method_name', 'asc'],
            ])
            ->values()
            ->all();
    }

    private function guessMethodName(array $tableRelation): string
    {
        if ($tableRelation['type'] === 'BelongsTo') {
            return Str::camel(
                preg_replace('/^id_|_id$|(_)id_/', '$1', $tableRelation['local_column'])
            );
        }

        return Str::camel($tableRelation['foreign_table']);
    }

    private function resolveSoftDeletes(array $tableInfo, array $modelInfo): ?bool
    {
        if ($modelInfo['deleted_at_column'] !== null) {
            return $tableInfo['manager']->hasColumn($modelInfo['deleted_at_column']);
        }

        if ($tableInfo['manager']->hasColumn('deleted_at')) {
            return false;
        }

        return null;
    }
}
