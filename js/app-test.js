import './bootstrap';
import { createApp } from 'vue';

const app = createApp({
    template: `
        <div class="min-h-screen bg-gray-100 flex items-center justify-center">
            <div class="bg-white p-8 rounded-lg shadow-lg">
                <h1 class="text-2xl font-bold text-blue-600 mb-4">Vue App Works!</h1>
                <p class="text-gray-600">If you see this, Vue is working correctly.</p>
            </div>
        </div>
    `
});

app.mount('#app');

