<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ config('app.name') }} :: Models</title>

    @include('models-scanner::partials.styles')
</head>
<body>
    @foreach ($mergedInfos as $mergedInfo)
        <div class="head">
            <strong>{!! $mergedInfo['table_name'] !!} :</strong>

            @isset ($mergedInfo['model_class'])
                <strong class="green">{!! $mergedInfo['model_class'] !!}</strong>
            @else
                <span class="muted">- Aucun modèle associé -</span>
            @endisset

            @isset ($mergedInfo['soft_deletes'])
                @if ($mergedInfo['soft_deletes'])
                    <span class="muted">(SoftDeletes)</span>
                @else
                    <span class="red">(SoftDeletes <strong>→ À VÉRIFIER</strong>)</span>
                @endif
            @endisset
        </div>

        <div class="body">
            @foreach ($mergedInfo['relations'] ?? [] as $relation)
                <div class="{!! $relation['from_model'] ? 'from-model' : 'from-db' !!}">
                    @isset ($relation['error'])
                        <div class="red">
                            <strong>{!! $relation['method_name'] !!}() :</strong>
                            <br>Erreur : {!! $relation['error'] !!}
                        </div>
                    @else
                        @if ($relation['from_model'])
                            @include('models-scanner::partials.relation-from-model')
                        @else
                            @include('models-scanner::partials.relation-from-db')
                        @endif

                        @include('models-scanner::partials.relation-schema')
                    @endisset
                </div>
            @endforeach
        </div>
    @endforeach

    @include('models-scanner::partials.scripts')
</body>
</html>
