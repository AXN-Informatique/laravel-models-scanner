<div class="indent">
    <strong>{!! $mergedInfo['table_name'] !!}</strong>.{!! $relation['local_column'] !!}

    @switch ($relation['type'])
        @case('BelongsTo')
        @case('MorphTo')
            →
            @break
        @case('HasMany')
        @case('HasOne')
        @case('MorphMany')
        @case('MorphOne')
            ←
            @break
        @case('BelongsToMany')
        @case('MorphToMany')
            ←
            <strong>{!! $relation['pivot_table'] !!}</strong>
            ({!! $relation['pivot_column_1'] !!}, {!! $relation['pivot_column_2'] !!})
            →
            @break
        @case('HasManyThrough')
            ←
            <strong>{!! $relation['through_table'] !!}</strong>
            ({!! $relation['through_column_1'] !!}, {!! $relation['through_column_2'] !!})
            ←
            @break
    @endswitch

    <strong>{!! $relation['foreign_table'] ?? '{'.$relation['morph_type'].'}' !!}</strong>.{!! $relation['foreign_column'] !!}

    @isset ($relation['constraint'])
        <div class="muted">♦ {!! $relation['constraint'] !!}</div>
    @endisset
</div>
