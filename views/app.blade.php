<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>School Management System</title>

    @php
        $faviconUrl = \DB::table('settings')->where('key', 'favicon_url')->value('value');
    @endphp

    @if($faviconUrl)
        <link rel="icon" type="image/x-icon" href="{{ $faviconUrl }}">
    @endif

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased">
    <div id="app">
        <!-- Loading fallback -->
        <div class="min-h-screen bg-gray-100 flex items-center justify-center">
            <div class="text-center">
                <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mb-4"></div>
                <p class="text-gray-600">Loading...</p>
            </div>
        </div>
    </div>
    
    <script>
        // Debug: Check if Vue is loading
        window.addEventListener('error', function(e) {
            console.error('Error:', e.error);
        });
    </script>
</body>
</html>

