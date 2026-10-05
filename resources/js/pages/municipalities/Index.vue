<script setup lang="ts">
import { computed, h, ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import {
    getCoreRowModel,
    useVueTable,
    type ColumnDef,
} from '@tanstack/vue-table';
import {
    Building,
    Building2,
    Compass,
    ExternalLink,
    Filter,
    Landmark,
    MapPin,
    Plus,
    RotateCcw,
    Search,
    Shield,
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
import { index as municipalitiesIndex } from '@/actions/App/Http/Controllers/MunicipalityController';
import { dashboard } from '@/routes';
import MunicipalityTableRowActions, {
    type MunicipalityItem,
} from './MunicipalityTableRowActions.vue';
import MunicipalityFormDialog from './MunicipalityFormDialog.vue';
import MunicipalityDeleteDialog from './MunicipalityDeleteDialog.vue';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
            {
                title: 'Municipalities & Cities',
                href: municipalitiesIndex.url(),
            },
        ],
    },
});

interface PaginatedData<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
}

interface Stats {
    total_lgus: number;
    total_cities: number;
    total_municipalities: number;
    district_1_count: number;
    district_2_count: number;
}

const props = defineProps<{
    municipalities: PaginatedData<MunicipalityItem>;
    filters: {
        search?: string;
        district?: string;
        status?: string;
        sort?: string;
        direction?: 'asc' | 'desc';
        per_page?: number;
    };
    stats: Stats;
    can_manage: boolean;
}>();

const search = ref(props.filters.search || '');
const districtFilter = ref(props.filters.district || 'all');
const statusFilter = ref(props.filters.status || 'all');

// Modals state
const isFormDialogOpen = ref(false);
const isDeleteDialogOpen = ref(false);
const selectedMunicipality = ref<MunicipalityItem | null>(null);

