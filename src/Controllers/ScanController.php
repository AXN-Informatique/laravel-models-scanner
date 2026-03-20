<?php

namespace Axn\ModelsScanner\Controllers;

use Axn\ModelsScanner\Services\ScanMerger;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScanController
{
    public function __invoke(Request $request, ScanMerger $scanMerger): View
    {
        return view('models-scanner::scan', [
            'request' => $request,
            'mergedInfos' => $this->filter($scanMerger->execute(), $request),
        ]);
    }

    private function filter(array $mergedInfos, Request $request): array
    {
        if (! $request->filled('search') && ! $request->filled('filter')) {
            return $mergedInfos;
        }

        $search = $request->query('search');
        $searchOn = $request->query('search_on', 'table');
        $filter = $request->query('filter');

        return collect($mergedInfos)
            ->when($search !== null && $searchOn === 'table', function ($mergedInfos) use ($search) {
                return $mergedInfos->filter(fn (array $mergedInfo): bool|int => stripos($mergedInfo['table_name'], $search) !== false);
            })
            ->when($search !== null && $searchOn === 'model', function ($mergedInfos) use ($search) {
                return $mergedInfos->filter(fn (array $mergedInfo): bool|int => stripos($mergedInfo['model_class'] ?? '', $search) !== false);
            })
            ->when($search !== null && $searchOn === 'relation_name', function ($mergedInfos) use ($search) {
                return $mergedInfos
                    ->map(function ($mergedInfo) use ($search) {
                        $mergedInfo['relations'] = collect($mergedInfo['relations'] ?? [])
                            ->filter(fn (array $relation): bool|int => stripos($relation['method_name'], $search) !== false)
                            ->all();

                        return $mergedInfo;
                    })
                    ->filter(fn ($mergedInfo) => ! empty($mergedInfo['relations']));
            })
            ->when($search !== null && $searchOn === 'relation_model', function ($mergedInfos) use ($search) {
                return $mergedInfos
                    ->map(function ($mergedInfo) use ($search) {
                        $mergedInfo['relations'] = collect($mergedInfo['relations'] ?? [])
                            ->filter(fn (array $relation): bool|int => stripos($relation['related_model_class'] ?? '', $search) !== false)
                            ->all();

                        return $mergedInfo;
                    })
                    ->filter(fn ($mergedInfo) => ! empty($mergedInfo['relations']));
            })
            ->when($search !== null && $searchOn === 'relation_schema', function ($mergedInfos) use ($search) {
                return $mergedInfos
                    ->map(function ($mergedInfo) use ($search) {
                        $mergedInfo['relations'] = collect($mergedInfo['relations'] ?? [])
                            ->filter(fn (array $relation): bool|int =>
                                isset($relation['local_column']) && stripos($mergedInfo['table_name'].'.'.$relation['local_column'], $search) !== false
                                || isset($relation['foreign_table']) && stripos($relation['foreign_table'].'.'.$relation['foreign_column'], $search) !== false
                                || isset($relation['pivot_table']) && stripos($relation['pivot_table'].'.'.$relation['pivot_column_1'], $search) !== false
                                || isset($relation['pivot_table']) && stripos($relation['pivot_table'].'.'.$relation['pivot_column_2'], $search) !== false
                                || isset($relation['through_table']) && stripos($relation['through_table'].'.'.$relation['through_column_1'], $search) !== false
                                || isset($relation['through_table']) && stripos($relation['through_table'].'.'.$relation['through_column_2'], $search) !== false
                            )
                            ->all();

                        return $mergedInfo;
                    })
                    ->filter(fn ($mergedInfo) => ! empty($mergedInfo['relations']));
            })
            ->when($filter !== null, function ($mergedInfos) use ($filter) {
                return $mergedInfos
                    ->map(function ($mergedInfo) use ($filter) {
                        if (empty($mergedInfo['relations'])) {
                            return $mergedInfo;
                        }

                        $mergedInfo['relations'] = collect($mergedInfo['relations'])
                            ->filter(fn ($relation) =>
                                $filter === 'defined_relations' && $relation['from_model']
                                || $filter === 'undefined_relations' && ! $relation['from_model']
                                || $filter === 'untyped_relations' && ! empty($relation['miss_return_type'])
                                || $filter === 'errors' && ! empty($relation['error'])
                            )
                            ->all();

                        return $mergedInfo;
                    })
                    ->filter(fn ($mergedInfo) =>
                        ! empty($mergedInfo['relations'])
                        || $filter === 'tables_without_model' && empty($mergedInfo['model_class'])
                        || $filter === 'errors' && isset($mergedInfo['soft_deletes']) && empty($mergedInfo['soft_deletes'])
                    );
            })
            ->all();
    }
}
