// import './bootstrap';
// import '../css/app.css';

// import { createApp, h } from 'vue';
// import { createInertiaApp } from '@inertiajs/vue3';
// import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
// import { ZiggyVue } from '../../vendor/tightenco/ziggy';
// import { initializeColorAdmin } from './colorAdminInit';

// const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// createInertiaApp({
//     title: (title) => `${title} - ${appName}`,
//     resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
//     setup({ el, App, props, plugin }) {
//         const vueApp = createApp({ render: () => h(App, props) })
//             .use(plugin)
//             .use(ZiggyVue);

//         vueApp.mount(el);

//         // Initialize Color Admin scripts after Vue app is mounted
//         initializeColorAdmin();

//         // Reinitialize scripts on every Inertia navigation
//         Inertia.on('navigate', () => {
//             console.log("Inertia navigation detected, reinitializing Color Admin...");
//             initializeColorAdmin();
//         });
//     },
//     progress: {
//         color: '#4B5563',
//     },
// });


import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import { initializeColorAdmin } from './colorAdminInit';


const appName = import.meta.env.VITE_APP_NAME || 'Laravel';


createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const vueApp = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue);

        vueApp.mount(el);

        // Initialize Color Admin scripts after Vue app is mounted
        initializeColorAdmin();

    },
    progress: false,
});

