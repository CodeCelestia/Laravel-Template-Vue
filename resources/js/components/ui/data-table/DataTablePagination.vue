<script setup lang="ts">
import type { Table } from '@tanstack/vue-table';
import { ChevronLeft, ChevronRight, ChevronsLeft, ChevronsRight } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

interface PaginationProps {
    table?: Table<any>;
    // Server-side props
    total?: number;
    from?: number | null;
    to?: number | null;
    currentPage?: number;
    lastPage?: number;
    perPage?: number;
    perPageOptions?: number[];
}

const props = withDefaults(defineProps<PaginationProps>(), {
    perPageOptions: () => [10, 25, 50, 100],
    perPage: 10,
    currentPage: 1,
    lastPage: 1,
    total: 0,
});

const emit = defineEmits<{
    (e: 'page-change', page: number): void;
    (e: 'per-page-change', perPage: number): void;
}>();

function onPageChange(page: number) {
    if (props.table) {
        props.table.setPageIndex(page - 1);
    }
    emit('page-change', page);
}

function onPerPageChange(value: unknown) {
    if (value === null || value === undefined) {
        return;
    }
    const num = Number(value);
    if (props.table) {
        props.table.setPageSize(num);
    }
    emit('per-page-change', num);
}

const activeCurrentPage = () => {
    if (props.table) {
        return props.table.getState().pagination.pageIndex + 1;
    }
    return props.currentPage;
};

const activeTotalPages = () => {
    if (props.table) {
        return props.table.getPageCount();
    }
    return props.lastPage;
};

const canPrevious = () => {
    if (props.table) {
        return props.table.getCanPreviousPage();
    }
    return props.currentPage > 1;
};

const canNext = () => {
    if (props.table) {
        return props.table.getCanNextPage();
    }
    return props.currentPage < props.lastPage;
};
</script>

<template>
    <div class="flex flex-col items-center justify-between gap-4 px-2 py-4 sm:flex-row">
        <!-- Results Count Info -->
        <div class="text-sm text-muted-foreground">
            <template v-if="total > 0 && from !== null && to !== null">
                Showing <span class="font-medium text-foreground">{{ from }}</span> to
                <span class="font-medium text-foreground">{{ to }}</span> of
                <span class="font-medium text-foreground">{{ total }}</span> results
            </template>
            <template v-else-if="table">
                Showing
                <span class="font-medium text-foreground">
                    {{ table.getState().pagination.pageIndex * table.getState().pagination.pageSize + 1 }}
                </span>
                to
                <span class="font-medium text-foreground">
                    {{
                        Math.min(
                            (table.getState().pagination.pageIndex + 1) * table.getState().pagination.pageSize,
                            table.getFilteredRowModel().rows.length,
                        )
                    }}
                </span>
                of
                <span class="font-medium text-foreground">
                    {{ table.getFilteredRowModel().rows.length }}
                </span>
                results
            </template>
            <template v-else>
                No results
            </template>
        </div>

        <!-- Controls: Page Size & Navigation -->
        <div class="flex flex-wrap items-center gap-4 sm:gap-6">
            <!-- Rows per page -->
            <div class="flex items-center space-x-2">
                <p class="text-xs font-medium text-muted-foreground sm:text-sm">Rows per page</p>
                <Select
                    :model-value="String(props.table ? props.table.getState().pagination.pageSize : props.perPage)"
                    @update:model-value="onPerPageChange"
                >
                    <SelectTrigger class="h-8 w-[70px]">
                        <SelectValue :placeholder="String(props.perPage)" />
                    </SelectTrigger>
                    <SelectContent side="top">
                        <SelectItem
                            v-for="size in perPageOptions"
                            :key="size"
                            :value="String(size)"
                        >
                            {{ size }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <!-- Page count display -->
            <div class="flex items-center justify-center text-xs font-medium text-muted-foreground sm:text-sm">
                Page {{ activeCurrentPage() }} of {{ Math.max(1, activeTotalPages()) }}
            </div>

            <!-- Navigation Buttons -->
            <div class="flex items-center space-x-1">
                <Button
                    variant="outline"
                    size="icon"
                    class="h-8 w-8 hidden sm:inline-flex"
                    :disabled="!canPrevious()"
                    @click="onPageChange(1)"
                >
                    <span class="sr-only">Go to first page</span>
                    <ChevronsLeft class="h-4 w-4" />
                </Button>
                <Button
                    variant="outline"
                    size="icon"
                    class="h-8 w-8"
                    :disabled="!canPrevious()"
                    @click="onPageChange(activeCurrentPage() - 1)"
                >
                    <span class="sr-only">Go to previous page</span>
                    <ChevronLeft class="h-4 w-4" />
                </Button>
                <Button
                    variant="outline"
                    size="icon"
                    class="h-8 w-8"
                    :disabled="!canNext()"
                    @click="onPageChange(activeCurrentPage() + 1)"
                >
                    <span class="sr-only">Go to next page</span>
                    <ChevronRight class="h-4 w-4" />
                </Button>
                <Button
                    variant="outline"
                    size="icon"
                    class="h-8 w-8 hidden sm:inline-flex"
                    :disabled="!canNext()"
                    @click="onPageChange(activeTotalPages())"
                >
                    <span class="sr-only">Go to last page</span>
                    <ChevronsRight class="h-4 w-4" />
                </Button>
            </div>
        </div>
    </div>
</template>
