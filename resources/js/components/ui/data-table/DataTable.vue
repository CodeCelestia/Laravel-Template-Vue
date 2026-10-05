<script setup lang="ts" generic="TData">
import type { HTMLAttributes } from 'vue';
import type { Table as TanstackTable } from '@tanstack/vue-table';
import { FlexRender } from '@tanstack/vue-table';
import { Database } from '@lucide/vue';
import { cn } from '@/lib/utils';
import {
    Table,
    TableBody,
    TableCell,
    TableEmpty,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Skeleton } from '@/components/ui/skeleton';

interface DataTableProps {
    table: TanstackTable<TData>;
    isLoading?: boolean;
    emptyMessage?: string;
    skeletonRows?: number;
    class?: HTMLAttributes['class'];
}

const props = withDefaults(defineProps<DataTableProps>(), {
    isLoading: false,
    emptyMessage: 'No results found.',
    skeletonRows: 5,
});
</script>

<template>
    <div :class="cn('rounded-xl border bg-card text-card-foreground shadow-sm overflow-hidden', props.class)">
        <Table>
            <TableHeader class="bg-muted/40">
                <TableRow
                    v-for="headerGroup in table.getHeaderGroups()"
                    :key="headerGroup.id"
                    class="hover:bg-transparent"
                >
                    <TableHead
                        v-for="header in headerGroup.headers"
                        :key="header.id"
                        :style="{ width: header.getSize() !== 150 ? `${header.getSize()}px` : undefined }"
                        class="h-11 font-semibold text-muted-foreground text-xs uppercase tracking-wider"
                    >
                        <FlexRender
                            v-if="!header.isPlaceholder"
                            :render="header.column.columnDef.header"
                            :props="header.getContext()"
                        />
                    </TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                <!-- Loading State Skeleton -->
                <template v-if="isLoading">
                    <TableRow v-for="index in skeletonRows" :key="`skeleton-${index}`">
                        <TableCell
                            v-for="column in table.getAllColumns()"
                            :key="`skeleton-${index}-${column.id}`"
                            class="p-4"
                        >
                            <Skeleton class="h-5 w-full max-w-[160px] rounded" />
                        </TableCell>
                    </TableRow>
                </template>

                <!-- Data Rows -->
                <template v-else-if="table.getRowModel().rows?.length">
                    <TableRow
                        v-for="row in table.getRowModel().rows"
                        :key="row.id"
                        :data-state="row.getIsSelected() ? 'selected' : undefined"
                        class="transition-colors hover:bg-muted/30 data-[state=selected]:bg-muted/50"
                    >
                        <TableCell
                            v-for="cell in row.getVisibleCells()"
                            :key="cell.id"
                            class="p-4 align-middle"
                        >
                            <FlexRender
                                :render="cell.column.columnDef.cell"
                                :props="cell.getContext()"
                            />
                        </TableCell>
                    </TableRow>
                </template>

                <!-- Empty State -->
                <template v-else>
                    <TableEmpty :colspan="table.getAllColumns().length">
                        <div class="flex flex-col items-center justify-center py-10 text-center">
                            <slot name="empty">
                                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-muted/60 text-muted-foreground mb-3">
                                    <Database class="h-6 w-6" />
                                </div>
                                <h3 class="text-sm font-semibold text-foreground">No records</h3>
                                <p class="text-xs text-muted-foreground mt-1 max-w-sm">
                                    {{ emptyMessage }}
                                </p>
                            </slot>
                        </div>
                    </TableEmpty>
                </template>
            </TableBody>
        </Table>
    </div>
</template>
