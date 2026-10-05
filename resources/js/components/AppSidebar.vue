<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import {
    LayoutGrid,
    MapPin,
    NotebookPen,
    ShieldCheck,
    Users,
} from '@lucide/vue';
import { index as usersIndex } from '@/actions/App/Http/Controllers/UserController';
import { index as rolesIndex } from '@/actions/App/Http/Controllers/RoleController';
import { index as municipalitiesIndex } from '@/actions/App/Http/Controllers/MunicipalityController';
import { index as notesIndex } from '@/actions/App/Http/Controllers/NoteController';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarHeader,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const page = usePage();

const baseNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Municipalities',
        href: municipalitiesIndex.url(),
        icon: MapPin,
    },
    {
        title: 'Users',
        href: usersIndex.url(),
        icon: Users,
    },
    {
        title: 'Roles & Permissions',
        href: rolesIndex.url(),
        icon: ShieldCheck,
    },
];

const hasNotesAccess = computed(() => {
    const auth = page.props.auth as any;
    if (!auth?.user) {
        return false;
    }
    if (auth.can?.viewNotes) {
        return true;
    }
    if (auth.permissions?.includes('notes.view')) {
        return true;
    }
    if (
        auth.roles?.includes('System Admin') ||
        auth.roles?.includes('Developer')
    ) {
        return true;
    }
    return false;
});

const navItems = computed<NavItem[]>(() => {
    const items = [...baseNavItems];
    if (hasNotesAccess.value) {
        items.push({
            title: 'Open Notes',
            href: notesIndex.url(),
            icon: NotebookPen,
        });
    }
    return items;
});
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader
            class="border-b border-sidebar-border/70 pb-4 pt-3 px-2 group-data-[collapsible=icon]:p-2 group-data-[collapsible=icon]:border-b-0"
        >
            <Link
                :href="dashboard()"
                class="group flex w-full flex-col items-center justify-center rounded-xl p-2 transition-all duration-200 hover:bg-sidebar-accent/50 focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-sidebar-ring group-data-[collapsible=icon]:p-0"
                title="Dashboard"
            >
                <AppLogo orientation="vertical" />
            </Link>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="navItems" />
        </SidebarContent>
    </Sidebar>
    <slot />
</template>
