<!DOCTYPE html>
<html>
<head>
    <title>Clear Translations Cache</title>
    <meta charset="UTF-8">
</head>
<body>
    <h1>Clear Translations Cache</h1>
    <p>Click the button below to clear localStorage translations cache:</p>
    <button id="clearCache" style="padding: 10px 20px; font-size: 16px; cursor: pointer;">
        Clear Translations Cache
    </button>
    <div id="result" style="margin-top: 20px; padding: 10px; display: none;"></div>

    <script>
        document.getElementById('clearCache').addEventListener('click', function() {
            // Clear all translations from localStorage
            const keys = Object.keys(localStorage);
            let cleared = 0;

            keys.forEach(key => {
                if (key.includes('translations') || key.includes('language')) {
                    localStorage.removeItem(key);
                    cleared++;
                }
            });

            // Show result
            const result = document.getElementById('result');
            result.style.display = 'block';
            result.style.backgroundColor = '#d4edda';
            result.style.color = '#155724';
            result.style.border = '1px solid #c3e6cb';
            result.innerHTML = `
                <strong>✅ Success!</strong><br>
                Cleared ${cleared} cache entries.<br>
                <strong>Please refresh the page now to reload translations.</strong>
            `;

            setTimeout(() => {
                window.location.reload();
            }, 2000);
        });
    </script>
</body>
</html>
