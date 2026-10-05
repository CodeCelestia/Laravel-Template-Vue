<script setup lang="ts">
import { computed, h, ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import {
    getCoreRowModel,
    useVueTable,
    type ColumnDef,
} from '@tanstack/vue-table';
import {
    Calendar,
    Filter,
    KeyRound,
    Plus,
    RotateCcw,
    Search,
    Shield,
    ShieldCheck,
    Users,
} from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    DataTable,
    DataTableColumnHeader,
    DataTablePagination,
} from '@/components/ui/data-table';
import { index as rolesIndex } from '@/actions/App/Http/Controllers/RoleController';
import { index as usersIndex } from '@/actions/App/Http/Controllers/UserController';
import { dashboard } from '@/routes';
import RoleTableRowActions, {
    type RoleItem,
} from './RoleTableRowActions.vue';
import RoleViewDialog from './RoleViewDialog.vue';
import RoleFormDialog, {
    type AvailablePermission,
} from './RoleFormDialog.vue';
import RoleDeleteDialog from './RoleDeleteDialog.vue';
import PermissionTableRowActions, {
    type PermissionItem,
} from './PermissionTableRowActions.vue';
import PermissionFormDialog from './PermissionFormDialog.vue';
import PermissionDeleteDialog from './PermissionDeleteDialog.vue';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
            {
                title: 'Roles & Permissions',
                href: rolesIndex.url(),
            },
        ],
    },
});

