export type Appearance = 'light' | 'dark' | 'system';
export type ResolvedAppearance = 'light' | 'dark';
export type ThemeColor = 'ocean' | 'emerald' | 'purple';
export type SidebarColor = 'mist-blue' | 'soft-sage' | 'soft-lavender';

export type AppVariant = 'header' | 'sidebar';

export type FlashToast = {
    type: 'success' | 'info' | 'warning' | 'error';
    message: string;
};

export type Branding = {
    name: string;
    logo: string;
    favicon: string;
    has_custom_logo: boolean;
    has_custom_favicon: boolean;
};