const applyFilters = (updates: { page?: number; per_page?: number } = {}) => {
    router.get(
        municipalitiesIndex.url(),
        {
            search: search.value || undefined,
            district: districtFilter.value !== 'all' ? districtFilter.value : undefined,
            status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
            sort: props.filters.sort,
            direction: props.filters.direction,
            per_page: updates.per_page ?? props.filters.per_page,
            page: updates.page ?? props.municipalities.current_page,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

const debouncedSearch = useDebounceFn(() => {
    applyFilters({ page: 1 });
}, 300);

watch(search, () => {
    debouncedSearch();
});

watch([districtFilter, statusFilter], () => {
    applyFilters({ page: 1 });
});

const resetFilters = () => {
    search.value = '';
    districtFilter.value = 'all';
    statusFilter.value = 'all';
    router.get(
        municipalitiesIndex.url(),
        {
            sort: props.filters.sort,
            direction: props.filters.direction,
            per_page: props.filters.per_page,
            page: 1,
        },
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
};

const hasActiveFilters = computed(() => {
    return (
        !!search.value ||
        districtFilter.value !== 'all' ||
        statusFilter.value !== 'all'
    );
});

// Handlers
const openCreateDialog = () => {
    selectedMunicipality.value = null;
    isFormDialogOpen.value = true;
};

const openEditDialog = (muni: MunicipalityItem) => {
    selectedMunicipality.value = muni;
    isFormDialogOpen.value = true;
};

const openDeleteDialog = (muni: MunicipalityItem) => {
    selectedMunicipality.value = muni;
    isDeleteDialogOpen.value = true;
};

// Table Columns
const columns: ColumnDef<MunicipalityItem>[] = [
    {
        accessorKey: 'name',
        header: () =>
            h(DataTableColumnHeader, { title: 'Municipality / City', sortable: false }),
        cell: ({ row }) => {
            const muni = row.original;
            return h('div', { class: 'flex items-center space-x-3 py-1' }, [
                h(
                    'div',
                    {
                        class: `flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border ${
                            muni.type === 'city'
                                ? 'border-amber-500/30 bg-amber-500/10 text-amber-600 dark:text-amber-400'
                                : 'border-primary/20 bg-primary/10 text-primary'
                        }`,
                    },
                    [
                        h(muni.type === 'city' ? Landmark : Building, {
                            class: 'h-4 w-4',
                        }),
                    ],
                ),
                h('div', { class: 'flex flex-col' }, [
                    h('div', { class: 'flex items-center space-x-2' }, [
                        h(
                            'span',
                            { class: 'font-bold text-foreground text-sm tracking-tight' },
                            muni.name,
                        ),
                        h(
                            Badge,
                            {
                                variant: 'secondary',
                                class: `text-[10.5px] font-semibold uppercase px-1.5 py-0 ${
                                    muni.type === 'city'
                                        ? 'bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-300 border-amber-300/40'
                                        : 'bg-muted text-muted-foreground'
                                }`,
                            },
                            () => (muni.type === 'city' ? 'Chartered City' : 'Municipality'),
                        ),
                    ]),
                    muni.location_description
                        ? h(
                              'span',
                              {
                                  class: 'text-xs text-muted-foreground line-clamp-1 max-w-[280px]',
                                  title: muni.location_description,
                              },
                              muni.location_description,
                          )
                        : null,
                ]),
            ]);
        },
    },
    {
        accessorKey: 'code',
        header: () =>
            h(DataTableColumnHeader, { title: 'Code & Postal', sortable: false }),
        cell: ({ row }) => {
            const muni = row.original;
            return h('div', { class: 'flex flex-col text-xs' }, [
                h(
                    'span',
                    { class: 'font-mono font-semibold text-foreground' },
                    muni.code || '—',
                ),
                muni.zip_code
                    ? h(
                          'span',
                          { class: 'text-muted-foreground text-[11.5px]' },
                          `Zip: ${muni.zip_code}`,
                      )
                    : null,
            ]);
        },
    },
    {
        accessorKey: 'district',
        header: () =>
            h(DataTableColumnHeader, { title: 'District', sortable: false }),
        cell: ({ row }) => {
            const muni = row.original;
            const isDistrict1 = muni.district === 'District 1';
            return h(
                Badge,
                {
                    variant: 'outline',
                    class: `text-xs font-medium px-2 py-0.5 ${
                        isDistrict1
                            ? 'border-indigo-500/40 bg-indigo-500/10 text-indigo-700 dark:text-indigo-300'
                            : 'border-emerald-500/40 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300'
                    }`,
                },
                () =>
                    isDistrict1
                        ? 'District 1 (Islands)'
                        : 'District 2 (Mainland)',
            );
        },
    },
    {
        accessorKey: 'latitude',
        header: () =>
            h(DataTableColumnHeader, { title: 'Location / Coordinates', sortable: false }),
        cell: ({ row }) => {
            const muni = row.original;
            const hasCoords = muni.latitude !== null && muni.longitude !== null;

            if (!hasCoords) {
                return h(
                    'span',
                    { class: 'text-xs text-muted-foreground italic' },
                    'Not specified',
                );
            }

            return h('div', { class: 'flex items-center space-x-2' }, [
                h(
                    'div',
                    {
                        class: 'flex items-center space-x-1 font-mono text-xs bg-muted/60 px-2 py-1 rounded-md border border-border/70',
                    },
                    [
                        h(MapPin, { class: 'h-3 w-3 text-emerald-600 dark:text-emerald-400 shrink-0' }),
                        h(
                            'span',
                            { class: 'text-foreground font-medium' },
                            `${muni.latitude?.toFixed(4)}°N, ${muni.longitude?.toFixed(4)}°E`,
                        ),
                    ],
                ),
                muni.map_url
                    ? h(
                          'a',
                          {
                              href: muni.map_url,
                              target: '_blank',
                              rel: 'noopener noreferrer',
                              class: 'inline-flex h-7 w-7 items-center justify-center rounded-md border border-border text-muted-foreground hover:bg-muted hover:text-foreground transition-colors',
                              title: 'Open in Google Maps',
                          },
                          [h(ExternalLink, { class: 'h-3.5 w-3.5' })],
                      )
                    : null,
            ]);
        },
    },
    {
        accessorKey: 'is_active',
        header: () =>
            h(DataTableColumnHeader, { title: 'Status', sortable: false }),
        cell: ({ row }) => {
            const active = row.original.is_active;
            return h(
                Badge,
                {
                    variant: active ? 'default' : 'secondary',
                    class: `text-[11.5px] font-medium ${
                        active
                            ? 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-500/20 border-emerald-500/30'
                            : 'bg-muted text-muted-foreground'
                    }`,
                },
                () => (active ? 'Active' : 'Inactive'),
            );
        },
    },
    {
        id: 'actions',
        cell: ({ row }) =>
            h(MunicipalityTableRowActions, {
                municipality: row.original,
                canManage: props.can_manage,
                onEdit: openEditDialog,
                onDelete: openDeleteDialog,
            }),
    },
];

const table = useVueTable({
    get data() {
        return props.municipalities.data;
    },
    columns,
    getCoreRowModel: getCoreRowModel(),
    manualPagination: true,
    manualSorting: true,
    manualFiltering: true,
});
</script>

<template>
    <div
        class="mx-auto flex h-full w-full max-w-[1536px] flex-1 flex-col gap-6 p-4 md:p-6"
    >
        <Head title="Municipalities & Cities" />
        <!-- Header Banner -->
        <div class="relative overflow-hidden rounded-2xl border border-border/80 bg-gradient-to-br from-card via-card to-muted/40 p-6 md:p-8 shadow-sm">
            <div class="relative z-10 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="space-y-1.5">
                    <div class="flex items-center space-x-2.5">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10 text-primary shadow-inner">
                            <Compass class="h-5 w-5" />
                        </div>
                        <h1 class="text-2xl md:text-3xl font-black tracking-tight text-foreground">
                            Municipalities & Cities
                        </h1>
                    </div>
                    <p class="text-xs md:text-sm text-muted-foreground max-w-2xl">
                        Territorial jurisdiction registry for the Province of Surigao del Norte. Manage local government units, geographic coordinates, legislative districts, and spatial coverage.
                    </p>
                </div>

                <div v-if="can_manage" class="flex items-center gap-2">
                    <Button
                        @click="openCreateDialog"
                        class="shadow-sm font-semibold"
                    >
                        <Plus class="mr-2 h-4 w-4" />
                        Add Municipality / City
                    </Button>
                </div>
            </div>
        </div>

        <!-- Metrics Overview Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="rounded-xl border border-border/80 bg-card p-4 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-muted-foreground">Total LGUs</span>
                    <Building2 class="h-4 w-4 text-primary" />
                </div>
                <div class="mt-2 flex items-baseline space-x-2">
                    <span class="text-2xl font-black text-foreground">{{ stats.total_lgus }}</span>
                    <span class="text-[11.5px] text-muted-foreground font-medium">Units</span>
                </div>
            </div>

            <div class="rounded-xl border border-border/80 bg-card p-4 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-muted-foreground">Cities & Capital</span>
                    <Landmark class="h-4 w-4 text-amber-600 dark:text-amber-400" />
                </div>
                <div class="mt-2 flex items-baseline space-x-2">
                    <span class="text-2xl font-black text-foreground">{{ stats.total_cities }}</span>
                    <span class="text-[11.5px] text-muted-foreground font-medium">Chartered City</span>
                </div>
            </div>

            <div class="rounded-xl border border-border/80 bg-card p-4 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-muted-foreground">District 1 (Islands)</span>
                    <MapPin class="h-4 w-4 text-indigo-600 dark:text-indigo-400" />
                </div>
                <div class="mt-2 flex items-baseline space-x-2">
                    <span class="text-2xl font-black text-foreground">{{ stats.district_1_count }}</span>
                    <span class="text-[11.5px] text-muted-foreground font-medium">Siargao & Bucas Grande</span>
                </div>
            </div>

            <div class="rounded-xl border border-border/80 bg-card p-4 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-muted-foreground">District 2 (Mainland)</span>
                    <MapPin class="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                </div>
                <div class="mt-2 flex items-baseline space-x-2">
                    <span class="text-2xl font-black text-foreground">{{ stats.district_2_count }}</span>
                    <span class="text-[11.5px] text-muted-foreground font-medium">Mainland Municipalities</span>
                </div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between rounded-xl border border-border/70 bg-card p-3 shadow-xs">
            <div class="flex flex-1 flex-wrap items-center gap-3">
                <div class="relative w-full sm:w-72">
                    <Search class="text-muted-foreground absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2" />
                    <Input
                        v-model="search"
                        placeholder="Search LGU name, code, zip..."
                        class="pl-9 h-9 text-xs"
                    />
                </div>

                <!-- District Filter -->
                <Select v-model="districtFilter">
                    <SelectTrigger class="h-9 w-[180px] text-xs">
                        <SelectValue placeholder="All Districts" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All Districts</SelectItem>
                        <SelectItem value="District 1">District 1 (Islands)</SelectItem>
                        <SelectItem value="District 2">District 2 (Mainland)</SelectItem>
                    </SelectContent>
                </Select>

                <!-- Status Filter -->
                <Select v-model="statusFilter">
                    <SelectTrigger class="h-9 w-[140px] text-xs">
                        <SelectValue placeholder="All Status" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All Status</SelectItem>
                        <SelectItem value="active">Active Only</SelectItem>
                        <SelectItem value="inactive">Inactive Only</SelectItem>
                    </SelectContent>
                </Select>

                <Button
                    v-if="hasActiveFilters"
                    variant="ghost"
                    size="sm"
                    @click="resetFilters"
                    class="h-9 px-2.5 text-xs text-muted-foreground hover:text-foreground"
                >
                    <RotateCcw class="mr-1.5 h-3.5 w-3.5" />
                    Reset
                </Button>
            </div>
        </div>

        <!-- Data Table Card -->
        <div class="rounded-xl border border-border/80 bg-card shadow-xs overflow-hidden">
            <DataTable :table="table" class="border-0 shadow-none rounded-none" />
            <div class="p-4 border-t border-border/60">
                <DataTablePagination
                    :total="municipalities.total"
                    :from="municipalities.from"
                    :to="municipalities.to"
                    :current-page="municipalities.current_page"
                    :last-page="municipalities.last_page"
                    :per-page="municipalities.per_page"
                    @page-change="applyFilters({ page: $event })"
                    @per-page-change="applyFilters({ per_page: $event, page: 1 })"
                />
            </div>
        </div>

        <!-- Dialogs -->
        <MunicipalityFormDialog
            v-model:open="isFormDialogOpen"
            :municipality="selectedMunicipality"
            @success="applyFilters"
        />

        <MunicipalityDeleteDialog
            v-model:open="isDeleteDialogOpen"
            :municipality="selectedMunicipality"
            @success="applyFilters"
        />
    </div>
</template>