interface RolesPagination {
    data: RoleItem[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
}

interface FilterState {
    search: string;
    sort: string;
    direction: 'asc' | 'desc';
    per_page: number;
    tab: 'roles' | 'permissions';
}

interface SimpleRole {
    id: number;
    name: string;
}

const props = defineProps<{
    roles: RolesPagination;
    permissions: PermissionItem[];
    all_roles: SimpleRole[];
    filters: FilterState;
}>();

// Active Tab state
const currentTab = ref<'roles' | 'permissions'>(props.filters.tab || 'roles');

// Search & filter states
const searchQuery = ref(props.filters.search || '');
const permissionSearchQuery = ref('');
const selectedModule = ref('all');
const isLoading = ref(false);

// Modal states for Roles
const isRoleFormOpen = ref(false);
const isRoleViewOpen = ref(false);
const isRoleDeleteOpen = ref(false);
const selectedRole = ref<RoleItem | null>(null);

// Modal states for Permissions
const isPermissionFormOpen = ref(false);
const isPermissionDeleteOpen = ref(false);
const selectedPermission = ref<PermissionItem | null>(null);

// Role badge styling
function getRoleBadgeClass(roleName: string) {
    switch (roleName) {
        case 'System Admin':
            return 'bg-rose-500/10 text-rose-700 dark:text-rose-400 border-rose-500/20';
        case 'User':
            return 'bg-blue-500/10 text-blue-700 dark:text-blue-400 border-blue-500/20';
        default:
            return 'bg-primary/10 text-primary border-primary/20';
    }
}

function formatDate(dateStr?: string | null) {
    if (!dateStr) return 'N/A';
    return new Date(dateStr).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

function getPermissionModule(name: string): string {
    const prefix = name.split('.')[0] || 'other';
    switch (prefix) {
        case 'users':
            return 'User Management';
        case 'roles':
            return 'Role Security';
        case 'permissions':
            return 'Permission Catalog';
        case 'municipalities':
            return 'Municipalities & Cities';
        case 'notes':
            return 'Developer Open Notes';
        default:
            return prefix.toUpperCase();
    }
}

// Navigation & Filters
function applyFilters(updates: Partial<FilterState> & { page?: number } = {}) {
    isLoading.value = true;
    const queryParams: Record<string, any> = {
        search: searchQuery.value || undefined,
        sort: props.filters.sort,
        direction: props.filters.direction,
        per_page: props.filters.per_page,
        page: props.roles.current_page,
        tab: currentTab.value,
        ...updates,
    };

    router.get(rolesIndex.url(), queryParams, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onFinish: () => {
            isLoading.value = false;
        },
    });
}

const debouncedSearch = useDebounceFn(() => {
    applyFilters({ page: 1 });
}, 350);

watch(searchQuery, () => {
    debouncedSearch();
});

function switchTab(tab: 'roles' | 'permissions') {
    currentTab.value = tab;
    searchQuery.value = '';
    applyFilters({ tab, search: undefined, page: 1 });
}

function resetRoleFilters() {
    searchQuery.value = '';
    applyFilters({
        search: undefined,
        sort: 'name',
        direction: 'asc',
        page: 1,
    });
}

function handleSort(column: string, direction: 'asc' | 'desc' | null) {
    if (!direction) {
        applyFilters({ sort: 'name', direction: 'asc', page: 1 });
    } else {
        applyFilters({ sort: column, direction, page: 1 });
    }
}

function getSortDirection(column: string): 'asc' | 'desc' | null {
    if (props.filters.sort === column) {
        return props.filters.direction;
    }
    return null;
}

// Role modal triggers
function openCreateRoleModal() {
    selectedRole.value = null;
    isRoleFormOpen.value = true;
}

function openViewRoleModal(role: RoleItem) {
    selectedRole.value = role;
    isRoleViewOpen.value = true;
}

function openEditRoleModal(role: RoleItem) {
    selectedRole.value = role;
    isRoleViewOpen.value = false;
    isRoleFormOpen.value = true;
}

function openDeleteRoleModal(role: RoleItem) {
    selectedRole.value = role;
    isRoleDeleteOpen.value = true;
}

// Permission modal triggers
function openCreatePermissionModal() {
    selectedPermission.value = null;
    isPermissionFormOpen.value = true;
}

function openEditPermissionModal(permission: PermissionItem) {
    selectedPermission.value = permission;
    isPermissionFormOpen.value = true;
}

function openDeletePermissionModal(permission: PermissionItem) {
    selectedPermission.value = permission;
    isPermissionDeleteOpen.value = true;
}

// TanStack Table Column Definitions for ROLES
const roleColumns: ColumnDef<RoleItem>[] = [
    {
        accessorKey: 'name',
        header: () =>
            h(DataTableColumnHeader, {
                title: 'Role Name',
                sortDirection: getSortDirection('name'),
                onSort: (dir: 'asc' | 'desc' | null) => handleSort('name', dir),
            }),
        cell: ({ row }) => {
            const role = row.original;
            return h('div', { class: 'flex items-center space-x-3' }, [
                h(
                    'div',
                    {
                        class: `flex h-9 w-9 shrink-0 items-center justify-center rounded-xl font-semibold text-xs border ${getRoleBadgeClass(role.name)}`,
                    },
                    [h(Shield, { class: 'h-4 w-4' })],
                ),
                h('div', { class: 'flex flex-col' }, [
                    h('div', { class: 'flex items-center gap-1.5' }, [
                        h(
                            'span',
                            { class: 'font-semibold text-foreground text-sm' },
                            role.name,
                        ),
                        role.name === 'System Admin'
                            ? h(
                                  Badge,
                                  {
                                      variant: 'secondary',
                                      class: 'text-[10.5px] px-1.5 py-0 font-normal',
                                  },
                                  () => 'Core Role',
                              )
                            : null,
                    ]),
                    h(
                        'span',
                        { class: 'text-xs text-muted-foreground' },
                        `${role.permissions.length} granted permission${role.permissions.length === 1 ? '' : 's'}`,
                    ),
                ]),
            ]);
        },
    },
    {
        accessorKey: 'users_count',
        header: () =>
            h(DataTableColumnHeader, {
                title: 'Assigned Staff',
                sortDirection: getSortDirection('users_count'),
                onSort: (dir: 'asc' | 'desc' | null) =>
                    handleSort('users_count', dir),
            }),
        cell: ({ row }) => {
            const count = row.original.users_count;
            return h(
                'div',
                {
                    class: 'flex items-center space-x-1.5 text-xs text-foreground font-medium',
                },
                [
                    h(Users, {
                        class: 'h-3.5 w-3.5 text-muted-foreground shrink-0',
                    }),
                    h('span', `${count} ${count === 1 ? 'user' : 'users'}`),
                ],
            );
        },
    },
    {
        accessorKey: 'permissions',
        header: 'Granted Permissions Preview',
        cell: ({ row }) => {
            const perms = row.original.permissions;
            if (perms.length === 0) {
                return h(
                    'span',
                    { class: 'text-xs text-muted-foreground italic' },
                    'No permissions',
                );
            }

            const visible = perms.slice(0, 3);
            const remaining = perms.length - visible.length;

            return h(
                'div',
                { class: 'flex flex-wrap items-center gap-1 max-w-md' },
                [
                    ...visible.map((p) =>
                        h(
                            Badge,
                            {
                                variant: 'outline',
                                class: 'font-mono text-[10.5px] py-0.5 px-1.5 bg-muted/30 border-muted-foreground/20 truncate max-w-[140px]',
                            },
                            () => p.name,
                        ),
                    ),
                    remaining > 0
                        ? h(
                              Badge,
                              {
                                  variant: 'secondary',
                                  class: 'text-[10.5px] py-0.5 px-1.5 font-medium cursor-pointer hover:bg-secondary/80',
                                  onClick: () => openViewRoleModal(row.original),
                              },
                              () => `+${remaining} more`,
                          )
                        : null,
                ],
            );
        },
    },
    {
        accessorKey: 'created_at',
        header: () =>
            h(DataTableColumnHeader, {
                title: 'Created At',
                sortDirection: getSortDirection('created_at'),
                onSort: (dir: 'asc' | 'desc' | null) =>
                    handleSort('created_at', dir),
            }),
        cell: ({ row }) => {
            return h(
                'span',
                { class: 'text-xs text-muted-foreground font-medium' },
                formatDate(row.original.created_at),
            );
        },
    },
    {
        id: 'actions',
        cell: ({ row }) => {
            return h(RoleTableRowActions, {
                role: row.original,
                onView: openViewRoleModal,
                onEdit: openEditRoleModal,
                onDelete: openDeleteRoleModal,
            });
        },
    },
];

const roleTable = useVueTable({
    get data() {
        return props.roles.data;
    },
    columns: roleColumns,
    getCoreRowModel: getCoreRowModel(),
    manualPagination: true,
    manualSorting: true,
    manualFiltering: true,
});

// Permissions Tab filtering
const filteredPermissions = computed(() => {
    const q = permissionSearchQuery.value.toLowerCase().trim();
    const mod = selectedModule.value;

    return props.permissions.filter((p) => {
        const matchesQuery = !q || p.name.toLowerCase().includes(q);
        const permModule = p.name.split('.')[0] || 'other';
        const matchesModule = mod === 'all' || permModule === mod;
        return matchesQuery && matchesModule;
    });
});

// All available permissions for the RoleFormDialog
const allAvailablePermissions = computed<AvailablePermission[]>(() => {
    return props.permissions.map((p) => ({
        id: p.id,
        name: p.name,
    }));
});
</script>

<template>
    <div
        class="mx-auto flex h-full w-full max-w-[1536px] flex-1 flex-col gap-6 p-4 md:p-6"
    >
        <Head title="Roles & Permissions" />

