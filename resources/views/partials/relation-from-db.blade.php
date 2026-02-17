@foreach ($relation['related_models'] as $relatedModelClass => $relatedModel)
    <div class="{!! $relation['type'] !!}" data-relation-code="
    public function {!! $relation['method_name'] !!}(): {!! $relation['type'] !!}
    {
@if ($relation['type'] === 'BelongsTo')
        return $this->belongsTo(\{!! $relatedModelClass !!}::class, '{!! $relation['local_column'] !!}'{!! $relation['foreign_column'] === 'id' ? '' : ', \''.$relation['foreign_column'].'\'' !!})@isset ($relatedModel['deleted_at_column'])->withTrashed()@endisset;
@else
        return $this->hasMany(\{!! $relatedModelClass !!}::class, '{!! $relation['foreign_column'] !!}'{!! $relation['local_column'] === 'id' ? '' : ', \''.$relation['local_column'].'\'' !!});
@endif
    }"
    >
        <strong>{!! $relation['method_name'] !!}() :</strong>
        {!! $relation['type'] !!}
        → <strong>{!! $relatedModelClass !!}</strong>
    </div>
@endforeach
