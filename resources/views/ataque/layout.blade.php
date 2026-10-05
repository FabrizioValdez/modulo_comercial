<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Plan de ataque') · CRM</title>
</head>
<body class="atq-body">
    <header class="atq-top">
        <a href="{{ route('ataque.index') }}" class="atq-logo"><span class="atq-ibeam" aria-hidden="true"></span>ACEROS · CRM</a>
    </header>
    @yield('content')
</body>
</html>
