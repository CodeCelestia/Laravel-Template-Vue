import { createInertiaApp, router } from '@inertiajs/vue3';
import { initializeTheme } from '@/composables/useAppearance';
import { updateFaviconDOM } from '@/composables/useBranding';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { initializeFlashToast } from '@/lib/flashToast';

void createInertiaApp({
    title: (title, page) => {
        const branding = (page?.props?.branding as Record<string, any>) || {};
        const raw = branding.name || (page?.props?.name as string);
        const name = (raw && raw !== 'Laravel' && raw !== 'Laravel Vue Template')
            ? raw
            : 'PRPIMS-SDN';
        return title ? `${title} - ${name}` : name;
    },
    layout: (name) => {
        switch (true) {
            case name === 'Welcome':
                return null;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    progress: {
        color: '#4B5563',
    },
});

router.on('navigate', (event) => {
    const branding = (event.detail.page.props.branding as Record<string, any>) || {};
    if (branding.favicon) {
        updateFaviconDOM(branding.favicon);
    }
});

// This will set light / dark mode on page load...
initializeTheme();

// This will listen for flash toast data from the server...
initializeFlashToast();
