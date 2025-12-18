<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    
    <!-- Prevent caching of HTML file -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    
    <!-- App version for auto-reload detection -->
    <meta name="app-version" content="<?php echo e(config('app.version', time())); ?>">
    
    <title>School Management System</title>

    <?php
        $faviconUrl = \DB::table('settings')->where('key', 'favicon_url')->value('value');
    ?>

    <?php if($faviconUrl): ?>
        <link rel="icon" type="image/x-icon" href="<?php echo e($faviconUrl); ?>">
    <?php endif; ?>

    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
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

<?php /**PATH /var/www/school/resources/views/app.blade.php ENDPATH**/ ?>