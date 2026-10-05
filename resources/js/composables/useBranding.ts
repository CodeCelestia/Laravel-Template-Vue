import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import type { Branding } from '@/types';

export function updateFaviconDOM(faviconUrl?: string | null): void {
    if (typeof document === 'undefined') {
        return;
    }

    const target = faviconUrl || '/favicon.ico';
    const iconLinks = document.querySelectorAll<HTMLLinkElement>("link[rel~='icon'], link[rel='apple-touch-icon']");

    if (iconLinks.length > 0) {
        iconLinks.forEach((link) => {
            link.href = target;
        });
    } else {
        const link = document.createElement('link');
        link.rel = 'icon';
        link.href = target;
        document.head.appendChild(link);
    }
}

export function useBranding() {
    const page = usePage();

    const branding = computed<Branding>(() => {
        const raw = (page.props.branding as Record<string, any>) || {};
        return {
            name: raw.name || (page.props.name as string) || 'PRPIMS-SDN',
            logo: raw.logo || '/images/sdn-logo.png',
            favicon: raw.favicon || '/favicon.ico',
            has_custom_logo: Boolean(raw.has_custom_logo),
            has_custom_favicon: Boolean(raw.has_custom_favicon),
        };
    });

    const appName = computed(() => branding.value.name);
    const logoUrl = computed(() => branding.value.logo);
    const faviconUrl = computed(() => branding.value.favicon);

    return {
        branding,
        appName,
        logoUrl,
        faviconUrl,
        updateFaviconDOM,
    };
}
