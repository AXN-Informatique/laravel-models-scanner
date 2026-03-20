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
    <form action="" style="margin-bottom: 20px">
        <input type="text" name="search" value="{{ $request->query('search') }}" size="30" placeholder="Rechercher">
        dans
        <select name="search_on" onchange="this.form.submit()">
            <option value="table" @selected(! $request->filled('search_on') || $request->query('search_on') === 'table')>Table</option>
            <option value="model" @selected($request->query('search_on') === 'model')>Modèle</option>
            <option value="relation_name" @selected($request->query('search_on') === 'relation_name')>Nom de relation</option>
            <option value="relation_model" @selected($request->query('search_on') === 'relation_model')>Modèle lié</option>
            <option value="relation_schema" @selected($request->query('search_on') === 'relation_schema')>Schéma de relation</option>
        </select>
        et filter sur
        <select name="filter" onchange="this.form.submit()">
            <option value=""></option>
            <option value="tables_without_model" @selected($request->query('filter') === 'tables_without_model')>Tables sans modèle</option>
            <option value="defined_relations" @selected($request->query('filter') === 'defined_relations')>Relations définies</option>
            <option value="undefined_relations" @selected($request->query('filter') === 'undefined_relations')>Relations non définies</option>
            <option value="untyped_relations" @selected($request->query('filter') === 'untyped_relations')>Relations non typées</option>
            <option value="errors" @selected($request->query('filter') === 'errors')>Erreurs</option>
        </select>

        @if ($request->anyFilled(['search', 'filter']))
            <a href="/_models">Réinitialiser</a>
        @endif

        <input type="submit" style="display:none">
    </form>

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
