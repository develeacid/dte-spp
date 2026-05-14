<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Portal de datos abiertos · DTE-SPP">
    <title>@yield('title', 'Portal de Transparencia') · DTE-SPP</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/portal.css', 'resources/js/portal.js'])
</head>
<body class="font-sans antialiased min-h-screen flex flex-col bg-portal-bg text-portal-text">
    <header class="bg-white border-b border-portal-border">
        <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
            <a href="{{ route('portal.index') }}" class="flex items-center gap-3">
                <span class="text-lg font-bold">Portal de Transparencia</span>
                <span class="text-sm text-portal-muted">· DTE-SPP</span>
            </a>
            <nav class="text-sm text-portal-muted">
                <a href="{{ route('portal.index') }}" class="hover:text-portal-text">Datasets</a>
            </nav>
        </div>
    </header>
    <main class="flex-1">
        <div class="max-w-6xl mx-auto px-6 py-8">
            @yield('content')
        </div>
    </main>
    <footer class="bg-white border-t border-portal-border py-6">
        <div class="max-w-6xl mx-auto px-6 text-sm text-portal-muted flex justify-between">
            <span>&copy; {{ date('Y') }} · Datos abiertos del Estado de Oaxaca</span>
            <span>Generado por DTE-SPP {{ date('Y') }}</span>
        </div>
    </footer>
</body>
</html>
