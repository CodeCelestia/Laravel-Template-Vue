<script setup lang="ts">
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import { Check, RotateCcw } from '@lucide/vue';
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import Heading from '@/components/Heading.vue';
import { useAppearance } from '@/composables/useAppearance';
import { edit } from '@/routes/appearance';
import type { SidebarColor, ThemeColor } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Appearance settings',
                href: edit(),
            },
        ],
    },
});

const {
    themeColor,
    sidebarColor,
    updateThemeColor,
    updateSidebarColor,
    resetSidebarColor,
} = useAppearance();

interface ThemeOption {
    value: ThemeColor;
    title: string;
    description: string;
    colors: [string, string, string];
}

interface SidebarOption {
    value: SidebarColor;
    title: string;
    description: string;
    colors: [string, string, string];
}

const themes: ThemeOption[] = [
    {
        value: 'ocean',
        title: 'Default — Ocean Blue',
        description: 'Clean enterprise blue for primary actions and focus rings.',
        colors: ['#0f172a', '#2563eb', '#60a5fa'],
    },
    {
        value: 'emerald',
        title: 'Emerald Green',
        description: 'Fresh green accents for a calm, productive admin feel.',
        colors: ['#064e3b', '#059669', '#6ee7b7'],
    },
    {
        value: 'purple',
        title: 'Royal Purple',
        description: 'Bold purple accents with a polished enterprise look.',
        colors: ['#2e1065', '#7c3aed', '#c4b5fd'],
    },
];

const activeThemeAccent = computed(() => {
    switch (themeColor.value) {
        case 'emerald':
            return '#059669';
        case 'purple':
            return '#7c3aed';
        case 'ocean':
        default:
            return '#2563eb';
    }
});

const sidebarColors = computed<SidebarOption[]>(() => [
    {
        value: 'mist-blue',
        title: 'Mist Blue',
        description: 'Clean & professional',
        colors: ['#E8F1FA', '#C9DFEF', activeThemeAccent.value],
    },
    {
        value: 'soft-sage',
        title: 'Soft Sage',
        description: 'Fresh & subtle',
        colors: ['#EAF5EF', '#C7E5D3', activeThemeAccent.value],
    },
    {
        value: 'soft-lavender',
        title: 'Soft Lavender',
        description: 'Modern & elegant',
        colors: ['#F1EDFA', '#DDD4F5', activeThemeAccent.value],
    },
]);
</script>

<template>
    <Head title="Appearance settings" />

    <h1 class="sr-only">Appearance settings</h1>

    <div class="space-y-10">
        <!-- Page Title & Description -->
        <Heading
            variant="small"
            title="Appearance settings"
            description="Update the appearance settings for your account"
        />

        <!-- Section 1: Color Mode -->
        <div class="space-y-3">
            <h3 class="text-sm font-semibold text-foreground">Color mode</h3>
            <AppearanceTabs />
        </div>

        <!-- Section 2: Theme -->
        <div class="space-y-3.5">
            <div>
                <h3 class="text-sm font-semibold text-foreground">Theme</h3>
                <p class="text-xs text-muted-foreground mt-0.5">
                    Choose one of our enterprise color themes. Preference is saved on this device.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3">
                <button
                    v-for="item in themes"
                    :key="item.value"
                    type="button"
                    @click="updateThemeColor(item.value)"
                    :class="[
                        'group relative flex flex-col rounded-2xl border-2 p-4 text-left transition-all duration-200 cursor-pointer bg-card outline-hidden',
                        themeColor === item.value
                            ? 'border-primary ring-2 ring-primary/20 shadow-xs'
                            : 'border-border/80 hover:border-border hover:shadow-2xs dark:border-border/70',
                    ]"
                >
                    <!-- Preview Swatch Bar -->
                    <div
                        class="relative mb-3.5 flex h-14 w-full overflow-hidden rounded-xl border border-black/5 dark:border-white/10 shadow-2xs"
                    >
                        <div
                            v-for="(color, index) in item.colors"
                            :key="index"
                            class="h-full flex-1"
                            :style="{ backgroundColor: color }"
                        />
                        <!-- Active Checkmark Badge -->
                        <div
                            v-if="themeColor === item.value"
                            class="absolute top-2.5 right-2.5 flex h-5 w-5 items-center justify-center rounded-full bg-primary text-primary-foreground shadow-xs"
                        >
                            <Check class="h-3 w-3 stroke-[3]" />
                        </div>
                    </div>

                    <!-- Card Title & Description -->
                    <div class="flex flex-col">
                        <span class="text-sm font-semibold text-foreground tracking-tight">
                            {{ item.title }}
                        </span>
                        <span class="text-xs text-muted-foreground mt-1 leading-relaxed">
                            {{ item.description }}
                        </span>
                    </div>
                </button>
            </div>
        </div>

        <!-- Section 3: Sidebar Color -->
        <div class="space-y-3.5">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h3 class="text-sm font-semibold text-foreground">
                        Sidebar color
                    </h3>
                    <p class="text-xs text-muted-foreground mt-0.5">
                        Customize the sidebar color while keeping your active theme accents consistent.
                    </p>
                </div>

                <button
                    type="button"
                    @click="resetSidebarColor"
                    title="Reset to default sidebar color"
                    class="inline-flex shrink-0 items-center gap-1.5 rounded-lg border border-border/80 bg-card px-2.5 py-1 text-xs font-medium text-muted-foreground hover:bg-accent hover:text-foreground transition-all shadow-2xs cursor-pointer"
                >
                    <RotateCcw class="h-3 w-3" />
                    <span>Reset</span>
                </button>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3">
                <button
                    v-for="item in sidebarColors"
                    :key="item.value"
                    type="button"
                    @click="updateSidebarColor(item.value)"
                    :class="[
                        'group relative flex flex-col rounded-2xl border-2 p-4 text-left transition-all duration-200 cursor-pointer bg-card outline-hidden',
                        sidebarColor === item.value
                            ? 'border-primary ring-2 ring-primary/20 shadow-xs'
                            : 'border-border/80 hover:border-border hover:shadow-2xs dark:border-border/70',
                    ]"
                >
                    <!-- Preview Swatch Bar -->
                    <div
                        class="relative mb-3.5 flex h-14 w-full overflow-hidden rounded-xl border border-black/5 dark:border-white/10 shadow-2xs"
                    >
                        <div
                            v-for="(color, index) in item.colors"
                            :key="index"
                            class="h-full flex-1"
                            :style="{ backgroundColor: color }"
                        />
                        <!-- Active Checkmark Badge -->
                        <div
                            v-if="sidebarColor === item.value"
                            class="absolute top-2.5 right-2.5 flex h-5 w-5 items-center justify-center rounded-full bg-primary text-primary-foreground shadow-xs"
                        >
                            <Check class="h-3 w-3 stroke-[3]" />
                        </div>
                    </div>

                    <!-- Card Title & Description -->
                    <div class="flex flex-col">
                        <span class="text-sm font-semibold text-foreground tracking-tight">
                            {{ item.title }}
                        </span>
                        <span class="text-xs text-muted-foreground mt-1 leading-relaxed">
                            {{ item.description }}
                        </span>
                    </div>
                </button>
            </div>
        </div>
    </div>
</template>
