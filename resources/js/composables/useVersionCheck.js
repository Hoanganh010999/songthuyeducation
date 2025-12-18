import { ref, onMounted, onUnmounted } from 'vue';

export function useVersionCheck() {
    const currentVersion = ref(null);
    const checkInterval = ref(null);
    const isReloading = ref(false); // Flag to prevent multiple reload popups
    const CHECK_INTERVAL = 5 * 60 * 1000; // Check every 5 minutes

    const fetchVersion = async () => {
        try {
            // Add timestamp to prevent caching of version.json
            const response = await fetch(`/version.json?t=${Date.now()}`, {
                cache: 'no-cache',
                headers: {
                    'Cache-Control': 'no-cache',
                    'Pragma': 'no-cache'
                }
            });
            
            if (!response.ok) {
                console.warn('⚠️ Failed to fetch version info');
                return null;
            }
            
            const data = await response.json();
            return data.version;
        } catch (error) {
            console.warn('⚠️ Error checking version:', error.message);
            return null;
        }
    };

    const checkForUpdates = async () => {
        // Prevent checking if already reloading
        if (isReloading.value) {
            console.log('⏳ Already reloading, skipping version check...');
            return;
        }
        
        const latestVersion = await fetchVersion();
        
        if (!latestVersion) return;
        
        // First time - just store current version
        if (currentVersion.value === null) {
            currentVersion.value = latestVersion;
            console.log('✅ Current app version:', latestVersion);
            return;
        }
        
        // Check if version changed
        if (latestVersion !== currentVersion.value) {
            console.log('🔄 New version detected!');
            console.log('  Current:', currentVersion.value);
            console.log('  Latest:', latestVersion);
            
            // Show notification to user
            if (window.confirm('Có phiên bản mới! Bạn có muốn tải lại trang để cập nhật không?')) {
                // Set flag immediately to prevent multiple reloads
                isReloading.value = true;
                
                // Stop version checking
                stopVersionCheck();
                
                // Remove focus event listener
                window.removeEventListener('focus', checkForUpdates);
                
                console.log('🔄 Reloading page to apply updates...');
                
                // Clear all caches and reload
                if ('caches' in window) {
                    caches.keys().then(names => {
                        names.forEach(name => caches.delete(name));
                    }).finally(() => {
                        window.location.reload(true);
                    });
                } else {
                    window.location.reload(true);
                }
            } else {
                // Update stored version so we don't keep asking
                currentVersion.value = latestVersion;
            }
        }
    };

    const startVersionCheck = () => {
        // Initial check
        checkForUpdates();
        
        // Check periodically
        checkInterval.value = setInterval(() => {
            checkForUpdates();
        }, CHECK_INTERVAL);
        
        console.log(`✅ Version check started (interval: ${CHECK_INTERVAL / 60000} minutes)`);
    };

    const stopVersionCheck = () => {
        if (checkInterval.value) {
            clearInterval(checkInterval.value);
            checkInterval.value = null;
            console.log('✅ Version check stopped');
        }
    };

    // Auto-start when component mounts
    onMounted(() => {
        startVersionCheck();
        
        // Also check when window regains focus
        window.addEventListener('focus', checkForUpdates);
    });

    // Cleanup on unmount
    onUnmounted(() => {
        stopVersionCheck();
        window.removeEventListener('focus', checkForUpdates);
    });

    return {
        currentVersion,
        checkForUpdates,
        startVersionCheck,
        stopVersionCheck
    };
}

