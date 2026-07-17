import '../css/app.css';

import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import { initializeTheme } from './composables/useAppearance';

declare module 'vite/client' {
    interface ImportMetaEnv {
        readonly VITE_APP_NAME: string;
        [key: string]: string | boolean | undefined;
    }

    interface ImportMeta {
        readonly env: ImportMetaEnv;
        readonly glob: <T>(pattern: string) => Record<string, () => Promise<T>>;
    }
}

const fallbackTitleSuffix = import.meta.env.VITE_APP_NAME || 'Laravel';
let titleSuffix = fallbackTitleSuffix;

function resolveTitleSuffix(pageProps: Record<string, unknown>): void {
    const store = pageProps.store as { seo?: { title_suffix?: string } } | undefined;
    titleSuffix = store?.seo?.title_suffix || fallbackTitleSuffix;
}

createInertiaApp({
    title: (title) => (title ? `${title} - ${titleSuffix}` : titleSuffix),
    resolve: (name) => resolvePageComponent(`./pages/${name}.vue`, import.meta.glob<DefineComponent>('./pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        resolveTitleSuffix(props.initialPage.props);

        router.on('navigate', (event) => {
            resolveTitleSuffix(event.detail.page.props);
        });

        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

initializeTheme();
