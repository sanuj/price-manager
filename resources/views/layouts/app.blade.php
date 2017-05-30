<!DOCTYPE html>
<html lang="{{ config('app.locale') }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name') }}</title>

    <!-- Styles -->
    <link href="{{ mix('/css/app.css') }}" rel="stylesheet">
    @if(file_exists(public_path('chunk-manifest.json')) and $chunks = json_decode(public_path('chunk-manifest.json'), true))
        @foreach($chunks as $chunk)
            <link rel="prefetch" href="{{ $chunk }}" as="script">
        @endforeach
    @endif

<!-- Scripts -->
    <script>
    window.Laravel = {!! json_encode([
            'csrfToken' => csrf_token(),
            'user' => Auth::user(),
        ]) !!};
    </script>
</head>
<body>
@yield('body')
</body>
</html>
