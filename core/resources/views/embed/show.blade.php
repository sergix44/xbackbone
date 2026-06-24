<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $embed['title'] }} | {{ config('app.name') }}</title>
    @include('partials.social-embed', ['embed' => $embed])
</head>
<body>
    {{-- A minimal fallback so a misclassified human (or a bot that renders the
         body) still has a way to reach the actual preview page. --}}
    <a href="{{ $resource->preview_url }}">{{ $embed['title'] }}</a>
</body>
</html>
