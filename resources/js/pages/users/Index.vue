<script setup lang="ts">
import { computed, h, ref, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import {
    getCoreRowModel,
    useVueTable,
    type ColumnDef,
} from '@tanstack/vue-table';
import {
    CheckCircle2,
    Filter,
    RotateCcw,
    Search,
    Shield,
    ShieldCheck,
    UserCheck,
    UserPlus,
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
import { index as usersIndex } from '@/actions/App/Http/Controllers/UserController';
import { index as rolesIndex } from '@/actions/App/Http/Controllers/RoleController';
import { dashboard } from '@/routes';
import UserFormDialog, {
    type UserItem,
    type UserRole,
} from './UserFormDialog.vue';
import UserViewDialog from './UserViewDialog.vue';
import UserDeleteDialog from './UserDeleteDialog.vue';
import UserTableRowActions from './UserTableRowActions.vue';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
            {
                title: 'User Management',
                href: usersIndex.url(),
            },
        ],
    },
});

interface UsersPagination {
    data: UserItem[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
}

interface FilterState {
    search: string;
    role: string;
    sort: string;
    direction: 'asc' | 'desc';
    per_page: number;
}

const props = defineProps<{
    users: UsersPagination;
    roles: UserRole[];
    filters: FilterState;
}>();

const page = usePage();
const currentUserId = computed(() => (page.props.auth as any)?.user?.id);

// State for search & filters
const searchQuery = ref(props.filters.search || '');
const selectedRole = ref(props.filters.role || 'all');
const isLoading = ref(false);

// State for modals
const isFormDialogOpen = ref(false);
const isViewDialogOpen = ref(false);
const isDeleteDialogOpen = ref(false);
const selectedUser = ref<UserItem | null>(null);

// Role badge styling
function getRoleBadgeClass(roleName: string) {
    switch (roleName) {
        case 'System Admin':
            return 'bg-rose-500/10 text-rose-700 dark:text-rose-400 border-rose-500/20';
        case 'User':
            return 'bg-blue-500/10 text-blue-700 dark:text-blue-400 border-blue-500/20';
        default:
            return 'bg-muted text-muted-foreground border-border';
    }
}

// Server-side visit helper
function applyFilters(updates: Partial<FilterState> & { page?: number } = {}) {
    isLoading.value = true;
    const queryParams: Record<string, any> = {
        search: searchQuery.value || undefined,
        role: selectedRole.value !== 'all' ? selectedRole.value : undefined,
        sort: props.filters.sort,
        direction: props.filters.direction,
        per_page: props.filters.per_page,
        page: props.users.current_page,
        ...updates,
    };

    router.get(usersIndex.url(), queryParams, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onFinish: () => {
            isLoading.value = false;
        },
    });
}

// Debounced search
const debouncedSearch = useDebounceFn(() => {
    applyFilters({ page: 1 });
}, 350);

watch(searchQuery, () => {
    debouncedSearch();
});

function handleRoleChange(value: unknown) {
    selectedRole.value = String(value || 'all');
    applyFilters({ page: 1 });
}

function resetFilters() {
    searchQuery.value = '';
    selectedRole.value = 'all';
    applyFilters({
        search: undefined,
        role: undefined,
        sort: 'created_at',
        direction: 'desc',
        page: 1,
    });
}

