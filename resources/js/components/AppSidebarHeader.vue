<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { ChevronDown, Moon, Sun } from '@lucide/vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { useAppearance } from '@/composables/useAppearance';
import { useInitials } from '@/composables/useInitials';
import type { BreadcrumbItem } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const page = usePage();
const user = computed(() => page.props.auth.user);

const { updateAppearance } = useAppearance();
const { getInitials } = useInitials();

const isDark = ref(false);

onMounted(() => {
    isDark.value = document.documentElement.classList.contains('dark');
    const observer = new MutationObserver(() => {
        isDark.value = document.documentElement.classList.contains('dark');
    });
    observer.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['class'],
    });
});

function toggleTheme() {
    const nextTheme = isDark.value ? 'light' : 'dark';
    updateAppearance(nextTheme);
    isDark.value = nextTheme === 'dark';
}
</script>

<template>
    <header
        class="border-sidebar-border/70 flex h-16 shrink-0 items-center justify-between gap-4 border-b px-4 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-6"
    >
        <!-- Left: Sidebar Trigger & Breadcrumbs -->
        <div class="flex items-center gap-2 min-w-0">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>

        <!-- Right: Appearance Toggle & User Profile Dropdown -->
        <div v-if="user" class="flex items-center gap-3 shrink-0">
            <!-- Appearance Toggle Button -->
            <button
                type="button"
                @click="toggleTheme"
                :title="isDark ? 'Switch to light theme' : 'Switch to dark theme'"
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-border/70 bg-card text-foreground transition-all duration-200 hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring shadow-xs cursor-pointer"
            >
                <Sun v-if="isDark" class="h-4 w-4 transition-transform duration-300 hover:rotate-45" />
                <Moon v-else class="h-4 w-4 transition-transform duration-300 hover:-rotate-12" />
                <span class="sr-only">Toggle theme</span>
            </button>

            <!-- User Profile Dropdown -->
            <DropdownMenu>
                <DropdownMenuTrigger
                    class="flex items-center gap-3 rounded-2xl border border-border/70 bg-card px-3 py-1.5 text-left transition-all duration-200 hover:bg-accent hover:border-border/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring shadow-xs cursor-pointer select-none"
                >
                    <div
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-[10px] bg-primary font-semibold text-xs text-primary-foreground shadow-xs"
                    >
                        {{ getInitials(user.name) }}
                    </div>

                    <div class="flex flex-col min-w-0 pr-1 text-left">
                        <span class="truncate text-xs font-semibold text-foreground leading-tight">
                            {{ user.name }}
                        </span>
                        <span class="truncate text-[11.5px] text-muted-foreground leading-tight">
                            {{ user.email }}
                        </span>
                    </div>

                    <ChevronDown
                        class="h-4 w-4 shrink-0 text-muted-foreground transition-transform duration-200"
                    />
                </DropdownMenuTrigger>

                <DropdownMenuContent
                    align="end"
                    class="w-56 rounded-xl p-1.5 shadow-lg"
                    :side-offset="6"
                >
                    <UserMenuContent :user="user" />
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    </header>
</template>
