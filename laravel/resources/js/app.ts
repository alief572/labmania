import '../css/app.css';
import { createInertiaApp } from '@inertiajs/vue3';
import { createApp, h, type DefineComponent } from 'vue';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { installSearchSelect } from './composables/searchSelect';

installSearchSelect();

createInertiaApp({
    title: (title) => title ? `${title} | Labmania` : 'Labmania',
    resolve: (name) => resolvePageComponent<DefineComponent>(`./pages/${name}.vue`, import.meta.glob<DefineComponent>('./pages/**/*.vue')),
    setup: ({ el, App, props, plugin }) => createApp({ render: () => h(App, props) }).use(plugin).mount(el),
});