function handleSort(column: string, direction: 'asc' | 'desc' | null) {
    if (!direction) {
        applyFilters({ sort: 'created_at', direction: 'desc', page: 1 });
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

// Modal triggers
function openCreateModal() {
    selectedUser.value = null;
    isFormDialogOpen.value = true;
}

function openViewModal(user: UserItem) {
    selectedUser.value = user;
    isViewDialogOpen.value = true;
}

function openEditModal(user: UserItem) {
    selectedUser.value = user;
    isViewDialogOpen.value = false;
    isFormDialogOpen.value = true;
}

function openDeleteModal(user: UserItem) {
    selectedUser.value = user;
    isDeleteDialogOpen.value = true;
}

// Format date helper
function formatDate(dateStr?: string | null) {
    if (!dateStr) return 'N/A';
    return new Date(dateStr).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

// TanStack Table Column Definitions
const columns: ColumnDef<UserItem>[] = [
    {
        accessorKey: 'name',
        header: () =>
            h(DataTableColumnHeader, {
                title: 'User',
                sortDirection: getSortDirection('name'),
                onSort: (dir: 'asc' | 'desc' | null) => handleSort('name', dir),
            }),
        cell: ({ row }) => {
            const user = row.original;
            const isSelf = user.id === currentUserId.value;
            const initials = user.name
                .split(' ')
                .map((n) => n[0])
                .slice(0, 2)
                .join('')
                .toUpperCase();

            return h('div', { class: 'flex items-center space-x-3' }, [
                h(
                    'div',
                    {
                        class: 'flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary font-semibold text-xs',
                    },
                    initials,
                ),
                h('div', { class: 'flex flex-col min-w-0' }, [
                    h('div', { class: 'flex items-center gap-1.5' }, [
                        h(
                            'span',
                            {
                                class: 'font-medium text-foreground text-sm truncate max-w-[200px]',
                            },
                            user.name,
                        ),
                        isSelf
                            ? h(
                                  Badge,
                                  {
                                      variant: 'secondary',
                                      class: 'text-[10.5px] px-1.5 py-0',
                                  },
                                  () => 'You',
                              )
                            : null,
                    ]),
                    h(
                        'span',
                        {
                            class: 'text-xs text-muted-foreground truncate max-w-[200px]',
                        },
                        user.email,
                    ),
                ]),
            ]);
        },
    },
    {
        accessorKey: 'email',
        header: () =>
            h(DataTableColumnHeader, {
                title: 'Email',
                sortDirection: getSortDirection('email'),
                onSort: (dir: 'asc' | 'desc' | null) =>
                    handleSort('email', dir),
            }),
        cell: ({ row }) => {
            const user = row.original;
            return h(
                'div',
                {
                    class: 'flex items-center space-x-1.5 text-sm text-foreground',
                },
                [
                    h('span', { class: 'truncate' }, user.email),
                    user.email_verified_at
                        ? h(CheckCircle2, {
                              class: 'h-3.5 w-3.5 text-emerald-500 shrink-0',
                              title: 'Verified',
                          })
                        : null,
                ],
            );
        },
    },
    {
        accessorKey: 'role',
        header: () =>
            h(DataTableColumnHeader, {
                title: 'Assigned Role',
                sortDirection: getSortDirection('role'),
                onSort: (dir: 'asc' | 'desc' | null) => handleSort('role', dir),
            }),
        cell: ({ row }) => {
            const user = row.original;
            return h(
                'div',
                { class: 'flex flex-wrap gap-1' },
                user.roles.map((role) =>
                    h(
                        Badge,
                        {
                            variant: 'outline',
                            class: `text-xs py-0.5 px-2 font-medium ${getRoleBadgeClass(role.name)}`,
                        },
                        () => [
                            h(Shield, {
                                class: 'mr-1 h-3 w-3 inline-block shrink-0',
                            }),
                            role.name,
                        ],
                    ),
                ),
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
            return h(UserTableRowActions, {
                user: row.original,
                onView: openViewModal,
                onEdit: openEditModal,
                onDelete: openDeleteModal,
            });
        },
    },
];

// Initialize TanStack Table
const table = useVueTable({
    get data() {
        return props.users.data;
    },
    columns,
    getCoreRowModel: getCoreRowModel(),
    manualPagination: true,
    manualSorting: true,
    manualFiltering: true,
});

const hasActiveFilters = computed(() => {
    return (
        !!searchQuery.value ||
        (selectedRole.value && selectedRole.value !== 'all')
    );
});
</script>

<template>
    <div
        class="mx-auto flex h-full w-full max-w-[1536px] flex-1 flex-col gap-6 p-4 md:p-6"
    >
        <Head title="User Management" />
        <!-- Header Section -->
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <div class="flex items-center gap-2">
                    <div
                        class="bg-primary/10 text-primary flex h-8 w-8 items-center justify-center rounded-lg"
                    >
                        <Users class="h-4 w-4" />
                    </div>
                    <h1
                        class="text-foreground text-xl font-bold tracking-tight md:text-2xl"
                    >
                        User Management
                    </h1>
                </div>
                <p class="text-muted-foreground mt-1 text-xs sm:text-sm">
                    Oversee user accounts, manage role permissions, and
                    regulate access to the application.
                </p>
            </div>

            <!-- Header Quick Nav and Action -->
            <div class="flex flex-wrap items-center gap-2">
                <!-- Navigation Switcher Pills -->
                <div class="inline-flex rounded-lg border bg-muted p-0.5">
                    <Button
                        variant="secondary"
                        size="sm"
                        class="h-7 text-xs font-semibold shadow-xs"
                    >
                        <Users class="mr-1.5 h-3.5 w-3.5 text-primary" />
                        Users
                    </Button>
                    <Button
                        as-child
                        variant="ghost"
                        size="sm"
                        class="h-7 text-xs font-medium text-muted-foreground hover:text-foreground"
                    >
                        <Link :href="rolesIndex.url()">
                            <ShieldCheck class="mr-1.5 h-3.5 w-3.5" />
                            Roles & Permissions
                        </Link>
                    </Button>
                </div>

                <!-- Create User Button -->
                <Button class="shrink-0 h-8 text-xs font-semibold" @click="openCreateModal">
                    <UserPlus class="mr-1.5 h-4 w-4" />
                    Add New User
                </Button>
            </div>
        </div>

        <!-- Filter & Search Toolbar -->
        <div
            class="bg-card flex flex-col gap-3 rounded-xl border p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
                <!-- Search Input -->
                <div class="relative w-full sm:max-w-xs">
                    <Search
                        class="text-muted-foreground absolute top-2.5 left-2.5 h-4 w-4"
                    />
                    <Input
                        v-model="searchQuery"
                        type="search"
                        placeholder="Search by name or email..."
                        class="h-9 pl-9"
                    />
                </div>

                <!-- Role Dropdown Filter -->
                <div class="w-full sm:w-[200px]">
                    <Select
                        :model-value="selectedRole"
                        @update:model-value="handleRoleChange"
                    >
                        <SelectTrigger class="h-9">
                            <Filter
                                class="text-muted-foreground mr-2 h-3.5 w-3.5"
                            />
                            <SelectValue placeholder="All Roles" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all"> All Roles </SelectItem>
                            <SelectItem
                                v-for="roleItem in roles"
                                :key="roleItem.id"
                                :value="roleItem.name"
                            >
                                {{ roleItem.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <!-- Reset Filters Button -->
                <Button
                    v-if="hasActiveFilters"
                    variant="ghost"
                    size="sm"
                    class="text-muted-foreground hover:text-foreground h-9 text-xs"
                    @click="resetFilters"
                >
                    <RotateCcw class="mr-1.5 h-3.5 w-3.5" />
                    Reset Filters
                </Button>
            </div>

            <!-- Quick Stats Badge -->
            <div
                class="text-muted-foreground hidden items-center text-xs md:flex"
            >
                <UserCheck class="mr-1.5 h-4 w-4 text-emerald-500" />
                <span
                    >Total Users:
                    <strong class="text-foreground">{{
                        users.total
                    }}</strong></span
                >
            </div>
        </div>

        <!-- Data Table Card -->
        <div class="rounded-xl border border-border/80 bg-card shadow-xs overflow-hidden">
            <DataTable
                :table="table"
                :is-loading="isLoading"
                empty-message="No users match your search criteria. Try clearing filters or create a new user."
                class="border-0 shadow-none rounded-none"
            />
            <div class="p-4 border-t border-border/60">
                <DataTablePagination
                    :total="users.total"
                    :from="users.from"
                    :to="users.to"
                    :current-page="users.current_page"
                    :last-page="users.last_page"
                    :per-page="users.per_page"
                    @page-change="applyFilters({ page: $event })"
                    @per-page-change="applyFilters({ per_page: $event, page: 1 })"
                />
            </div>
        </div>

        <!-- Create & Edit User Dialog -->
        <UserFormDialog
            v-model:open="isFormDialogOpen"
            :user="selectedUser"
            :roles="roles"
            @success="applyFilters"
        />

        <!-- View User Details Dialog -->
        <UserViewDialog
            v-model:open="isViewDialogOpen"
            :user="selectedUser"
            @edit="openEditModal"
        />

        <!-- Delete Confirmation Dialog -->
        <UserDeleteDialog
            v-model:open="isDeleteDialogOpen"
            :user="selectedUser"
            @success="applyFilters"
        />
    </div>
</template>
