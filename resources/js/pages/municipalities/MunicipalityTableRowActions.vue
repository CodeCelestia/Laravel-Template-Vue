<script setup lang="ts">
import { ExternalLink, MoreHorizontal, Pencil, Trash2 } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

export interface MunicipalityItem {
    id: number;
    name: string;
    code: string | null;
    type: 'city' | 'municipality';
    district: string;
    zip_code: string | null;
    location_description: string | null;
    latitude: number | null;
    longitude: number | null;
    map_url: string | null;
    is_active: boolean;
    created_at: string | null;
}

const props = defineProps<{
    municipality: MunicipalityItem;
    canManage: boolean;
}>();

const emit = defineEmits<{
    (e: 'edit', municipality: MunicipalityItem): void;
    (e: 'delete', municipality: MunicipalityItem): void;
}>();
</script>

<template>
    <div class="flex items-center justify-end space-x-1">
        <a
            v-if="municipality.map_url"
            :href="municipality.map_url"
            target="_blank"
            rel="noopener noreferrer"
            class="inline-flex h-8 w-8 items-center justify-center rounded-md text-muted-foreground hover:bg-muted hover:text-foreground transition-colors"
            title="Open in Maps"
        >
            <ExternalLink class="h-4 w-4" />
            <span class="sr-only">Open Maps</span>
        </a>

        <DropdownMenu>
            <DropdownMenuTrigger as-child>
                <Button
                    variant="ghost"
                    size="icon"
                    class="h-8 w-8 text-muted-foreground hover:text-foreground"
                >
                    <MoreHorizontal class="h-4 w-4" />
                    <span class="sr-only">Actions</span>
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" class="w-48">
                <DropdownMenuItem
                    v-if="canManage"
                    class="cursor-pointer"
                    @click="emit('edit', municipality)"
                >
                    <Pencil class="mr-2 h-4 w-4" />
                    <span>Edit Details</span>
                </DropdownMenuItem>
                <DropdownMenuItem
                    v-if="municipality.map_url"
                    as="a"
                    :href="municipality.map_url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="cursor-pointer"
                >
                    <ExternalLink class="mr-2 h-4 w-4" />
                    <span>View on Google Maps</span>
                </DropdownMenuItem>
                <DropdownMenuSeparator v-if="canManage" />
                <DropdownMenuItem
                    v-if="canManage"
                    class="cursor-pointer text-destructive focus:text-destructive"
                    @click="emit('delete', municipality)"
                >
                    <Trash2 class="mr-2 h-4 w-4" />
                    <span>Delete</span>
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    </div>
</template>
