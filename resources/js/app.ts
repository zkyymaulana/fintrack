import './bootstrap';
import { createInertiaApp } from '@inertiajs/vue3';
import { createApp, h, type DefineComponent } from 'vue';

const pages = import.meta.glob<{ default: DefineComponent }>('./Pages/**/*.vue');

createInertiaApp({
    title: (title) => `${title} · FinTrack`,
    resolve: (name) => {
        const page = pages[`./Pages/${name}.vue`];

        if (!page) {
            throw new Error(`Halaman ${name} tidak ditemukan.`);
        }

        return page().then((module) => module.default);
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
    progress: { color: '#256958' },
});
