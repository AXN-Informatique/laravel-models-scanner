<div class="{!! $relation['type'] !!}">
    <strong>{!! $relation['method_name'] !!}() :</strong>
    <span class="{!! $relation['miss_return_type'] ? 'miss-return-type' : '' !!}">{!! $relation['type'] !!}</span>
    → <strong>{!! $relation['related_model_class'] ?? '{'.$relation['morph_type'].'}' !!}</strong>

    @isset ($relation['with_trashed'])
        @if ($relation['with_trashed'])
            <span class="with-trashed">(withTrashed)</span>
        @else
            <span class="without-trashed">(withoutTrashed)</span>
        @endif
    @endisset
</div>

<div class="additionnal-info">
    @if ($relation['declaration_source'] !== $mergedInfo['model_class'])
        <div class="declaration-source">
            {!! $relation['declaration_source'] !!}
        </div>
    @endif
</div>
