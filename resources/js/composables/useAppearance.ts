import type { ComputedRef, Ref } from 'vue';
import { computed, onMounted, ref } from 'vue';
import type {
    Appearance,
    ResolvedAppearance,
    SidebarColor,
    ThemeColor,
} from '@/types';

export type { Appearance, ResolvedAppearance, SidebarColor, ThemeColor };

export type UseAppearanceReturn = {
    appearance: Ref<Appearance>;
    resolvedAppearance: ComputedRef<ResolvedAppearance>;
    updateAppearance: (value: Appearance) => void;
    themeColor: Ref<ThemeColor>;
    sidebarColor: Ref<SidebarColor>;
    updateThemeColor: (value: ThemeColor) => void;
    updateSidebarColor: (value: SidebarColor) => void;
    resetSidebarColor: () => void;
};

export function updateTheme(value: Appearance): void {
    if (typeof window === 'undefined') {
        return;
    }

    if (value === 'system') {
        const mediaQueryList = window.matchMedia(
            '(prefers-color-scheme: dark)',
        );
        const systemTheme = mediaQueryList.matches ? 'dark' : 'light';

        document.documentElement.classList.toggle(
            'dark',
            systemTheme === 'dark',
        );
    } else {
        document.documentElement.classList.toggle('dark', value === 'dark');
    }
}

export function updateThemeColorDOM(value: ThemeColor): void {
    if (typeof document === 'undefined') {
        return;
    }
    document.documentElement.setAttribute('data-theme', value);
}

export function updateSidebarColorDOM(value: SidebarColor): void {
    if (typeof document === 'undefined') {
        return;
    }
    document.documentElement.setAttribute('data-sidebar', value);
}

const setCookie = (name: string, value: string, days = 365) => {
    if (typeof document === 'undefined') {
        return;
    }

    const maxAge = days * 24 * 60 * 60;

    document.cookie = `${name}=${value};path=/;max-age=${maxAge};SameSite=Lax`;
};

const mediaQuery = () => {
    if (typeof window === 'undefined') {
        return null;
    }

    return window.matchMedia('(prefers-color-scheme: dark)');
};

const getStoredAppearance = () => {
    if (typeof window === 'undefined') {
        return null;
    }

    return localStorage.getItem('appearance') as Appearance | null;
};

const getStoredThemeColor = (): ThemeColor => {
    if (typeof window === 'undefined') {
        return 'ocean';
    }

    return (localStorage.getItem('theme_color') as ThemeColor) || 'ocean';
};

const getStoredSidebarColor = (): SidebarColor => {
    if (typeof window === 'undefined') {
        return 'mist-blue';
    }

    const val = localStorage.getItem('sidebar_color');
    if (val === 'mist-blue' || val === 'soft-sage' || val === 'soft-lavender') {
        return val;
    }

    return 'mist-blue';
};

const prefersDark = (): boolean => {
    if (typeof window === 'undefined') {
        return false;
    }

    return window.matchMedia('(prefers-color-scheme: dark)').matches;
};

const handleSystemThemeChange = () => {
    const currentAppearance = getStoredAppearance();

    updateTheme(currentAppearance || 'system');
};

export function initializeTheme(): void {
    if (typeof window === 'undefined') {
        return;
    }

    // Initialize theme from saved preference or default to system...
    const savedAppearance = getStoredAppearance();
    updateTheme(savedAppearance || 'system');

    // Initialize theme color and sidebar color...
    const savedTheme = getStoredThemeColor();
    updateThemeColorDOM(savedTheme);

    const savedSidebar = getStoredSidebarColor();
    updateSidebarColorDOM(savedSidebar);

    // Set up system theme change listener...
    mediaQuery()?.addEventListener('change', handleSystemThemeChange);
}

const appearance = ref<Appearance>('system');
const themeColor = ref<ThemeColor>('ocean');
const sidebarColor = ref<SidebarColor>('mist-blue');

export function useAppearance(): UseAppearanceReturn {
    onMounted(() => {
        const savedAppearance = localStorage.getItem(
            'appearance',
        ) as Appearance | null;

        if (savedAppearance) {
            appearance.value = savedAppearance;
        }

        const savedTheme = localStorage.getItem(
            'theme_color',
        ) as ThemeColor | null;

        if (savedTheme) {
            themeColor.value = savedTheme;
            updateThemeColorDOM(savedTheme);
        }

        const savedSidebar = localStorage.getItem(
            'sidebar_color',
        ) as SidebarColor | null;

        if (savedSidebar) {
            sidebarColor.value = savedSidebar;
            updateSidebarColorDOM(savedSidebar);
        }
    });

    const resolvedAppearance = computed<ResolvedAppearance>(() => {
        if (appearance.value === 'system') {
            return prefersDark() ? 'dark' : 'light';
        }

        return appearance.value;
    });

    function updateAppearance(value: Appearance) {
        appearance.value = value;

        // Store in localStorage for client-side persistence...
        localStorage.setItem('appearance', value);

        // Store in cookie for SSR...
        setCookie('appearance', value);

        updateTheme(value);
    }

    function updateThemeColor(value: ThemeColor) {
        themeColor.value = value;
        localStorage.setItem('theme_color', value);
        setCookie('theme_color', value);
        updateThemeColorDOM(value);
    }

    function updateSidebarColor(value: SidebarColor) {
        sidebarColor.value = value;
        localStorage.setItem('sidebar_color', value);
        setCookie('sidebar_color', value);
        updateSidebarColorDOM(value);
    }

    function resetSidebarColor() {
        updateSidebarColor('mist-blue');
    }

    return {
        appearance,
        resolvedAppearance,
        updateAppearance,
        themeColor,
        sidebarColor,
        updateThemeColor,
        updateSidebarColor,
        resetSidebarColor,
    };
}