        <!-- Header Section -->
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <div class="flex items-center gap-2">
                    <div
                        class="bg-primary/10 text-primary flex h-8 w-8 items-center justify-center rounded-lg"
                    >
                        <ShieldCheck class="h-4 w-4" />
                    </div>
                    <h1
                        class="text-foreground text-xl font-bold tracking-tight md:text-2xl"
                    >
                        Roles & Permissions
                    </h1>
                </div>
                <p class="text-muted-foreground mt-1 text-xs sm:text-sm">
                    Configure official LGU role profiles, grant granular capabilities, and govern security tiers.
                </p>
            </div>

            <!-- Header Quick Nav and Action -->
            <div class="flex flex-wrap items-center gap-2">
                <!-- Navigation Switcher Pills -->
                <div class="inline-flex rounded-lg border bg-muted p-0.5">
                    <Button
                        as-child
                        variant="ghost"
                        size="sm"
                        class="h-7 text-xs font-medium text-muted-foreground hover:text-foreground"
                    >
                        <Link :href="usersIndex.url()">
                            <Users class="mr-1.5 h-3.5 w-3.5" />
                            Users
                        </Link>
                    </Button>
                    <Button
                        variant="secondary"
                        size="sm"
                        class="h-7 text-xs font-semibold shadow-xs"
                    >
                        <ShieldCheck class="mr-1.5 h-3.5 w-3.5 text-primary" />
                        Roles & Permissions
                    </Button>
                </div>

                <!-- Primary Action Button -->
                <Button
                    v-if="currentTab === 'roles'"
                    class="shrink-0 h-8 text-xs font-semibold"
                    @click="openCreateRoleModal"
                >
                    <Plus class="mr-1.5 h-4 w-4" />
                    Add New Role
                </Button>

