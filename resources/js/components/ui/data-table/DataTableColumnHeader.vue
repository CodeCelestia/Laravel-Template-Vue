<script setup lang="ts" generic="TData, TValue">
import type { Column } from '@tanstack/vue-table';
import type { HTMLAttributes } from 'vue';
import { computed } from 'vue';
import { ArrowDown, ArrowUp, ArrowUpDown } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

interface DataTableColumnHeaderProps {
    column?: Column<TData, TValue>;
    title: string;
    class?: HTMLAttributes['class'];
    sortable?: boolean;
    sortDirection?: 'asc' | 'desc' | false | null;
}

const props = withDefaults(defineProps<DataTableColumnHeaderProps>(), {
    sortable: true,
    sortDirection: null,
});

const emit = defineEmits<{
    (e: 'sort', direction: 'asc' | 'desc' | null): void;
}>();

const currentSort = computed(() => {
    if (props.column) {
        return props.column.getIsSorted();
    }
    return props.sortDirection;
});

function handleSort() {
    if (!props.sortable) {
        return;
    }

    if (props.column) {
        props.column.toggleSorting(props.column.getIsSorted() === 'asc');
        return;
    }

    let nextDirection: 'asc' | 'desc' | null = 'asc';
    if (props.sortDirection === 'asc') {
        nextDirection = 'desc';
    } else if (props.sortDirection === 'desc') {
        nextDirection = null;
    }
    emit('sort', nextDirection);
}
</script>

<template>
    <div v-if="!sortable" :class="cn('text-xs font-semibold uppercase tracking-wider', props.class)">
        {{ title }}
    </div>
    <div v-else :class="cn('flex items-center space-x-2', props.class)">
        <Button
            type="button"
            variant="ghost"
            size="sm"
            class="-ml-3 h-8 data-[state=open]:bg-accent text-xs font-semibold uppercase tracking-wider hover:bg-muted/80"
            @click="handleSort"
        >
            <span>{{ title }}</span>
            <ArrowUp v-if="currentSort === 'asc'" class="ml-2 h-3.5 w-3.5 text-primary" />
            <ArrowDown v-else-if="currentSort === 'desc'" class="ml-2 h-3.5 w-3.5 text-primary" />
            <ArrowUpDown v-else class="ml-2 h-3.5 w-3.5 text-muted-foreground/70" />
        </Button>
    </div>
</template>