                <Button
                    v-else
                    class="shrink-0 h-8 text-xs font-semibold"
                    @click="openCreatePermissionModal"
                >
                    <Plus class="mr-1.5 h-4 w-4" />
                    Add New Permission
                </Button>
            </div>
        </div>

        <!-- Tab Switching Bar -->
        <div class="flex border-b border-border">
            <button
                type="button"
                @click="switchTab('roles')"
                :class="[
                    'flex items-center gap-2 px-4 py-2.5 text-sm font-semibold border-b-2 transition-colors -mb-px',
                    currentTab === 'roles'
                        ? 'border-primary text-primary'
                        : 'border-transparent text-muted-foreground hover:text-foreground hover:border-muted-foreground/30',
                ]"
            >
                <Shield class="h-4 w-4" />
                Roles
                <Badge variant="secondary" class="text-xs px-1.5 py-0 font-normal">
                    {{ roles.total }}
                </Badge>
            </button>

            <button
                type="button"
                @click="switchTab('permissions')"
                :class="[
                    'flex items-center gap-2 px-4 py-2.5 text-sm font-semibold border-b-2 transition-colors -mb-px',
                    currentTab === 'permissions'
                        ? 'border-primary text-primary'
                        : 'border-transparent text-muted-foreground hover:text-foreground hover:border-muted-foreground/30',
                ]"
            >
                <KeyRound class="h-4 w-4" />
                Permissions
                <Badge variant="secondary" class="text-xs px-1.5 py-0 font-normal">
                    {{ permissions.length }}
                </Badge>
            </button>
        </div>

        <!-- TAB 1: ROLES MANAGEMENT -->
        <template v-if="currentTab === 'roles'">
            <!-- Roles Filter Toolbar -->
            <div
                class="bg-card flex flex-col gap-3 rounded-xl border p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
                    <div class="relative w-full sm:max-w-xs">
                        <Search
                            class="text-muted-foreground absolute top-2.5 left-2.5 h-4 w-4"
                        />
                        <Input
                            v-model="searchQuery"
                            type="search"
                            placeholder="Search roles by name..."
                            class="h-9 pl-9 text-xs"
                        />
                    </div>

                    <Button
                        v-if="searchQuery"
                        variant="ghost"
                        size="sm"
                        class="text-muted-foreground hover:text-foreground h-9 text-xs"
                        @click="resetRoleFilters"
                    >
                        <RotateCcw class="mr-1.5 h-3.5 w-3.5" />
                        Reset Search
                    </Button>
                </div>

                <div class="text-muted-foreground hidden items-center text-xs md:flex">
                    <Shield class="mr-1.5 h-4 w-4 text-primary" />
                    <span>Total Roles: <strong class="text-foreground">{{ roles.total }}</strong></span>
                </div>
            </div>

            <!-- Data Table Card for Roles -->
            <div class="rounded-xl border border-border/80 bg-card shadow-xs overflow-hidden">
                <DataTable
                    :table="roleTable"
                    :is-loading="isLoading"
                    empty-message="No roles match your search criteria. Try clearing search or create a new role."
                    class="border-0 shadow-none rounded-none"
                />
                <div class="p-4 border-t border-border/60">
                    <DataTablePagination
                        :total="roles.total"
                        :from="roles.from"
                        :to="roles.to"
                        :current-page="roles.current_page"
                        :last-page="roles.last_page"
                        :per-page="roles.per_page"
                        @page-change="applyFilters({ page: $event })"
                        @per-page-change="applyFilters({ per_page: $event, page: 1 })"
                    />
                </div>
            </div>
        </template>

        <!-- TAB 2: PERMISSIONS MANAGEMENT -->
        <template v-else>
            <!-- Permissions Filter Toolbar -->
            <div
                class="bg-card flex flex-col gap-3 rounded-xl border p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
                    <div class="relative w-full sm:max-w-xs">
                        <Search
                            class="text-muted-foreground absolute top-2.5 left-2.5 h-4 w-4"
                        />
                        <Input
                            v-model="permissionSearchQuery"
                            type="search"
                            placeholder="Search permissions..."
                            class="h-9 pl-9 text-xs"
                        />
                    </div>

                    <div class="w-full sm:w-[220px]">
                        <Select v-model="selectedModule">
                            <SelectTrigger class="h-9 text-xs">
                                <Filter class="text-muted-foreground mr-2 h-3.5 w-3.5" />
                                <SelectValue placeholder="All Modules" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Modules</SelectItem>
                                <SelectItem value="users">User Accounts (users.*)</SelectItem>
                                <SelectItem value="roles">Role Security (roles.*)</SelectItem>
                                <SelectItem value="permissions">Permissions Catalog (permissions.*)</SelectItem>
                                <SelectItem value="municipalities">Municipalities (municipalities.*)</SelectItem>
                                <SelectItem value="notes">Developer Notes (notes.*)</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <Button
                        v-if="permissionSearchQuery || selectedModule !== 'all'"
                        variant="ghost"
                        size="sm"
                        class="text-muted-foreground hover:text-foreground h-9 text-xs"
                        @click="
                            permissionSearchQuery = '';
                            selectedModule = 'all';
                        "
                    >
                        <RotateCcw class="mr-1.5 h-3.5 w-3.5" />
                        Reset Filters
                    </Button>
                </div>

                <div class="text-muted-foreground hidden items-center text-xs md:flex">
                    <KeyRound class="mr-1.5 h-4 w-4 text-emerald-500" />
                    <span>Catalog Size: <strong class="text-foreground">{{ filteredPermissions.length }}</strong> of {{ permissions.length }}</span>
                </div>
            </div>

            <!-- Permissions Table -->
            <div class="rounded-xl border bg-card shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-muted/50 border-b text-muted-foreground uppercase font-semibold text-[11.5px] tracking-wider">
                            <tr>
                                <th class="p-4">Permission Name</th>
                                <th class="p-4">Module / Domain</th>
                                <th class="p-4">Assigned Roles</th>
                                <th class="p-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr
                                v-if="filteredPermissions.length === 0"
                                class="text-center"
                            >
                                <td colspan="4" class="p-8 text-muted-foreground">
                                    No permissions match your search or filter.
                                </td>
                            </tr>
                            <tr
                                v-for="perm in filteredPermissions"
                                :key="perm.id"
                                class="hover:bg-muted/30 transition-colors"
                            >
                                <!-- Name -->
                                <td class="p-4">
                                    <div class="flex items-center gap-2">
                                        <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md bg-muted text-muted-foreground">
                                            <KeyRound class="h-3.5 w-3.5" />
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="font-mono font-semibold text-foreground text-xs">
                                                {{ perm.name }}
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Module -->
                                <td class="p-4">
                                    <Badge variant="outline" class="font-medium text-[11.5px] py-0.5">
                                        {{ getPermissionModule(perm.name) }}
                                    </Badge>
                                </td>

                                <!-- Roles -->
                                <td class="p-4">
                                    <div class="flex flex-wrap items-center gap-1 max-w-sm">
                                        <Badge
                                            v-for="r in perm.roles"
                                            :key="r.id"
                                            variant="secondary"
                                            :class="`text-[10.5px] py-0.5 px-1.5 font-medium ${getRoleBadgeClass(r.name)}`"
                                        >
                                            {{ r.name }}
                                        </Badge>
                                        <span v-if="perm.roles.length === 0" class="text-muted-foreground italic text-[11.5px]">
                                            Unassigned
                                        </span>
                                    </div>
                                </td>

                                <!-- Actions -->
                                <td class="p-4 text-right">
                                    <PermissionTableRowActions
                                        :permission="perm"
                                        @edit="openEditPermissionModal"
                                        @delete="openDeletePermissionModal"
                                    />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </template>

        <!-- MODAL DIALOGS -->
        <!-- Role Create / Edit Dialog -->
        <RoleFormDialog
            v-model:open="isRoleFormOpen"
            :role="selectedRole"
            :all-permissions="allAvailablePermissions"
            @success="applyFilters"
        />

        <!-- Role View Dialog -->
        <RoleViewDialog
            v-model:open="isRoleViewOpen"
            :role="selectedRole"
            @edit="openEditRoleModal"
        />

        <!-- Role Delete Dialog -->
        <RoleDeleteDialog
            v-model:open="isRoleDeleteOpen"
            :role="selectedRole"
            @success="applyFilters"
        />

        <!-- Permission Create / Edit Dialog -->
        <PermissionFormDialog
            v-model:open="isPermissionFormOpen"
            :permission="selectedPermission"
            :all-roles="all_roles"
            @success="applyFilters"
        />

        <!-- Permission Delete Dialog -->
        <PermissionDeleteDialog
            v-model:open="isPermissionDeleteOpen"
            :permission="selectedPermission"
            @success="applyFilters"
        />
    </div>
</template>
